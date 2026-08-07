<?php

namespace App\Http\Controllers;

use App\Models\ProjectWorkflowTask;
use App\Models\DocumentControlRegistration;
use App\Models\BusinessCategory;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentControlInboxController extends Controller
{
    public function index(Request $request)
    {
        $search=trim((string)$request->query('q'));
        $query=ProjectWorkflowTask::with(['project.product','revision'])
            ->where('stage',ProjectWorkflowTask::STAGE_DRAWING)
            ->where('assigned_role','document_control')
            ->oldest('available_at')->oldest('id');
        if($search!=='') $query->where(function($q) use($search){$q->where('metadata->a00_number','like',"%{$search}%")->orWhereHas('project',fn($project)=>$project->where('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%"));});
        $tasks=(clone $query)->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])->paginate(20,['*'],'tasks_page')->withQueryString();
        $groups=$query->get()->groupBy(fn($task)=>data_get($task->metadata,'a00_form_id')?'a00:'.data_get($task->metadata,'a00_form_id'):(data_get($task->metadata,'a00_number')?'number:'.data_get($task->metadata,'a00_number'):'task:'.$task->id))
            ->map(function($items){
                $active=$items->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])->values();
                if($active->isEmpty()) return null;
                return (object)['tasks'=>$active,'first'=>$active->first(),'total'=>$items->count(),'completed'=>$items->where('status',ProjectWorkflowTask::STATUS_COMPLETED)->count()];
            })->filter()->values();
        $page=max(1,(int)$request->query('tasks_page',1));$perPage=20;
        $taskGroups=new LengthAwarePaginator($groups->forPage($page,$perPage)->values(),$groups->count(),$perPage,$page,['path'=>$request->url(),'pageName'=>'tasks_page','query'=>$request->query()]);
        $registrations=DocumentControlRegistration::with('workflowTask')
            ->whereNotNull('document_project_id')
            ->when($search!=='',function($query) use($search){$query->where(function($q) use($search){$q->where('registration_no','like',"%{$search}%")->orWhere('customer','like',"%{$search}%")->orWhere('project','like',"%{$search}%")->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%");});})
            ->orderByDesc('registration_date')->orderByDesc('id')
            ->paginate(15,['*'],'registrations_page')->withQueryString();
        return view('document-control.inbox',[
            'taskGroups'=>$taskGroups,'tasks'=>$tasks,
            'registrations'=>$registrations,'search'=>$search,
            'customerOptions'=>Customer::query()->orderBy('name')->pluck('name'),
            'businessCategoryOptions'=>BusinessCategory::query()->orderBy('code')->orderBy('name')->get(['code','name']),
        ]);
    }
}
