<?php
namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentControlRegistration;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\Plant;
use App\Models\Pic;
use App\Models\ProjectA00Form;
use App\Models\ProjectA00Item;
use App\Models\ProjectWorkflowTask;
use App\Services\Costing\CostingGroupService;
use App\Services\ControlProject\A00ExcelPdfService;
use App\Support\BusinessCategoryContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectA00Controller extends Controller
{
    public function index(Request $request)
    {
        $tab=in_array($request->query('tab'),['pending','issued'],true)?(string)$request->query('tab'):'pending';
        $search=trim((string)$request->query('q'));
        $query=ProjectA00Form::with(['project','items'])->latest('document_date')->latest('id');
        BusinessCategoryContext::apply($query);
        if($search!=='') $query->where(fn($q)=>$q->where('document_number','like',"%{$search}%")->orWhere('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")->orWhere('assy_number','like',"%{$search}%"));

        $pendingQuery=DocumentProject::with(['product','revisions'=>fn($q)=>$q->latest('version_number'),'workflowTasks'])
            ->whereDoesntHave('a00Form')->whereDoesntHave('a00Item')
            ->whereHas('workflowTasks',fn($q)=>$q->where(function($sourceQuery){
                $sourceQuery->where('metadata->source','manual_drawing_registration')
                    ->orWhere('metadata->source','manual_breakdown')
                    ->orWhere('metadata->source','new_project_draft');
            }))
            ->when($search!=='',fn($q)=>$q->where(fn($projectQuery)=>$projectQuery
                ->where('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")
                ->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%")))
            ->latest('updated_at')->latest('id');
        BusinessCategoryContext::applyToProjects($pendingQuery);

        return view('control-project.a00.index',[
            'forms'=>$query->paginate(25,['*'],'a00_page')->withQueryString(),
            'pendingProjects'=>$pendingQuery->paginate(25,['*'],'pending_page')->withQueryString(),
            'tab'=>$tab,
        ]);
    }

    public function create(Request $request)
    {
        $projectIds=collect($request->input('project_ids',[]));
        if($request->filled('project_id')) $projectIds->push($request->integer('project_id'));
        $projectIds=$projectIds->map(fn($id)=>(int)$id)->filter()->unique()->values();
        abort_if($projectIds->count()>100,422,'Maksimal 100 project dapat digabungkan dalam satu A00.');
        $sourceProjects=collect();
        if($projectIds->isNotEmpty()){
            $sourceProjects=DocumentProject::with(['product','revisions'=>fn($q)=>$q->latest('version_number')])
                ->whereDoesntHave('a00Form')->whereDoesntHave('a00Item')
                ->whereIn('id',$projectIds)->get()->sortBy(fn($project)=>$projectIds->search($project->id))->values();
            abort_if($sourceProjects->count()!==$projectIds->count(),422,'Salah satu project sudah memiliki A00 atau tidak ditemukan.');
            abort_if($sourceProjects->pluck('customer')->map(fn($value)=>mb_strtolower(trim((string)$value)))->unique()->count()>1,422,'Project gabungan harus memiliki customer yang sama.');
            abort_if($sourceProjects->pluck('product.code')->filter()->unique()->count()>1,422,'Project gabungan harus memiliki Business Category yang sama.');
        }
        $sourceProject=$sourceProjects->first();
        return view('control-project.a00.create',[
            'customers'=>Customer::orderBy('name')->get(), 'categories'=>BusinessCategory::orderBy('name')->get(),
            'plants'=>Plant::orderBy('code')->get(),
            'picsEngineering'=>Pic::where('type','engineering')->orderBy('name')->get(),
            'picsMarketing'=>Pic::where('type','marketing')->orderBy('name')->get(),
            'picsDirector'=>Pic::where('type','director')->orderBy('name')->get(),
            'picsDivMarketing'=>Pic::where('type','div_marketing')->orderBy('name')->get(),
            'nextNumber'=>null,
            'sourceProject'=>$sourceProject,
            'sourceProjects'=>$sourceProjects,
            'defaultCategoryId'=>BusinessCategoryContext::selectedId(),
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'document_number'=>['required','string','max:150','unique:project_a00_forms,document_number'],
            'plant_id'=>['required','exists:plants,id'],'pic_engineering'=>['required','string','max:255'],
            'pic_marketing'=>['required','string','max:255'],'period'=>['required','date_format:Y-m'],
            'business_category_id'=>['required','exists:business_categories,id'],'customer_id'=>['required','exists:customers,id'],
            'items'=>['required','array','min:1','max:100'],'items.*.model'=>['required','string','max:255'],
            'items.*.document_project_id'=>['nullable','integer','exists:document_projects,id'],
            'items.*.assy_name'=>['required','string','max:255'],'items.*.assy_number'=>['required','string','max:255'],
            'items.*.quantity'=>['nullable','integer','min:0'],'items.*.quantity_uom'=>['required','string','max:20'],
            'items.*.quantity_basis'=>['required','string','max:30'],'items.*.product_life_years'=>['nullable','integer','min:0','max:99'],
            'items.*.spot_order'=>['nullable','boolean'],
            'document_date'=>['required','date'],'revision'=>['required','string','max:10'],'from_department'=>['required','string','max:100'],'to_department'=>['required','string','max:100'],
            'request_type'=>['nullable','in:RFI,RFQ,OTHER'],'request_number'=>['nullable','string','max:100'],'request_received_date'=>['nullable','date'],
            'source_file'=>['nullable','file','mimes:pdf,xls,xlsx,doc,docx','max:10240'],'due_part_list'=>['nullable','date'],'due_umh'=>['nullable','date'],'due_new_part_price'=>['nullable','date'],
            'due_costing'=>['nullable','date'],'due_submit_quotation'=>['nullable','date'],'pp1_date'=>['nullable','date'],'pp2_date'=>['nullable','date'],
            'pp3_date'=>['nullable','date'],'sop_mp_date'=>['nullable','date'],'sop_mp_tba'=>['nullable','boolean'],'issue_location'=>['required','string','max:100'],
            'customer_events'=>['nullable','array','min:1','max:20'],'customer_events.*.name'=>['required','string','max:100'],
            'customer_events.*.date'=>['nullable','date'],'customer_events.*.tba'=>['nullable','boolean'],
            'prepared_by'=>['nullable','string','max:255'],'acknowledged_by'=>['nullable','string','max:255'],'approved_by'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:2000'],
            'prepared_signature'=>['nullable','file','mimes:png','max:2048'],'acknowledged_signature'=>['nullable','file','mimes:png','max:2048'],'approved_signature'=>['nullable','file','mimes:png','max:2048'],
        ]);
        $data['customer_events']=$this->normalizeCustomerEvents($data['customer_events'] ?? []);
        $data['prepared_by']=trim((string)($data['prepared_by']??'')) ?: $data['pic_marketing'];
        $data['acknowledged_by']=trim((string)($data['acknowledged_by']??'')) ?: 'L. Andri H';
        $data['approved_by']=trim((string)($data['approved_by']??'')) ?: 'Y. Susanto';
        $customer=Customer::findOrFail($data['customer_id']); $category=BusinessCategory::findOrFail($data['business_category_id']);
        abort_if(BusinessCategoryContext::selectedId() && BusinessCategoryContext::selectedId() !== $category->id,422,'Business Category form harus sama dengan kategori aktif.');
        $file=$request->file('source_file'); $stored=$file?['name'=>$file->getClientOriginalName(),'path'=>$file->store('control-project/a00-sources')]:['name'=>null,'path'=>null];
        $signaturePaths=[];
        foreach (['prepared','acknowledged','approved'] as $signatureType) {
            $signaturePaths[$signatureType.'_signature_path']=$this->storeSignature($request->file($signatureType.'_signature'),$signatureType);
            unset($data[$signatureType.'_signature']);
        }
        $form=DB::transaction(function() use($data,$customer,$category,$stored,$signaturePaths,$request){
            $product=Product::firstOrCreate(['code'=>$category->code ?: strtoupper(Str::slug($category->name,'-'))],['name'=>$category->name,'line'=>'']);
            $created=[];
            $linkedProjectCount=collect($data['items'])->pluck('document_project_id')->filter()->unique()->count();
            foreach($data['items'] as $index=>$item){
                $existingProjectId=(int)($item['document_project_id']??0);
                unset($item['document_project_id']);
                if($existingProjectId>0){
                    $project=DocumentProject::lockForUpdate()->findOrFail($existingProjectId);
                    abort_if($project->a00Form()->exists()||$project->a00Item()->exists(),422,'Project pada baris '.($index+1).' sudah memiliki A00.');
                    if($linkedProjectCount>1){
                        abort_if(mb_strtolower(trim((string)$project->customer))!==mb_strtolower(trim($customer->name)),422,'Semua project gabungan harus memiliki customer yang sama.');
                        abort_if($project->product?->code!==$category->code,422,'Semua project gabungan harus memiliki Business Category yang sama.');
                    }
                    $key=hash('sha256',mb_strtolower(implode('|',[trim($customer->name),trim($item['model']),trim($item['assy_number']),trim($item['assy_name'])])));
                    $duplicate=DocumentProject::where('project_key',$key)->whereKeyNot($project->id)->exists();
                    abort_if($duplicate,422,'Data project pada baris '.($index+1).' sama dengan project lain.');
                    $project->update(['product_id'=>$product->id,'customer'=>$customer->name,'model'=>$item['model'],'part_number'=>$item['assy_number'],'part_name'=>$item['assy_name'],'project_key'=>$key]);
                    $revision=$project->revisions()->latest('version_number')->lockForUpdate()->firstOrFail();
                    $revision->update(['received_date'=>$data['document_date'],'plant_id'=>$data['plant_id'],'period'=>$data['period'],'pic_engineering'=>$data['pic_engineering'],'pic_marketing'=>$data['pic_marketing'],'status'=>DocumentRevision::STATUS_A00_ISSUED,'a00'=>'ada','a00_received_date'=>$data['document_date'],'notes'=>$data['notes']??$revision->notes,'change_remark'=>'A00 New Project Declaration diterbitkan untuk project yang telah diregistrasi.']);
                }else{
                    $key=hash('sha256',mb_strtolower(implode('|',[trim($customer->name),trim($item['model']),trim($item['assy_number']),trim($item['assy_name'])])));
                    if(DocumentProject::where('project_key',$key)->exists()) abort(422,'Project pada baris '.($index+1).' sudah ada. Pilih project tersebut dari daftar menunggu A00.');
                    $project=DocumentProject::create(['product_id'=>$product->id,'customer'=>$customer->name,'model'=>$item['model'],'part_number'=>$item['assy_number'],'part_name'=>$item['assy_name'],'project_key'=>$key]);
                    $revision=DocumentRevision::create(['document_project_id'=>$project->id,'version_number'=>1,'received_date'=>$data['document_date'],'plant_id'=>$data['plant_id'],'period'=>$data['period'],'pic_engineering'=>$data['pic_engineering'],'pic_marketing'=>$data['pic_marketing'],'status'=>DocumentRevision::STATUS_A00_ISSUED,'a00'=>'ada','a00_received_date'=>$data['document_date'],'partlist_original_name'=>'','partlist_file_path'=>'','umh_original_name'=>'','umh_file_path'=>'','notes'=>$data['notes']??null,'change_remark'=>'A00 New Project Declaration diterbitkan.']);
                }
                $created[]=['project'=>$project,'revision'=>$revision,'item'=>$item];
            }
            $first=$created[0]; $items=$data['items']; unset($data['items'],$data['business_category_id'],$data['customer_id'],$data['source_file'],$data['plant_id'],$data['period'],$data['pic_engineering'],$data['pic_marketing']);
            $form=ProjectA00Form::create($data+$signaturePaths+['document_project_id'=>$first['project']->id,'document_revision_id'=>$first['revision']->id,'customer'=>$customer->name,'model'=>$first['item']['model'],'assy_number'=>$first['item']['assy_number'],'assy_name'=>$first['item']['assy_name'],'quantity'=>$first['item']['quantity']??null,'quantity_uom'=>$first['item']['quantity_uom'],'quantity_basis'=>$first['item']['quantity_basis'],'product_life_years'=>$first['item']['product_life_years']??null,'spot_order'=>!empty($first['item']['spot_order']),'source_file_name'=>$stored['name'],'source_file_path'=>$stored['path'],'status'=>'issued','issued_at'=>now(),'created_by'=>$request->user()->id]);
            foreach($created as $index=>$entry) {
                ProjectA00Item::create(['project_a00_form_id'=>$form->id,'document_project_id'=>$entry['project']->id,'document_revision_id'=>$entry['revision']->id,'line_number'=>$index+1]+$entry['item']);
                $drawingTask=ProjectWorkflowTask::firstOrCreate([
                    'document_revision_id'=>$entry['revision']->id,
                    'stage'=>ProjectWorkflowTask::STAGE_DRAWING,
                ],[
                    'document_project_id'=>$entry['project']->id,
                    'assigned_role'=>'document_control',
                    'status'=>ProjectWorkflowTask::STATUS_PENDING,
                    'available_at'=>now(),
                    'notes'=>'A00 '.$form->document_number.' telah diterbitkan. Menunggu registrasi dan distribusi drawing.',
                    'metadata'=>['a00_form_id'=>$form->id,'a00_number'=>$form->document_number],
                ]);
                $drawingTask->update(['metadata'=>array_merge($drawingTask->metadata??[],['a00_form_id'=>$form->id,'a00_number'=>$form->document_number,'without_a00'=>false])]);
                ProjectWorkflowTask::where('document_revision_id',$entry['revision']->id)->get()->each(function($task)use($form){
                    $task->update(['metadata'=>array_merge($task->metadata??[],['a00_form_id'=>$form->id,'a00_number'=>$form->document_number,'without_a00'=>false])]);
                });
                DocumentControlRegistration::where('document_revision_id',$entry['revision']->id)->update(['a00'=>'ada']);

                if (count($created) > 1) {
                    ProjectWorkflowTask::firstOrCreate([
                        'document_revision_id' => $entry['revision']->id,
                        'stage' => ProjectWorkflowTask::STAGE_BREAKDOWN,
                    ], [
                        'document_project_id' => $entry['project']->id,
                        'assigned_role' => 'admin_costing',
                        'status' => ProjectWorkflowTask::STATUS_PENDING,
                        'available_at' => now(),
                        'notes' => 'A00 Gabung dapat langsung diproses di Breakdown tanpa menunggu distribusi Drawing.',
                        'metadata' => [
                            'source' => 'a00_group_direct',
                            'a00_form_id' => $form->id,
                            'a00_number' => $form->document_number,
                            'drawing_optional' => true,
                        ],
                    ]);
                }
            }
            app(CostingGroupService::class)->syncFromA00($form, $request->user()->id);
            return $form;
        });
        if ($request->boolean('embedded')) {
            return response()->view('control-project.a00.created', ['form' => $form]);
        }
        return redirect()->route('control-project.a00.show',$form)->with('success','A00 diterbitkan dan Project V0 berhasil dibuat.');
    }

    public function show(ProjectA00Form $a00){
        // Detail A00 bersifat read-only. Hindari sinkronisasi costing (beserta
        // seluruh query/update turunannya) pada setiap kunjungan halaman.
        // Sinkronisasi tetap dijalankan sekali untuk data lama yang belum
        // mempunyai costing group.
        if (!$a00->costingGroup()->exists()) {
            app(CostingGroupService::class)->syncFromA00($a00, auth()->id());
        }

        $a00->load('project','projectRevision','items.project','costingGroup.items.a00Item','costingGroup.items.project','costingGroup.items.costingData','costingGroup.items.revision');
        return view('control-project.a00.show',['a00'=>$a00]);
    }

    public function downloadPdf(ProjectA00Form $a00, A00ExcelPdfService $pdfService)
    {
        $filename='A00 - '.str_replace(['<','>',':','"','/','\\','|','?','*'],'-',$a00->document_number).'.pdf';
        $path=$pdfService->generate($a00);$content=(string)file_get_contents($path);@unlink($path);
        return response($content,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }

    public function downloadExcel(ProjectA00Form $a00, A00ExcelPdfService $excelService)
    {
        $filename='A00 - '.str_replace(['<','>',':','"','/','\\','|','?','*'],'-',$a00->document_number).'.xlsx';
        $path=$excelService->generateExcel($a00);

        return response()->download($path,$filename,[
            'Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function edit(ProjectA00Form $a00)
    {
        $a00->load(['project.product','projectRevision.plant','items.project.product','items.projectRevision']);
        return view('control-project.a00.edit', [
            'a00'=>$a00,'customers'=>Customer::orderBy('name')->get(),
            'categories'=>BusinessCategory::orderBy('name')->get(),'plants'=>Plant::orderBy('code')->get(),
            'picsEngineering'=>Pic::where('type','engineering')->orderBy('name')->get(),
            'picsMarketing'=>Pic::where('type','marketing')->orderBy('name')->get(),
            'picsDirector'=>Pic::where('type','director')->orderBy('name')->get(),
            'picsDivMarketing'=>Pic::where('type','div_marketing')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ProjectA00Form $a00)
    {
        $data=$request->validate([
            'document_number'=>['required','string','max:150','unique:project_a00_forms,document_number,'.$a00->id],
            'document_date'=>['required','date'],'revision'=>['required','string','max:10'],
            'from_department'=>['required','string','max:100'],'to_department'=>['required','string','max:100'],
            'request_type'=>['nullable','in:RFI,RFQ,OTHER'],'request_number'=>['nullable','string','max:100'],
            'request_received_date'=>['nullable','date'],'source_file'=>['nullable','file','mimes:pdf,xls,xlsx,doc,docx','max:10240'],
            'business_category_id'=>['required','exists:business_categories,id'],'customer_id'=>['required','exists:customers,id'],
            'plant_id'=>['required','exists:plants,id'],'period'=>['required','date_format:Y-m'],
            'pic_engineering'=>['required','string','max:255'],'pic_marketing'=>['required','string','max:255'],
            'items'=>['required','array','min:1'],'items.*.id'=>['required','exists:project_a00_items,id'],
            'items.*.model'=>['required','string','max:255'],'items.*.assy_name'=>['required','string','max:255'],
            'items.*.assy_number'=>['required','string','max:255'],'items.*.quantity'=>['nullable','integer','min:0'],
            'items.*.quantity_uom'=>['required','string','max:20'],'items.*.quantity_basis'=>['required','string','max:30'],
            'items.*.product_life_years'=>['nullable','integer','min:0','max:99'],'items.*.spot_order'=>['nullable','boolean'],
            'due_part_list'=>['nullable','date'],'due_umh'=>['nullable','date'],'due_new_part_price'=>['nullable','date'],
            'due_costing'=>['nullable','date'],'due_submit_quotation'=>['nullable','date'],'pp1_date'=>['nullable','date'],
            'pp2_date'=>['nullable','date'],'pp3_date'=>['nullable','date'],'sop_mp_date'=>['nullable','date'],
            'sop_mp_tba'=>['nullable','boolean'],'issue_location'=>['required','string','max:100'],
            'customer_events'=>['nullable','array','min:1','max:20'],'customer_events.*.name'=>['required','string','max:100'],
            'customer_events.*.date'=>['nullable','date'],'customer_events.*.tba'=>['nullable','boolean'],
            'prepared_by'=>['nullable','string','max:255'],'acknowledged_by'=>['nullable','string','max:255'],
            'approved_by'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:2000'],
            'prepared_signature'=>['nullable','file','mimes:png','max:2048'],
            'acknowledged_signature'=>['nullable','file','mimes:png','max:2048'],
            'approved_signature'=>['nullable','file','mimes:png','max:2048'],
        ]);

        $data['customer_events']=$this->normalizeCustomerEvents($data['customer_events'] ?? $a00->customer_events ?? []);
        $data['prepared_by']=trim((string)($data['prepared_by']??'')) ?: $data['pic_marketing'];
        $data['acknowledged_by']=trim((string)($data['acknowledged_by']??'')) ?: 'L. Andri H';
        $data['approved_by']=trim((string)($data['approved_by']??'')) ?: 'Y. Susanto';
        $customer=Customer::findOrFail($data['customer_id']);
        $category=BusinessCategory::findOrFail($data['business_category_id']);
        $storedFile=$request->file('source_file');
        $newSignaturePaths=[];
        foreach (['prepared','acknowledged','approved'] as $signatureType) {
            if ($request->hasFile($signatureType.'_signature')) {
                $newSignaturePaths[$signatureType.'_signature_path']=$this->storeSignature($request->file($signatureType.'_signature'),$signatureType);
            }
            unset($data[$signatureType.'_signature']);
        }
        $oldSignaturePaths=collect(array_keys($newSignaturePaths))->map(fn($column)=>$a00->{$column})->filter();

        DB::transaction(function() use($data,$a00,$customer,$category,$storedFile,$newSignaturePaths){
            $product=Product::firstOrCreate(['code'=>$category->code ?: strtoupper(Str::slug($category->name,'-'))],['name'=>$category->name,'line'=>'']);
            $a00->load('items.project','items.projectRevision');
            foreach($data['items'] as $itemData){
                $item=$a00->items->firstWhere('id',(int)$itemData['id']);
                abort_unless($item,422,'Item A00 tidak valid.');
                $key=hash('sha256',mb_strtolower(implode('|',[trim($customer->name),trim($itemData['model']),trim($itemData['assy_number']),trim($itemData['assy_name'])])));
                $item->project->update(['product_id'=>$product->id,'customer'=>$customer->name,'model'=>$itemData['model'],'part_number'=>$itemData['assy_number'],'part_name'=>$itemData['assy_name'],'project_key'=>$key]);
                $item->projectRevision->update(['received_date'=>$data['document_date'],'plant_id'=>$data['plant_id'],'period'=>$data['period'],'pic_engineering'=>$data['pic_engineering'],'pic_marketing'=>$data['pic_marketing'],'a00_received_date'=>$data['document_date'],'notes'=>$data['notes']??null]);
                $item->update(collect($itemData)->except('id')->all()+['spot_order'=>!empty($itemData['spot_order'])]);
            }
            $first=$a00->items->firstWhere('id',(int)$data['items'][0]['id']);
            $formData=collect($data)->except(['items','business_category_id','customer_id','plant_id','period','pic_engineering','pic_marketing','source_file'])->all();
            $formData += $newSignaturePaths+['customer'=>$customer->name,'model'=>$data['items'][0]['model'],'assy_number'=>$data['items'][0]['assy_number'],'assy_name'=>$data['items'][0]['assy_name'],'quantity'=>$data['items'][0]['quantity']??null,'quantity_uom'=>$data['items'][0]['quantity_uom'],'quantity_basis'=>$data['items'][0]['quantity_basis'],'product_life_years'=>$data['items'][0]['product_life_years']??null,'spot_order'=>!empty($data['items'][0]['spot_order'])];
            if($storedFile){$formData['source_file_name']=$storedFile->getClientOriginalName();$formData['source_file_path']=$storedFile->store('control-project/a00-sources');}
            $a00->update($formData);
            ProjectWorkflowTask::whereIn('document_revision_id',$a00->items->pluck('document_revision_id'))->where('stage',ProjectWorkflowTask::STAGE_DRAWING)->get()->each(function($task) use($a00){$task->update(['metadata'=>array_merge($task->metadata??[],['a00_number'=>$a00->document_number])]);});
            app(CostingGroupService::class)->syncFromA00($a00, auth()->id());
        });
        $oldSignaturePaths->each(fn($path)=>Storage::disk('public')->delete($path));

        if($request->boolean('embedded')) return response('<!doctype html><script>parent.postMessage({type:"a00-updated"},location.origin)</script>');
        return redirect()->route('control-project.a00.index')->with('success','New Project Declaration berhasil diperbarui.');
    }

    public function editOperational(ProjectA00Form $a00)
    {
        $a00->load(['projectRevision.plant','items.projectRevision']);
        return view('control-project.a00.edit-operational', [
            'a00' => $a00,
            'plants' => Plant::orderBy('code')->get(),
            'picsEngineering' => Pic::where('type','engineering')->orderBy('name')->get(),
            'picsMarketing' => Pic::where('type','marketing')->orderBy('name')->get(),
        ]);
    }

    public function updateOperational(Request $request, ProjectA00Form $a00)
    {
        $data = $request->validate([
            'plant_id' => ['required','exists:plants,id'],
            'period' => ['required','date_format:Y-m'],
            'pic_engineering' => ['required','string','max:255'],
            'pic_marketing' => ['required','string','max:255'],
        ]);

        $a00->load('items');
        $revisionIds = $a00->items->pluck('document_revision_id')
            ->push($a00->document_revision_id)->filter()->unique();
        DocumentRevision::whereIn('id', $revisionIds)->update($data);
        app(CostingGroupService::class)->syncFromA00($a00, $request->user()->id);

        if ($request->boolean('embedded')) {
            return response('<!doctype html><script>parent.postMessage({type:"a00-updated"},location.origin)</script>');
        }
        return redirect()->route('control-project.a00.index')->with('success','Data operasional project berhasil diperbarui.');
    }

    public function destroy(ProjectA00Form $a00)
    {
        $number = $a00->document_number;
        $sourcePath = $a00->source_file_path;
        $signaturePaths = collect([
            $a00->prepared_signature_path,
            $a00->acknowledged_signature_path,
            $a00->approved_signature_path,
        ])->filter()->values();

        DB::transaction(function () use ($a00) {
            $a00->load('items','costingGroup');
            $projectIds = $a00->items->pluck('document_project_id')
                ->push($a00->document_project_id)->filter()->unique()->values();

            // Putus referensi versi terakhir sebelum cascade menghapus group beserta versinya.
            if ($a00->costingGroup) {
                $groupId = $a00->costingGroup->id;
                $versionIds = DB::table('costing_group_versions')->where('costing_group_id',$groupId)->pluck('id');
                DB::table('costing_groups')->where('id',$groupId)->update(['last_submitted_version_id'=>null]);
                DB::table('costing_group_events')->where('costing_group_id',$groupId)->delete();
                DB::table('costing_group_version_items')->whereIn('costing_group_version_id',$versionIds)->delete();
                DB::table('costing_group_versions')->where('costing_group_id',$groupId)->delete();
                DB::table('costing_group_items')->where('costing_group_id',$groupId)->delete();
                DB::table('costing_groups')->where('id',$groupId)->delete();
            }
            $a00->delete();
            DocumentProject::whereIn('id', $projectIds)->delete();
        });

        if ($sourcePath) Storage::delete($sourcePath);
        $signaturePaths->each(fn ($path) => Storage::disk('public')->delete($path));

        return redirect()->route('control-project.a00.index')
            ->with('success', "A00 {$number} beserta workflow terkait berhasil dihapus.");
    }

    private function normalizeCustomerEvents(array $events): array
    {
        return collect($events)->map(fn (array $event) => [
            'name' => trim((string) ($event['name'] ?? '')),
            'date' => filled($event['date'] ?? null) ? (string) $event['date'] : null,
            'tba' => (bool) ($event['tba'] ?? false),
        ])->values()->all();
    }

    private function storeSignature(?\Illuminate\Http\UploadedFile $file, string $type): ?string
    {
        if (!$file) return null;
        return $file->storeAs(
            'control-project/a00-signatures/'.now()->format('Y/m'),
            Str::uuid().'-'.$type.'.png',
            'public'
        );
    }

}
