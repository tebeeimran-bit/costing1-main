<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentControlRegistration;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\Pic;
use App\Models\ProjectWorkflowTask;
use App\Models\ProjectDocumentRevision;
use App\Models\ProjectA00Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\BusinessCategoryContext;

class BreakdownInboxController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $filter = (string) $request->query('status', 'active');
        if (! in_array($filter, ['active', 'history', 'all'], true)) {
            $filter = 'active';
        }

        $tasks = ProjectWorkflowTask::with([
            'project.product',
            'project.a00Item.form' => fn ($query) => $query->withCount('items'),
            'revision',
            'assignedUser',
        ])
            ->where('stage', ProjectWorkflowTask::STAGE_BREAKDOWN)
            ->where('assigned_role', 'admin_costing')
            ->when($filter === 'active', fn ($query) => $query->whereIn('status', [
                ProjectWorkflowTask::STATUS_PENDING,
                ProjectWorkflowTask::STATUS_IN_PROGRESS,
            ]))
            ->when($filter === 'history', fn ($query) => $query->where('status', ProjectWorkflowTask::STATUS_COMPLETED))
            ->when($search !== '', fn ($query) => $query->whereHas('project', fn ($project) => $project
                ->where('customer', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('part_number', 'like', "%{$search}%")
                ->orWhere('part_name', 'like', "%{$search}%")))
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM project_a00_items pai WHERE pai.document_project_id = project_workflow_tasks.document_project_id) THEN 0 ELSE 1 END')
            ->orderByRaw('(SELECT pai.project_a00_form_id FROM project_a00_items pai WHERE pai.document_project_id = project_workflow_tasks.document_project_id LIMIT 1) DESC')
            ->orderByRaw('(SELECT pai.line_number FROM project_a00_items pai WHERE pai.document_project_id = project_workflow_tasks.document_project_id LIMIT 1)')
            ->latest('available_at')
            ->latest('id');
        BusinessCategoryContext::apply($tasks);
        $tasks=$tasks->paginate(20)->withQueryString();

        $customers=Customer::orderBy('name')->get();
        $categories=BusinessCategory::orderBy('code')->orderBy('name')->get();
        $picsEngineering=Pic::where('type','engineering')->orderBy('name')->get();
        $picsMarketing=Pic::where('type','marketing')->orderBy('name')->get();
        $activeBusinessCategory=BusinessCategoryContext::selected();
        $categoryByCode=$categories->keyBy(fn($category)=>mb_strtolower(trim((string)$category->code)));
        $customerByName=$customers->keyBy(fn($customer)=>mb_strtolower(trim((string)$customer->name)));
        $sourcePayload=function(string $source, DocumentProject $project, ?DocumentRevision $revision)use($categoryByCode,$customerByName){
            $category=$categoryByCode->get(mb_strtolower(trim((string)$project->product?->code)));
            $customer=$customerByName->get(mb_strtolower(trim((string)$project->customer)));
            return [
                'source'=>$source,'project_id'=>$project->id,
                'label'=>trim($project->part_number.' — '.$project->part_name),
                'business_category_id'=>$category?->id,'customer_id'=>$customer?->id,
                'model'=>$project->model,'assy_name'=>$project->part_name,'assy_number'=>$project->part_number,
                'received_date'=>$revision?->received_date?->format('Y-m-d'),
                'pic_engineering'=>$revision?->pic_engineering,'pic_marketing'=>$revision?->pic_marketing,
            ];
        };
        $documentControlRows=DocumentControlRegistration::with('revision')
            ->whereNotNull('document_project_id')->latest('id')->get()->unique('document_project_id');
        $documentControlProjects=DocumentProject::with('product')->whereIn('id',$documentControlRows->pluck('document_project_id'));
        BusinessCategoryContext::applyToProjects($documentControlProjects);
        $documentControlProjects=$documentControlProjects->get()->keyBy('id');
        $documentControlSources=$documentControlRows
            ->map(fn($registration)=>$documentControlProjects->has($registration->document_project_id)
                ? $sourcePayload('document_control',$documentControlProjects->get($registration->document_project_id),$registration->revision)
                : null)->filter()->values();
        $a00Items=ProjectA00Item::with(['projectRevision','form'])
            ->whereNotNull('document_project_id')->latest('id')->get()->unique('document_project_id');
        $a00Projects=DocumentProject::with('product')->whereIn('id',$a00Items->pluck('document_project_id'));
        BusinessCategoryContext::applyToProjects($a00Projects);
        $a00Projects=$a00Projects->get()->keyBy('id');
        $controlProjectSources=$a00Items
            ->map(fn($item)=>$a00Projects->has($item->document_project_id)
                ? $sourcePayload('control_project',$a00Projects->get($item->document_project_id),$item->projectRevision)
                : null)->filter()->values();
        $projectSources=DocumentProject::with(['product','revisions'=>fn($query)=>$query->latest('version_number')])
            ->whereDoesntHave('a00Form')->whereDoesntHave('a00Item');
        BusinessCategoryContext::applyToProjects($projectSources);
        $projectSources=$projectSources->get()
            ->map(fn($project)=>$sourcePayload('project',$project,$project->revisions->first()))->values();
        $breakdownSources=$projectSources->concat($documentControlSources)->concat($controlProjectSources)->values();
        return view('breakdown.inbox', compact('tasks', 'search', 'filter', 'customers', 'categories', 'picsEngineering', 'picsMarketing', 'activeBusinessCategory', 'breakdownSources'));
    }

    public function storeManual(Request $request)
    {
        $data=$request->validateWithBag('manualBreakdown',[
            'business_category_id'=>['required','exists:business_categories,id'],
            'customer_id'=>['required','exists:customers,id'],
            'model'=>['required','string','max:255'],'assy_name'=>['required','string','max:255'],
            'assy_number'=>['required','string','max:255'],'received_date'=>['required','date'],
            'pic_engineering'=>['required','string','max:255'],'pic_marketing'=>['nullable','string','max:255'],
            'notes'=>['nullable','string','max:1000'],
            'source_project_id'=>['nullable','integer','exists:document_projects,id'],
        ]);
        $category=BusinessCategory::findOrFail($data['business_category_id']);
        abort_if(BusinessCategoryContext::selectedId() && BusinessCategoryContext::selectedId() !== $category->id,422,'Business Category form harus sama dengan kategori aktif.');
        $customer=Customer::findOrFail($data['customer_id']);

        DB::transaction(function() use($data,$category,$customer,$request){
            if (!empty($data['source_project_id'])) {
                $project=DocumentProject::lockForUpdate()->findOrFail($data['source_project_id']);
                $revision=$project->revisions()->latest('version_number')->lockForUpdate()->firstOrFail();
                ProjectWorkflowTask::firstOrCreate([
                    'document_revision_id'=>$revision->id,'stage'=>ProjectWorkflowTask::STAGE_BREAKDOWN,
                ],[
                    'document_project_id'=>$project->id,'assigned_role'=>'admin_costing',
                    'status'=>ProjectWorkflowTask::STATUS_PENDING,'available_at'=>now(),
                    'metadata'=>['source'=>'project','without_a00'=>$revision->a00!=='ada'],
                ]);
                return;
            }
            $product=Product::firstOrCreate(
                ['code'=>$category->code?:strtoupper(Str::slug($category->name,'-'))],
                ['name'=>$category->name,'line'=>'']
            );
            $projectKey=hash('sha256',mb_strtolower(implode('|',[
                trim($customer->name),trim($data['model']),trim($data['assy_number']),trim($data['assy_name']),
            ])));
            $project=DocumentProject::firstOrCreate(['project_key'=>$projectKey],[
                'product_id'=>$product->id,'customer'=>$customer->name,'model'=>$data['model'],
                'part_number'=>$data['assy_number'],'part_name'=>$data['assy_name'],
            ]);
            $version=((int)$project->revisions()->max('version_number'))+1;
            $revision=DocumentRevision::create([
                'document_project_id'=>$project->id,'version_number'=>$version,'received_date'=>$data['received_date'],
                'pic_engineering'=>$data['pic_engineering'],'pic_marketing'=>$data['pic_marketing']??null,
                'status'=>DocumentRevision::STATUS_PENDING_FORM_INPUT,'a00'=>'tidak ada',
                'partlist_original_name'=>'','partlist_file_path'=>'','umh_original_name'=>'','umh_file_path'=>'',
                'notes'=>$data['notes']??null,'change_remark'=>'Breakdown dibuat manual tanpa proses A00 dan distribusi drawing.',
            ]);
            ProjectWorkflowTask::create([
                'document_project_id'=>$project->id,'document_revision_id'=>$revision->id,
                'stage'=>ProjectWorkflowTask::STAGE_BREAKDOWN,'assigned_role'=>'admin_costing',
                'status'=>ProjectWorkflowTask::STATUS_PENDING,'available_at'=>now(),
                'metadata'=>['source'=>'manual_breakdown','without_a00'=>true],
            ]);
        });

        return redirect()->route('breakdown.inbox')->with('success','Breakdown manual berhasil dibuat dan sudah masuk ke halaman Project.');
    }

    public function destroyTask(ProjectWorkflowTask $task)
    {
        abort_unless($task->stage === ProjectWorkflowTask::STAGE_BREAKDOWN && $task->assigned_role === 'admin_costing', 404);
        $partNumber=$task->project?->part_number ?: 'Project';
        $task->delete();

        return redirect()->route('breakdown.inbox')->with('success', $partNumber.' berhasil dihapus dari Inbox Breakdown. Data di Document Control dan Control Project tetap tersimpan.');
    }

    public function complete(Request $request, ProjectWorkflowTask $task)
    {
        abort_unless($task->stage === ProjectWorkflowTask::STAGE_BREAKDOWN && $task->assigned_role === 'admin_costing', 404);
        abort_if($task->status === ProjectWorkflowTask::STATUS_COMPLETED, 422, 'Task Breakdown sudah selesai.');

        $validated = $request->validate([
            'partlist_file' => ['nullable', 'required_without:umh_file', 'file', 'mimes:xls,xlsx,pdf', 'max:20480'],
            'umh_file' => ['nullable', 'required_without:partlist_file', 'file', 'mimes:xls,xlsx,pdf', 'max:20480'],
        ], [
            'partlist_file.required_without' => 'Pilih minimal satu file Partlist atau UMH.',
            'umh_file.required_without' => 'Pilih minimal satu file Partlist atau UMH.',
            'partlist_file.mimes' => 'Partlist harus berupa Excel atau PDF.',
            'umh_file.mimes' => 'UMH harus berupa Excel atau PDF.',
        ]);

        $partlist = $validated['partlist_file'] ?? null;
        $umh = $validated['umh_file'] ?? null;
        $directory = 'workflow/breakdown/'.$task->document_revision_id;
        $partlistPath = $partlist?->store($directory.'/partlist');
        $umhPath = $umh?->store($directory.'/umh');

        DB::transaction(function () use ($request, $task, $partlist, $umh, $partlistPath, $umhPath) {
            $revisionUpdate = [];
            if ($partlist) {
                $revisionUpdate += [
                    'partlist_original_name' => $partlist->getClientOriginalName(),
                    'partlist_file_path' => $partlistPath,
                    'partlist_update_count' => ((int) $task->revision->partlist_update_count) + 1,
                    'partlist_updated_at' => now(),
                ];
            }
            if ($umh) {
                $revisionUpdate += [
                    'umh_original_name' => $umh->getClientOriginalName(),
                    'umh_file_path' => $umhPath,
                    'umh_update_count' => ((int) $task->revision->umh_update_count) + 1,
                    'umh_updated_at' => now(),
                ];
            }
            $task->revision->update($revisionUpdate);
            $task->revision->refresh();
            $isComplete = filled($task->revision->partlist_file_path) && filled($task->revision->umh_file_path);

            $task->update([
                'status' => $isComplete ? ProjectWorkflowTask::STATUS_COMPLETED : ProjectWorkflowTask::STATUS_IN_PROGRESS,
                'assigned_user_id' => $task->assigned_user_id ?: $request->user()->id,
                'started_at' => $task->started_at ?: now(),
                'completed_by_id' => $isComplete ? $request->user()->id : null,
                'completed_at' => $isComplete ? now() : null,
                'metadata' => array_merge($task->metadata ?? [], [
                    'partlist_name' => $task->revision->partlist_original_name,
                    'umh_name' => $task->revision->umh_original_name,
                ]),
            ]);

            if (filled($task->revision->partlist_file_path)) {
                ProjectWorkflowTask::firstOrCreate([
                    'document_revision_id' => $task->document_revision_id,
                    'stage' => ProjectWorkflowTask::STAGE_COSTING,
                ], [
                    'document_project_id' => $task->document_project_id,
                    'assigned_role' => 'admin_costing',
                    'status' => ProjectWorkflowTask::STATUS_PENDING,
                    'available_at' => now(),
                    'metadata' => ['source' => 'breakdown_upload', 'breakdown_task_id' => $task->id],
                ]);
            }
        });

        $task->revision->refresh();
        if (filled($task->revision->partlist_file_path) && filled($task->revision->umh_file_path)) {
            return redirect()->route('breakdown.inbox')->with('success', 'Partlist dan UMH sudah lengkap. Breakdown selesai dan task Costing telah dibuat.');
        }

        $waitingFor = filled($task->revision->partlist_file_path) ? 'UMH' : 'Partlist';
        $message = filled($task->revision->partlist_file_path)
            ? 'Partlist berhasil disimpan. Form Costing sudah dapat diproses sementara Breakdown menunggu UMH.'
            : 'Dokumen berhasil disimpan. Breakdown tetap terbuka dan menunggu '.$waitingFor.'.';
        return redirect()->route('breakdown.inbox')->with('success', $message);
    }

    public function uploadRevision(Request $request, ProjectWorkflowTask $task)
    {
        abort_unless($task->stage===ProjectWorkflowTask::STAGE_BREAKDOWN&&$task->assigned_role==='admin_costing',404);
        $data=$request->validate([
            'revision_type'=>['required','in:design,partlist,drawing,umh'],
            'revision_file'=>['required','file','mimes:xls,xlsx','max:20480'],
            'description'=>['nullable','string','max:1000'],
        ],[
            'revision_type.required'=>'Pilih jenis revisi dokumen.',
            'revision_file.required'=>'Pilih file Excel revisi.',
            'revision_file.mimes'=>'Dokumen revisi harus berupa file Excel (.xls atau .xlsx).',
        ]);

        $file=$data['revision_file'];
        $path=$file->store('workflow/revisions/'.$task->document_revision_id.'/'.$data['revision_type']);

        DB::transaction(function()use($request,$task,$data,$file,$path){
            ProjectDocumentRevision::create([
                'document_project_id'=>$task->document_project_id,
                'document_revision_id'=>$task->document_revision_id,
                'workflow_task_id'=>$task->id,
                'revision_type'=>$data['revision_type'],
                'original_name'=>$file->getClientOriginalName(),
                'file_path'=>$path,
                'description'=>$data['description']??null,
                'uploaded_by'=>$request->user()->id,
            ]);

            if($data['revision_type']==='partlist'){
                $task->revision->update([
                    'partlist_original_name'=>$file->getClientOriginalName(),
                    'partlist_file_path'=>$path,
                    'partlist_update_count'=>((int)$task->revision->partlist_update_count)+1,
                    'partlist_updated_at'=>now(),
                ]);
            }elseif($data['revision_type']==='umh'){
                $task->revision->update([
                    'umh_original_name'=>$file->getClientOriginalName(),
                    'umh_file_path'=>$path,
                    'umh_update_count'=>((int)$task->revision->umh_update_count)+1,
                    'umh_updated_at'=>now(),
                ]);
            }
        });

        return back()->with('success','Dokumen '.$this->revisionTypeLabel($data['revision_type']).' berhasil diunggah.');
    }

    private function revisionTypeLabel(string $type): string
    {
        return match($type){
            'design'=>'Revisi Design','partlist'=>'Revisi Partlist',
            'drawing'=>'Revisi Drawing','umh'=>'Revisi UMH',
        };
    }

    public function startCosting(Request $request, ProjectWorkflowTask $task)
    {
        abort_unless($task->stage === ProjectWorkflowTask::STAGE_BREAKDOWN && $task->assigned_role === 'admin_costing', 404);
        abort_unless(filled($task->revision?->partlist_file_path), 422, 'Partlist harus tersedia sebelum Costing dimulai.');

        $costingTask = ProjectWorkflowTask::firstOrCreate([
            'document_revision_id' => $task->document_revision_id,
            'stage' => ProjectWorkflowTask::STAGE_COSTING,
        ], [
            'document_project_id' => $task->document_project_id,
            'assigned_role' => 'admin_costing',
            'status' => ProjectWorkflowTask::STATUS_PENDING,
            'available_at' => now(),
            'metadata' => ['source' => 'partlist_available', 'breakdown_task_id' => $task->id],
        ]);

        if ($costingTask->status === ProjectWorkflowTask::STATUS_PENDING) {
            $costingTask->update([
                'status' => ProjectWorkflowTask::STATUS_IN_PROGRESS,
                'assigned_user_id' => $request->user()->id,
                'started_at' => now(),
            ]);
        }

        return redirect()->route('form', ['tracking_revision_id' => $task->document_revision_id]);
    }
}
