<?php
namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\ProjectA00Form;
use App\Models\ProjectA00Item;
use App\Models\ProjectWorkflowTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectA00Controller extends Controller
{
    public function index(Request $request)
    {
        $query=ProjectA00Form::with(['project','items'])->latest('document_date')->latest('id');
        if($search=trim((string)$request->query('q'))) $query->where(fn($q)=>$q->where('document_number','like',"%{$search}%")->orWhere('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")->orWhere('assy_number','like',"%{$search}%"));
        return view('control-project.a00.index',['forms'=>$query->paginate(25)->withQueryString()]);
    }

    public function create()
    {
        return view('control-project.a00.create',[
            'customers'=>Customer::orderBy('name')->get(), 'categories'=>BusinessCategory::orderBy('name')->get(),
            'nextNumber'=>null,
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'document_number'=>['required','string','max:150','unique:project_a00_forms,document_number'],
            'business_category_id'=>['required','exists:business_categories,id'],'customer_id'=>['required','exists:customers,id'],
            'items'=>['required','array','min:1','max:100'],'items.*.model'=>['required','string','max:255'],
            'items.*.assy_name'=>['required','string','max:255'],'items.*.assy_number'=>['required','string','max:255'],
            'items.*.quantity'=>['nullable','integer','min:0'],'items.*.quantity_uom'=>['required','string','max:20'],
            'items.*.quantity_basis'=>['required','string','max:30'],'items.*.product_life_years'=>['nullable','integer','min:0','max:99'],
            'items.*.spot_order'=>['nullable','boolean'],
            'document_date'=>['required','date'],'revision'=>['required','string','max:10'],'from_department'=>['required','string','max:100'],'to_department'=>['required','string','max:100'],
            'request_type'=>['nullable','in:RFI,RFQ,OTHER'],'request_number'=>['nullable','string','max:100'],'request_received_date'=>['nullable','date'],
            'source_file'=>['nullable','file','mimes:pdf,xls,xlsx,doc,docx','max:10240'],'due_part_list'=>['nullable','date'],'due_umh'=>['nullable','date'],'due_new_part_price'=>['nullable','date'],
            'due_costing'=>['nullable','date'],'due_submit_quotation'=>['nullable','date'],'pp1_date'=>['nullable','date'],'pp2_date'=>['nullable','date'],
            'pp3_date'=>['nullable','date'],'sop_mp_date'=>['nullable','date'],'sop_mp_tba'=>['nullable','boolean'],'issue_location'=>['required','string','max:100'],
            'prepared_by'=>['nullable','string','max:255'],'acknowledged_by'=>['nullable','string','max:255'],'approved_by'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:2000'],
            'prepared_signature'=>['nullable','string','max:150000'],'acknowledged_signature'=>['nullable','string','max:150000'],'approved_signature'=>['nullable','string','max:150000'],
        ]);
        $customer=Customer::findOrFail($data['customer_id']); $category=BusinessCategory::findOrFail($data['business_category_id']);
        $file=$request->file('source_file'); $stored=$file?['name'=>$file->getClientOriginalName(),'path'=>$file->store('control-project/a00-sources')]:['name'=>null,'path'=>null];
        $signaturePaths=[];
        foreach (['prepared','acknowledged','approved'] as $signatureType) {
            $signaturePaths[$signatureType.'_signature_path']=$this->storeSignature($data[$signatureType.'_signature']??null,$signatureType);
            unset($data[$signatureType.'_signature']);
        }
        $form=DB::transaction(function() use($data,$customer,$category,$stored,$signaturePaths,$request){
            $product=Product::firstOrCreate(['code'=>$category->code ?: strtoupper(Str::slug($category->name,'-'))],['name'=>$category->name,'line'=>'']);
            $created=[];
            foreach($data['items'] as $index=>$item){
                $key=hash('sha256',mb_strtolower(implode('|',[trim($customer->name),trim($item['model']),trim($item['assy_number']),trim($item['assy_name'])])));
                if(DocumentProject::where('project_key',$key)->exists()) abort(422,'Project pada baris '.($index+1).' sudah ada.');
                $project=DocumentProject::create(['product_id'=>$product->id,'customer'=>$customer->name,'model'=>$item['model'],'part_number'=>$item['assy_number'],'part_name'=>$item['assy_name'],'project_key'=>$key]);
                $revision=DocumentRevision::create(['document_project_id'=>$project->id,'version_number'=>1,'received_date'=>$data['document_date'],'pic_engineering'=>'-','pic_marketing'=>$data['prepared_by']??$request->user()->name,'status'=>DocumentRevision::STATUS_A00_ISSUED,'a00'=>'ada','a00_received_date'=>$data['document_date'],'partlist_original_name'=>'','partlist_file_path'=>'','umh_original_name'=>'','umh_file_path'=>'','notes'=>$data['notes']??null,'change_remark'=>'A00 New Project Declaration diterbitkan.']);
                $created[]=['project'=>$project,'revision'=>$revision,'item'=>$item];
            }
            $first=$created[0]; $items=$data['items']; unset($data['items'],$data['business_category_id'],$data['customer_id'],$data['source_file']);
            $form=ProjectA00Form::create($data+$signaturePaths+['document_project_id'=>$first['project']->id,'document_revision_id'=>$first['revision']->id,'customer'=>$customer->name,'model'=>$first['item']['model'],'assy_number'=>$first['item']['assy_number'],'assy_name'=>$first['item']['assy_name'],'quantity'=>$first['item']['quantity']??null,'quantity_uom'=>$first['item']['quantity_uom'],'quantity_basis'=>$first['item']['quantity_basis'],'product_life_years'=>$first['item']['product_life_years']??null,'spot_order'=>!empty($first['item']['spot_order']),'source_file_name'=>$stored['name'],'source_file_path'=>$stored['path'],'status'=>'issued','issued_at'=>now(),'created_by'=>$request->user()->id]);
            foreach($created as $index=>$entry) {
                ProjectA00Item::create(['project_a00_form_id'=>$form->id,'document_project_id'=>$entry['project']->id,'document_revision_id'=>$entry['revision']->id,'line_number'=>$index+1]+$entry['item']);
                ProjectWorkflowTask::create([
                    'document_project_id'=>$entry['project']->id,
                    'document_revision_id'=>$entry['revision']->id,
                    'stage'=>ProjectWorkflowTask::STAGE_DRAWING,
                    'assigned_role'=>'document_control',
                    'status'=>ProjectWorkflowTask::STATUS_PENDING,
                    'available_at'=>now(),
                    'notes'=>'A00 '.$form->document_number.' telah diterbitkan. Menunggu registrasi dan distribusi drawing.',
                    'metadata'=>['a00_form_id'=>$form->id,'a00_number'=>$form->document_number],
                ]);
            }
            return $form;
        });
        if ($request->boolean('embedded')) {
            return response()->view('control-project.a00.created', ['form' => $form]);
        }
        return redirect()->route('control-project.a00.show',$form)->with('success','A00 diterbitkan dan Project V0 berhasil dibuat.');
    }

    public function show(ProjectA00Form $a00){$a00->load('project','projectRevision','items.project');return view('control-project.a00.show',compact('a00'));}

    private function storeSignature(?string $dataUrl, string $type): ?string
    {
        if (!$dataUrl) return null;
        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) abort(422, 'Format tanda tangan tidak valid.');
        $binary=base64_decode($matches[1], true);
        if ($binary===false || strlen($binary)>100000) abort(422, 'Ukuran tanda tangan terlalu besar.');
        $path='control-project/a00-signatures/'.now()->format('Y/m').'/'.Str::uuid().'-'.$type.'.png';
        Storage::disk('public')->put($path,$binary);
        return $path;
    }

}
