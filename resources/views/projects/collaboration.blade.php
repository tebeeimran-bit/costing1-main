@extends('layouts.app')

@section('title', 'Project Collaboration')
@section('page-title', 'Project Collaboration')

@section('breadcrumb')
    <a href="{{ route('project', absolute: false) }}">Project</a><span class="breadcrumb-separator">/</span><span>Activity &amp; Comments</span>
@endsection

@section('content')
@php
    $project = $revision->project;
    $partNumber = $costing?->assy_no ?: $project?->part_number ?: '-';
    $partName = $costing?->assy_name ?: $project?->part_name ?: 'Project Costing';
    $customer = $costing?->customer?->name ?: $project?->customer ?: '-';
@endphp
<div class="collaboration-page">
    @if(session('success'))<div class="collab-alert">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="collab-alert is-error">{{ $errors->first() }}</div>@endif

    <section class="collab-hero">
        <div><span>PROJECT WORKSPACE</span><h2>{{ $partNumber }} — {{ $partName }}</h2><p>{{ $customer }} · {{ $project?->model ?: $costing?->model ?: '-' }} · {{ $revision->version_label }}</p></div>
        <div class="collab-hero-actions"><a href="{{ route('project', ['search' => $partNumber], false) }}">Back to Project</a>@if($costing)<a class="primary" href="{{ route('form', ['tracking_revision_id' => $revision->id], false) }}">Open Costing</a>@endif</div>
    </section>

    <section class="collab-kpis">
        <div><span>Workflow</span><strong>{{ $workflow['progress'] }}%</strong><small>{{ $workflow['completed_count'] }}/{{ $workflow['total_count'] }} stages complete</small></div>
        <div><span>Current Status</span><strong>{{ $revision->status_label }}</strong><small>{{ $workflow['next_action']['label'] }}</small></div>
        <div class="{{ $deadline['is_overdue'] ? 'danger' : '' }}"><span>{{ $deadline['is_custom'] ? 'Deadline' : 'Default SLA' }}</span><strong>{{ $deadline['due_at']->format('d M Y') }}</strong><small>{{ $deadline['label'] }}</small></div>
        <div><span>Aging</span><strong>{{ $deadline['aging_days'] }} day(s)</strong><small>SLA target: {{ $deadline['sla_days'] }} day(s)</small></div>
    </section>

    @if($canManageDeadline)
        <section class="deadline-panel">
            <div><h3>Deadline &amp; SLA</h3><p>Set a custom deadline or leave it blank to use the default SLA for the current workflow stage.</p></div>
            <form method="POST" action="{{ route('project-collaboration.deadline', $revision, false) }}">@csrf @method('PATCH')<input type="date" name="due_at" value="{{ $revision->taskSetting?->due_at?->format('Y-m-d') }}"><button type="submit">Update Deadline</button></form>
        </section>
    @endif

    <div class="collab-grid">
        <section class="collab-card activity-panel">
            <div class="collab-card-head"><div><span>AUDIT TRAIL</span><h3>Activity Timeline</h3></div><b>{{ $revision->activities->count() }}</b></div>
            <div class="activity-list">
                @forelse($revision->activities as $activity)
                    <article class="activity-item type-{{ $activity->event_type }}">
                        <i></i><div><div class="activity-title"><strong>{{ $activity->title }}</strong><time>{{ $activity->occurred_at?->diffForHumans() }}</time></div>@if($activity->description)<p>{{ $activity->description }}</p>@endif<small>{{ $activity->user?->name ?: 'System' }} · {{ $activity->occurred_at?->format('d M Y, H:i') }}</small></div>
                    </article>
                @empty
                    <div class="collab-empty">No activity has been recorded for this revision.</div>
                @endforelse
            </div>
        </section>

        <section class="collab-card comment-panel">
            <div class="collab-card-head"><div><span>TEAM DISCUSSION</span><h3>Comments &amp; Mentions</h3></div><b>{{ $revision->comments->count() }}</b></div>
            <form class="comment-form" method="POST" action="{{ route('project-collaboration.comments.store', $revision, false) }}">
                @csrf
                <textarea name="body" rows="4" maxlength="3000" required placeholder="Write a comment. Mention a teammate with @handle...">{{ old('body') }}</textarea>
                <div><small>Examples: {{ $mentionUsers->take(3)->map(fn($user) => '@'.$user->handle)->implode(', ') }}</small><button type="submit">Post Comment</button></div>
            </form>
            <details class="mention-directory"><summary>Available mention handles</summary><div>@foreach($mentionUsers as $mentionUser)<span><b>@{{ $mentionUser->handle }}</b><small>{{ $mentionUser->name }} · {{ $mentionUser->role }}</small></span>@endforeach</div></details>
            <div class="comment-list">
                @forelse($revision->comments as $comment)
                    <article class="comment-item">
                        <div class="comment-avatar">{{ strtoupper(substr($comment->user?->name ?: 'U', 0, 1)) }}</div>
                        <div><header><strong>{{ $comment->user?->name ?: 'Unknown user' }}</strong><time>{{ $comment->created_at->diffForHumans() }}</time></header><p>{!! nl2br(e($comment->body)) !!}</p>@if(count($comment->mentioned_user_ids ?? []))<small class="mention-count">{{ count($comment->mentioned_user_ids) }} teammate(s) mentioned</small>@endif</div>
                        @if(auth()->id() === $comment->user_id || auth()->user()?->role === 'admin')<form method="POST" action="{{ route('project-collaboration.comments.destroy', [$revision, $comment], false) }}" onsubmit="return confirm('Remove this comment?')">@csrf @method('DELETE')<button type="submit" title="Remove comment">×</button></form>@endif
                    </article>
                @empty
                    <div class="collab-empty">No comments yet. Start the discussion above.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>

