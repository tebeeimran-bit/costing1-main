<?php

namespace App\Http\Controllers;

use App\Models\CostingGroup;
use App\Models\CostingGroupItem;
use App\Models\CostingApproval;
use App\Models\DocumentRevision;
use App\Models\DocumentProject;
use App\Models\ProjectA00Item;
use App\Models\ProjectWorkflowTask;
use App\Models\Pic;
use App\Services\Costing\BulkyCogmSnapshotService;
use App\Services\Costing\CostingGroupService;
use App\Services\Costing\CostingGroupNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CostingGroupController extends Controller
{
    public function workspace(Request $request, CostingGroup $group)
    {
        $this->authorizeRoles($request,['admin','admin_costing','coordinator_costing']);
        app(CostingGroupService::class)->syncFromA00($group->a00Form,$request->user()->id);
        $a00=$group->a00Form()->with(['project','projectRevision','items.project','costingGroup.items.a00Item','costingGroup.items.project','costingGroup.items.costingData','costingGroup.items.revision'])->firstOrFail();
        return view('control-project.a00.show',['a00'=>$a00,'picsEngineering'=>Pic::where('type','engineering')->orderBy('name')->get(),'picsMarketing'=>Pic::where('type','marketing')->orderBy('name')->get()]);
    }
    public function draft(Request $request, CostingGroup $group, BulkyCogmSnapshotService $snapshots)
    {
        $this->authorizeRoles($request, ['admin','admin_costing','coordinator_costing']);
        app(CostingGroupService::class)->syncFromA00($group->a00Form, $request->user()->id);
        $version = $snapshots->create($group->fresh(), 'draft', $request->user()->id);
        app(CostingGroupNotificationService::class)->notify($group->fresh(), 'draft_generated', "Snapshot draft versi {$version->version_number} dibuat.");
        return back()->with('success', "Snapshot draft Bulky COGM versi {$version->version_number} berhasil dibuat.");
    }

    public function updateItemPics(Request $request, CostingGroupItem $item)
    {
        $this->authorizeRoles($request, ['admin','admin_costing','admin_control_project']);
        $data = $request->validate([
            'pic_engineering' => ['nullable','string','max:255'],
            'pic_marketing' => ['nullable','string','max:255'],
        ]);
        $oldNames=[$item->effectivePicEngineering(),$item->effectivePicMarketing()];
        $item->update($data);
        $item->group->events()->create([
            'costing_group_item_id'=>$item->id,'event_type'=>'item_pic_updated',
            'actor_id'=>$request->user()->id,'metadata'=>['pic_engineering'=>$item->effectivePicEngineering(),'pic_marketing'=>$item->effectivePicMarketing()],
        ]);
        app(CostingGroupNotificationService::class)->notify($item->group, 'item_pic_updated', 'PIC item '.($item->a00Item?->assy_number ?: $item->id).' diperbarui.', $oldNames);
        return back()->with('success', 'PIC item berhasil diperbarui. Kosong berarti mengikuti PIC A00.');
    }

    public function submitApproval(Request $request, CostingGroup $group)
    {
        $this->authorizeRoles($request,['admin','admin_costing']);
        app(CostingGroupService::class)->syncFromA00($group->a00Form,$request->user()->id);
        $group->refresh()->load(['activeItems.revision','activeItems.costingData']); $submitted=0; $skipped=[];
        DB::transaction(function() use($group,$request,&$submitted,&$skipped){
            foreach($group->activeItems as $item){
                if(!$item->costingData){$skipped[]=$item->id.': costing belum ada';continue;}
                if($item->revision->unpricedParts()->whereNull('resolved_at')->exists()){$skipped[]=$item->id.': harga belum lengkap';continue;}
                if(in_array($item->revision->status,[DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL,DocumentRevision::STATUS_APPROVED_BY_COORDINATOR,DocumentRevision::STATUS_SUBMITTED_TO_MARKETING],true)) continue;
                CostingApproval::create(['document_revision_id'=>$item->revision->id,'costing_data_id'=>$item->costingData->id,'status'=>CostingApproval::STATUS_WAITING,'cogm_value'=>$item->costingData->total_cost,'submitted_by_id'=>$request->user()->id,'submitted_at'=>now(),'submit_notes'=>'Diajukan melalui Bulky COGM.']);
                $item->revision->update(['status'=>DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL]); $submitted++;
            }
        });
        app(CostingGroupService::class)->refreshStatus($group->fresh());
        app(CostingGroupNotificationService::class)->notify($group->fresh(),'group_submitted_approval',"{$submitted} item diajukan ke Coordinator.");
        return back()->with($submitted?'success':'warning',$submitted." item diajukan. ".($skipped?'Belum diproses: '.implode(', ',$skipped):''));
    }

    public function approve(Request $request, CostingGroup $group)
    {
        $this->authorizeRoles($request,['admin','coordinator_costing']); $approved=0;
        $group->load(['activeItems.revision']);
        DB::transaction(function() use($group,$request,&$approved){foreach($group->activeItems as $item){
            if($item->revision->status!==DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL) continue;
            $approval=CostingApproval::where('document_revision_id',$item->revision->id)->latest('id')->first(); if(!$approval) continue;
            $approval->update(['status'=>CostingApproval::STATUS_APPROVED,'approved_by_id'=>$request->user()->id,'approved_at'=>now(),'approval_notes'=>'Disetujui melalui Bulky COGM.']);
            $item->revision->update(['status'=>DocumentRevision::STATUS_APPROVED_BY_COORDINATOR]); $approved++;
        }});
        app(CostingGroupService::class)->refreshStatus($group->fresh());
        app(CostingGroupNotificationService::class)->notify($group->fresh(),'group_approved',"{$approved} item disetujui Coordinator.");
        return back()->with($approved?'success':'warning',"{$approved} item berhasil disetujui.");
    }

    public function addItem(Request $request, CostingGroup $group)
    {
        $this->authorizeRoles($request,['admin','admin_control_project']);
        $data=$request->validate([
            'model'=>['required','string','max:255'],'assy_name'=>['required','string','max:255'],
            'assy_number'=>['required','string','max:255'],'quantity'=>['nullable','integer','min:1'],
            'quantity_uom'=>['required','string','max:20'],'quantity_basis'=>['required','string','max:30'],
            'product_life_years'=>['nullable','integer','min:0','max:99'],'spot_order'=>['nullable','boolean'],
            'pic_engineering'=>['nullable','string','max:255'],'pic_marketing'=>['nullable','string','max:255'],
            'reason'=>[$group->last_submitted_version_id?'required':'nullable','string','max:1000'],
        ]);
        $createdItem=DB::transaction(function() use($group,$data,$request){
            $group->load(['a00Form.project.product','a00Form.items','a00Form.projectRevision']); $form=$group->a00Form;
            $key=hash('sha256',mb_strtolower(implode('|',[trim($form->customer),trim($data['model']),trim($data['assy_number']),trim($data['assy_name'])])));
            abort_if(DocumentProject::where('project_key',$key)->exists(),422,'Project dengan identitas item tersebut sudah ada.');
            $project=DocumentProject::create(['product_id'=>$form->project?->product_id,'customer'=>$form->customer,'model'=>$data['model'],'part_number'=>$data['assy_number'],'part_name'=>$data['assy_name'],'project_key'=>$key]);
            $base=$form->projectRevision;
            $revision=DocumentRevision::create(['document_project_id'=>$project->id,'version_number'=>1,'received_date'=>$form->document_date,'plant_id'=>$base?->plant_id,'period'=>$base?->period,'pic_engineering'=>($data['pic_engineering']??null)?:$group->pic_engineering,'pic_marketing'=>($data['pic_marketing']??null)?:$group->pic_marketing,'status'=>DocumentRevision::STATUS_A00_ISSUED,'a00'=>'ada','a00_received_date'=>$form->document_date,'partlist_original_name'=>'','partlist_file_path'=>'','umh_original_name'=>'','umh_file_path'=>'','notes'=>$data['reason']??null,'change_remark'=>'Item ditambahkan ke A00 '.$form->document_number.'.']);
            $sequence=(int)$form->items()->max('line_number')+1;
            $a00Item=ProjectA00Item::create(['project_a00_form_id'=>$form->id,'document_project_id'=>$project->id,'document_revision_id'=>$revision->id,'line_number'=>$sequence,'model'=>$data['model'],'assy_number'=>$data['assy_number'],'assy_name'=>$data['assy_name'],'quantity'=>$data['quantity']??null,'quantity_uom'=>$data['quantity_uom'],'quantity_basis'=>$data['quantity_basis'],'product_life_years'=>$data['product_life_years']??null,'spot_order'=>!empty($data['spot_order'])]);
            ProjectWorkflowTask::create(['document_project_id'=>$project->id,'document_revision_id'=>$revision->id,'stage'=>ProjectWorkflowTask::STAGE_DRAWING,'assigned_role'=>'document_control','status'=>ProjectWorkflowTask::STATUS_PENDING,'available_at'=>now(),'notes'=>'Item baru A00 '.$form->document_number.'. Menunggu drawing.','metadata'=>['a00_form_id'=>$form->id,'a00_number'=>$form->document_number,'added_after_submission'=>(bool)$group->last_submitted_version_id]]);
            $form->unsetRelation('items');
            $synced=app(CostingGroupService::class)->syncFromA00($form,$request->user()->id);
            $groupItem=$synced->items()->where('project_a00_item_id',$a00Item->id)->firstOrFail();
            $groupItem->update(['pic_engineering'=>$data['pic_engineering']??null,'pic_marketing'=>$data['pic_marketing']??null,'added_after_submission'=>(bool)$group->last_submitted_version_id,'change_reason'=>$data['reason']??null,'status'=>$group->last_submitted_version_id?'added_after_submission':'pending']);
            $synced->update(['mode'=>CostingGroup::MODE_BULKY,'status'=>$group->last_submitted_version_id?CostingGroup::STATUS_UNDER_REVISION:$synced->status]);
            $synced->events()->create(['costing_group_item_id'=>$groupItem->id,'event_type'=>'item_added','actor_id'=>$request->user()->id,'reason'=>$data['reason']??null,'metadata'=>['assy_number'=>$data['assy_number'],'after_submission'=>(bool)$group->last_submitted_version_id]]);
            return $groupItem;
        });
        app(CostingGroupNotificationService::class)->notify($createdItem->group,'item_added','Item '.$data['assy_number'].' ditambahkan ke A00.',[$createdItem->effectivePicEngineering(),$createdItem->effectivePicMarketing()]);
        return back()->with('success','Item baru berhasil ditambahkan dan workflow Drawing telah dibuat.');
    }

    public function removeItem(Request $request, CostingGroupItem $item)
    {
        $this->authorizeRoles($request,['admin','admin_control_project']);
        $data=$request->validate(['reason'=>['required','string','max:1000']]); $group=$item->group;
        abort_if($group->activeItems()->count()<=1,422,'Item terakhir dalam Costing Group tidak boleh dikeluarkan.');
        DB::transaction(function() use($item,$group,$data,$request){
            $item->update(['status'=>'removed','removed_at'=>now(),'removed_by_id'=>$request->user()->id,'removal_reason'=>$data['reason']]);
            $group->update(['status'=>$group->last_submitted_version_id?CostingGroup::STATUS_UNDER_REVISION:CostingGroup::STATUS_IN_PROGRESS,'updated_by_id'=>$request->user()->id]);
            $group->events()->create(['costing_group_item_id'=>$item->id,'event_type'=>'item_removed','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'metadata'=>['assy_number'=>$item->a00Item?->assy_number]]);
        });
        app(CostingGroupNotificationService::class)->notify($group,'item_removed','Item '.($item->a00Item?->assy_number?:$item->id).' dikeluarkan dari versi berikutnya.');
        return back()->with('success','Item dikeluarkan dari versi berikutnya. Histori lama tetap tersimpan.');
    }

    public function uploadFinal(Request $request, CostingGroup $group, BulkyCogmSnapshotService $snapshots)
    {
        $this->authorizeRoles($request,['admin','coordinator_costing']);
        $data=$request->validate(['final_file'=>['required','file','mimes:xlsx,xls','max:20480'],'change_summary'=>['nullable','string','max:2000']]);
        $file=$data['final_file']; $path=$file->store('bulky-cogm/final');
        try {
            $version=$snapshots->create($group,'final',$request->user()->id);
            $version->update(['file_path'=>$path,'original_name'=>$file->getClientOriginalName(),'file_checksum'=>hash_file('sha256',Storage::path($path)),'change_summary'=>$data['change_summary']??null]);
            $group->versions()->where('type','final')->where('status','generated')->whereKeyNot($version->id)->update(['status'=>'superseded']);
        } catch (\Throwable $exception) {
            Storage::delete($path);
            throw $exception;
        }
        $group->events()->create(['costing_group_version_id'=>$version->id,'event_type'=>'final_file_attached','actor_id'=>$request->user()->id,'metadata'=>['version'=>$version->version_number,'file'=>$version->original_name]]);
        return back()->with('success',"File final versi {$version->version_number} tersimpan dan siap dikirim.");
    }

    public function submitFinal(Request $request, CostingGroup $group)
    {
        $this->authorizeRoles($request,['admin','coordinator_costing']);
        $version=DB::transaction(function() use($group,$request){
            $lockedGroup=CostingGroup::whereKey($group->id)->lockForUpdate()->firstOrFail();
            $version=$lockedGroup->versions()->where('type','final')->where('status','generated')->latest('version_number')->lockForUpdate()->with('items')->first();
            abort_unless($version && $version->file_path && Storage::exists($version->file_path),422,'File final Bulky COGM belum tersedia atau sudah pernah dikirim.');
            abort_unless(hash_equals((string)$version->file_checksum,(string)hash_file('sha256',Storage::path($version->file_path))),422,'Integritas file final tidak valid. Upload ulang file final.');
            $lockedGroup->load(['activeItems.revision','activeItems.costingData']);
            abort_if($lockedGroup->activeItems->count()!==$version->items->count(),422,'Komposisi item berubah setelah file final dibuat. Buat file final baru.');
            foreach($lockedGroup->activeItems as $item){
                $snapshot=$version->items->firstWhere('costing_group_item_id',$item->id); $costing=$item->costingData;
                abort_if(!$snapshot || $snapshot->document_revision_id!==$item->active_document_revision_id || $snapshot->costing_data_id!==$item->costing_data_id,422,'Revision atau costing item berubah setelah file final dibuat.');
                abort_if($item->revision->unpricedParts()->whereNull('resolved_at')->exists(),422,'Masih ada harga item yang belum lengkap.');
                $currentCosts=[(float)$costing->material_cost,(float)$costing->labor_cost,(float)$costing->overhead_cost,(float)$costing->scrap_cost,(float)$costing->total_cost];
                $snapshotCosts=[(float)$snapshot->material_cost,(float)$snapshot->labor_cost,(float)$snapshot->overhead_cost,(float)$snapshot->scrap_cost,(float)$snapshot->unit_cogm];
                abort_if(array_map(fn($value)=>round($value,4),$currentCosts)!==array_map(fn($value)=>round($value,4),$snapshotCosts),422,'Nilai costing berubah setelah file final dibuat. Buat file final baru.');
                abort_if(round((float)$item->quantity,4)!==round((float)$snapshot->quantity,4),422,'Quantity berubah setelah file final dibuat. Buat file final baru.');
                abort_if(trim((string)$item->effectivePicMarketing())!==trim((string)$snapshot->pic_marketing),422,'PIC Marketing berubah setelah file final dibuat. Buat file final baru.');
            }
            $version->update(['status'=>'submitted','submitted_by_id'=>$request->user()->id,'submitted_at'=>now()]);
            $lockedGroup->update(['status'=>CostingGroup::STATUS_SUBMITTED,'last_submitted_version_id'=>$version->id,'updated_by_id'=>$request->user()->id]);
            $lockedGroup->activeItems()->update(['status'=>'submitted','added_after_submission'=>false,'change_reason'=>null]);
            $lockedGroup->events()->create(['costing_group_version_id'=>$version->id,'event_type'=>'final_submitted','actor_id'=>$request->user()->id,'metadata'=>['version'=>$version->version_number]]);
            return $version;
        });
        app(CostingGroupNotificationService::class)->notify($group->fresh(),'final_submitted',"Final Bulky COGM versi {$version->version_number} dikirim ke Marketing.");
        return back()->with('success','Final Bulky COGM berhasil dikirim ke Marketing.');
    }

    public function download(Request $request, \App\Models\CostingGroupVersion $version)
    {
        abort_unless($version->status==='submitted' && $version->file_path && Storage::exists($version->file_path),404);
        $this->authorizeGroupRecipient($request,$version->group);
        abort_unless(hash_equals((string)$version->file_checksum,(string)hash_file('sha256',Storage::path($version->file_path))),409,'Integritas file final tidak valid.');
        return Storage::download($version->file_path,$version->original_name ?: 'Bulky-COGM.xlsx');
    }

    private function authorizeGroupRecipient(Request $request, CostingGroup $group): void
    {
        if(in_array((string)$request->user()->role,['admin','admin_costing','coordinator_costing'],true)) return;
        abort_unless((string)$request->user()->role==='marketing',403);
        $name=mb_strtolower(trim((string)$request->user()->name));
        $allowed=collect([$group->pic_marketing])->merge($group->activeItems()->pluck('pic_marketing'))->filter()->map(fn($value)=>mb_strtolower(trim((string)$value)))->contains($name);
        abort_unless($allowed,403,'COGM ini ditujukan untuk PIC Marketing lain.');
    }

    private function authorizeRoles(Request $request, array $roles): void
    {
        abort_unless(in_array((string)$request->user()->role, $roles, true), 403);
    }
}
