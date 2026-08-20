<?php

namespace App\Http\Controllers;

use App\Models\ProjectWorkflowTask;
use App\Models\DocumentControlRegistration;
use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\DocumentProject;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\BusinessCategoryContext;

class DocumentControlInboxController extends Controller
{
    public function index(Request $request)
    {
        $requestedTab = (string) $request->query('tab');
        // Registrasi dan distribusi adalah satu proses. URL tab lama tetap
        // diarahkan ke antrean yang sama agar bookmark pengguna tidak rusak.
        $tab = $requestedTab === 'registrations' ? 'registrations' : 'pending';
        $search=trim((string)$request->query('q'));
        $query=ProjectWorkflowTask::with(['project.product','revision','drawingRegistration'])
            ->where('stage',ProjectWorkflowTask::STAGE_DRAWING)
            ->where('assigned_role','document_control')
            ->latest('available_at')->latest('id');
        BusinessCategoryContext::apply($query);
        $breakdownProjectsQuery=DocumentProject::with(['product','revisions'=>fn($q)=>$q->latest('version_number')])
            ->whereHas('workflowTasks',fn($q)=>$q->where('stage',ProjectWorkflowTask::STAGE_BREAKDOWN)
                ->whereIn('metadata->source',['manual_breakdown','new_project_draft']))
            ->whereDoesntHave('workflowTasks',fn($q)=>$q->where('stage',ProjectWorkflowTask::STAGE_DRAWING))
            ->when($search!=='',fn($q)=>$q->where(fn($project)=>$project
                ->where('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")
                ->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%")))
            ->latest('updated_at')->latest('id');
        BusinessCategoryContext::applyToProjects($breakdownProjectsQuery);
        $breakdownProjects=$breakdownProjectsQuery->paginate(20,['*'],'breakdown_page')->withQueryString();
        if($search!=='') $query->where(function($q) use($search){$q->where('metadata->a00_number','like',"%{$search}%")->orWhereHas('project',fn($project)=>$project->where('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%"));});
        $tasks=(clone $query)->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])->paginate(20,['*'],'tasks_page')->withQueryString();
        $groups=$query->get()->groupBy(fn($task)=>data_get($task->metadata,'a00_form_id')?'a00:'.data_get($task->metadata,'a00_form_id'):(data_get($task->metadata,'a00_number')?'number:'.data_get($task->metadata,'a00_number'):'task:'.$task->id))
            ->map(function($items){
                $active=$items->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])->values();
                if($active->isEmpty()) return null;
                return (object)['tasks'=>$items->values(),'first'=>$active->first(),'total'=>$items->count(),'completed'=>$items->where('status',ProjectWorkflowTask::STATUS_COMPLETED)->count()];
            })->filter()->values();
        $page=max(1,(int)$request->query('tasks_page',1));$perPage=20;
        $taskGroups=new LengthAwarePaginator($groups->forPage($page,$perPage)->values(),$groups->count(),$perPage,$page,['path'=>$request->url(),'pageName'=>'tasks_page','query'=>$request->query()]);
        $registrationsQuery=DocumentControlRegistration::with('workflowTask')
            ->whereNotNull('document_project_id')
            ->where(function ($query) {
                $query->whereNull('workflow_task_id')
                    ->orWhereHas('workflowTask', fn ($task) => $task->where('status', ProjectWorkflowTask::STATUS_COMPLETED));
            });
        BusinessCategoryContext::apply($registrationsQuery);
        $registrations=$registrationsQuery
            ->when($search!=='',function($query) use($search){$query->where(function($q) use($search){$q->where('registration_no','like',"%{$search}%")->orWhere('customer','like',"%{$search}%")->orWhere('project','like',"%{$search}%")->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%");});})
            ->orderByDesc('registration_date')->orderByDesc('id')
            ->paginate(15,['*'],'registrations_page')->withQueryString();
        return view('document-control.inbox',[
            'taskGroups'=>$taskGroups,'tasks'=>$tasks,
            'registrations'=>$registrations,'search'=>$search,
            'breakdownProjects'=>$breakdownProjects,
            'tab'=>$tab,
            'customerOptions'=>Customer::query()->orderBy('name')->pluck('name'),
            'businessCategoryOptions'=>BusinessCategory::query()->orderBy('code')->orderBy('name')->get(['code','name']),
            'activeBusinessCategory'=>BusinessCategoryContext::selected(),
        ]);
    }
}