<style>
    .collaboration-page{max-width:1400px;margin:0 auto;color:#173451}.collab-alert{margin-bottom:12px;padding:11px 14px;border:1px solid #a7e2c0;border-radius:10px;background:#edfff4;color:#14733d;font-size:12px;font-weight:700}.collab-alert.is-error{border-color:#fecaca;background:#fff1f2;color:#b42318}.collab-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:23px 26px;border-radius:15px;background:linear-gradient(120deg,#073b82,#0874e9);color:#fff}.collab-hero>div>span,.collab-card-head span{font-size:9px;font-weight:900;letter-spacing:.12em}.collab-hero h2{margin:5px 0;font-size:22px}.collab-hero p{margin:0;color:#dbeafe;font-size:12px}.collab-hero-actions{display:flex;gap:8px}.collab-hero-actions a{padding:9px 12px;border:1px solid rgba(255,255,255,.35);border-radius:8px;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.collab-hero-actions a.primary{border-color:#fff;background:#fff;color:#0968cf}.collab-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:14px 0}.collab-kpis>div{padding:14px 16px;border:1px solid #dbe5ef;border-radius:12px;background:#fff}.collab-kpis span,.collab-kpis strong,.collab-kpis small{display:block}.collab-kpis span{color:#6c7f94;font-size:10px}.collab-kpis strong{margin:5px 0;color:#173b62;font-size:15px}.collab-kpis small{color:#788a9d;font-size:10px}.collab-kpis .danger{border-color:#fecaca;background:#fff5f5}.collab-kpis .danger strong,.collab-kpis .danger small{color:#b42318}.deadline-panel{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;padding:13px 16px;border:1px solid #cfe0f1;border-radius:12px;background:#f2f8ff}.deadline-panel h3{margin:0 0 3px;font-size:13px}.deadline-panel p{margin:0;color:#6b7e93;font-size:10px}.deadline-panel form{display:flex;gap:7px}.deadline-panel input{padding:8px 10px;border:1px solid #c8d7e6;border-radius:8px}.deadline-panel button,.comment-form button{border:0;border-radius:8px;background:#0870df;color:#fff;font-size:11px;font-weight:800;cursor:pointer;padding:9px 12px}.collab-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:14px}.collab-card{min-width:0;border:1px solid #d9e4ef;border-radius:14px;background:#fff;overflow:hidden}.collab-card-head{display:flex;align-items:center;justify-content:space-between;padding:15px 17px;border-bottom:1px solid #e2e9f1}.collab-card-head span{color:#0870df}.collab-card-head h3{margin:3px 0 0;font-size:15px}.collab-card-head>b{display:grid;place-items:center;min-width:25px;height:25px;border-radius:20px;background:#eaf3fd;color:#1265bd;font-size:10px}.activity-list,.comment-list{max-height:620px;padding:15px 17px;overflow:auto}.activity-item{position:relative;display:grid;grid-template-columns:16px 1fr;gap:10px;padding-bottom:19px}.activity-item:not(:last-child):before{content:"";position:absolute;top:15px;bottom:0;left:5px;width:1px;background:#d9e5f0}.activity-item>i{z-index:1;width:11px;height:11px;margin-top:3px;border:3px solid #dbeafe;border-radius:50%;background:#1674dc}.activity-title{display:flex;justify-content:space-between;gap:10px}.activity-title strong{font-size:12px}.activity-title time,.activity-item small{color:#8493a4;font-size:9px}.activity-item p{margin:4px 0;color:#60748a;font-size:11px}.comment-form{padding:14px 17px;border-bottom:1px solid #e3eaf2}.comment-form textarea{box-sizing:border-box;width:100%;resize:vertical;padding:10px;border:1px solid #cad8e6;border-radius:9px;outline:0;font:inherit;font-size:11px}.comment-form textarea:focus{border-color:#2580e6;box-shadow:0 0 0 3px #e2f0ff}.comment-form>div{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:7px}.comment-form small{color:#77899e;font-size:9px}.mention-directory{margin:10px 17px;border:1px solid #dce6ef;border-radius:8px}.mention-directory summary{padding:8px 10px;cursor:pointer;color:#47637f;font-size:10px;font-weight:800}.mention-directory>div{display:grid;grid-template-columns:repeat(2,1fr);gap:5px;padding:0 9px 9px}.mention-directory span{padding:6px;border-radius:6px;background:#f2f6fa}.mention-directory b,.mention-directory small{display:block;font-size:9px}.mention-directory small{color:#7b8da0}.comment-item{position:relative;display:grid;grid-template-columns:30px 1fr auto;gap:9px;padding:12px 0;border-bottom:1px solid #e8eef4}.comment-avatar{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#e5f1ff;color:#1266bd;font-size:10px;font-weight:900}.comment-item header{display:flex;justify-content:space-between;gap:8px}.comment-item header strong{font-size:11px}.comment-item time{color:#8998a8;font-size:9px}.comment-item p{margin:5px 0;color:#526b84;font-size:11px;line-height:1.55}.comment-item form button{border:0;background:transparent;color:#9aa8b7;cursor:pointer;font-size:17px}.mention-count{color:#0870df;font-size:9px}.collab-empty{padding:35px 15px;color:#8090a2;font-size:11px;text-align:center}@media(max-width:950px){.collab-grid{grid-template-columns:1fr}.collab-kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.collab-hero,.deadline-panel{align-items:stretch;flex-direction:column}.collab-hero-actions,.deadline-panel form{display:grid}.collab-kpis{grid-template-columns:1fr}.mention-directory>div{grid-template-columns:1fr}}
</style>
@endsection
