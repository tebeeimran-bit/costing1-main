<?php

namespace App\Http\Controllers;

use App\Models\ProjectWorkflowTask;
use App\Models\DocumentControlRegistration;
use Illuminate\Http\Request;

class DocumentControlInboxController extends Controller
{
    public function index(Request $request)
    {
        $search=trim((string)$request->query('q'));
        $query=ProjectWorkflowTask::with(['project.product','revision'])
            ->where('stage',ProjectWorkflowTask::STAGE_DRAWING)
            ->where('assigned_role','document_control')
            ->whereIn('status',[ProjectWorkflowTask::STATUS_PENDING,ProjectWorkflowTask::STATUS_IN_PROGRESS])
            ->oldest('available_at')->oldest('id');
        if($search!=='') $query->whereHas('project',fn($q)=>$q->where('customer','like',"%{$search}%")->orWhere('model','like',"%{$search}%")->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%"));
        $registrations=DocumentControlRegistration::query()
            ->whereNotNull('document_project_id')
            ->when($search!=='',function($query) use($search){$query->where(function($q) use($search){$q->where('registration_no','like',"%{$search}%")->orWhere('customer','like',"%{$search}%")->orWhere('project','like',"%{$search}%")->orWhere('part_number','like',"%{$search}%")->orWhere('part_name','like',"%{$search}%");});})
            ->orderByDesc('registration_date')->orderByDesc('id')
            ->paginate(15,['*'],'registrations_page')->withQueryString();
        return view('document-control.inbox',[
            'tasks'=>$query->paginate(20,['*'],'tasks_page')->withQueryString(),
            'registrations'=>$registrations,'search'=>$search,
        ]);
    }
}
