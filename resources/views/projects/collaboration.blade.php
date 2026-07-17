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
        <div class="completeness-{{ $completeness['level'] }}"><span>Kelengkapan Data</span><strong>{{ $completeness['score'] }}%</strong><small>{{ count($completeness['missing']) }} item perlu dilengkapi</small></div>
    </section>

    @if($completeness['missing'])
        <section class="collab-missing"><div><h3>Data yang masih perlu dilengkapi</h3><p>Selesaikan item berikut untuk mencapai kelengkapan 100%.</p></div><div>@foreach($completeness['missing'] as $missing)<a href="{{ $missing['url'] }}"><b>{{ $missing['label'] }} <span>+{{ $missing['weight'] }}%</span></b><small>{{ $missing['description'] }}</small></a>@endforeach</div></section>
    @endif

    <section class="revision-compare-card">
        <div class="revision-compare-head"><div><span>APPROVAL CHECK</span><h3>Perbandingan dengan Revisi Sebelumnya</h3><p>Periksa perubahan biaya sebelum melakukan approval.</p></div>
        @if($revisionComparison['available'])<a href="{{ route('compare.costing',['compare_a_id'=>$costing->id,'compare_b_id'=>$previousCosting->id],false) }}">Buka Perbandingan Lengkap</a>@endif</div>
        @if($revisionComparison['available'])
            <div class="revision-compare-summary"><div><span>Revisi Saat Ini</span><b>{{ $revision->version_label }}</b><small>Rp {{ number_format($revisionComparison['current_total'],0,',','.') }}</small></div><div><span>Revisi Sebelumnya</span><b>{{ $previousCosting->trackingRevision?->version_label ?: '-' }}</b><small>Rp {{ number_format($revisionComparison['previous_total'],0,',','.') }}</small></div><div class="{{ $revisionComparison['total_delta']>0?'increase':'decrease' }}"><span>Perubahan COGM</span><b>{{ $revisionComparison['total_delta']>=0?'+':'' }}Rp {{ number_format($revisionComparison['total_delta'],0,',','.') }}</b><small>{{ $revisionComparison['total_delta_percent']===null?'-':number_format($revisionComparison['total_delta_percent'],2,',','.') . '%' }}</small></div><div><span>Material Berubah</span><b>{{ $revisionComparison['material_changes'] }}</b><small>part ditambah, dihapus, atau berubah</small></div></div>
            <div class="revision-component-list">@foreach($revisionComparison['components'] as $component)<div><span>{{ $component['label'] }}</span><b class="{{ $component['delta']>0?'increase':($component['delta']<0?'decrease':'') }}">{{ $component['delta']>=0?'+':'' }}Rp {{ number_format($component['delta'],0,',','.') }}</b></div>@endforeach</div>
        @else<div class="revision-no-baseline">Belum ada costing pada revisi sebelumnya yang dapat dijadikan pembanding.</div>@endif
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
    .collaboration-page{max-width:1400px;margin:0 auto;color:#173451}.collab-alert{margin-bottom:12px;padding:11px 14px;border:1px solid #a7e2c0;border-radius:10px;background:#edfff4;color:#14733d;font-size:12px;font-weight:700}.collab-alert.is-error{border-color:#fecaca;background:#fff1f2;color:#b42318}.collab-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:23px 26px;border-radius:15px;background:linear-gradient(120deg,#073b82,#0874e9);color:#fff}.collab-hero>div>span,.collab-card-head span{font-size:9px;font-weight:900;letter-spacing:.12em}.collab-hero h2{margin:5px 0;font-size:22px}.collab-hero p{margin:0;color:#dbeafe;font-size:12px}.collab-hero-actions{display:flex;gap:8px}.collab-hero-actions a{padding:9px 12px;border:1px solid rgba(255,255,255,.35);border-radius:8px;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.collab-hero-actions a.primary{border-color:#fff;background:#fff;color:#0968cf}.collab-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin:14px 0}.collab-kpis>div{padding:14px 16px;border:1px solid #dbe5ef;border-radius:12px;background:#fff}.collab-kpis span,.collab-kpis strong,.collab-kpis small{display:block}.collab-kpis span{color:#6c7f94;font-size:10px}.collab-kpis strong{margin:5px 0;color:#173b62;font-size:15px}.collab-kpis small{color:#788a9d;font-size:10px}.collab-kpis .danger,.collab-kpis .completeness-danger{border-color:#fecaca;background:#fff5f5}.collab-kpis .danger strong,.collab-kpis .danger small,.collab-kpis .completeness-danger strong{color:#b42318}.collab-missing{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;margin-bottom:14px;padding:14px 16px;border:1px solid #f5d5a3;border-radius:12px;background:#fffaf0}.collab-missing h3,.collab-missing p{margin:0}.collab-missing h3{font-size:12px}.collab-missing p{margin-top:3px;color:#836d52;font-size:9px}.collab-missing>div:last-child{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:6px}.collab-missing a{padding:7px 9px;border-radius:7px;background:#fff;color:#5b4b37;text-decoration:none}.collab-missing a b,.collab-missing a small{display:block;font-size:9px}.collab-missing a b span{color:#d97706}.collab-missing a small{margin-top:2px;color:#8b7b68}.revision-compare-card{margin-bottom:14px;padding:15px 17px;border:1px solid #d8e4ef;border-radius:12px;background:#fff}.revision-compare-head{display:flex;justify-content:space-between;gap:15px}.revision-compare-head span{color:#7c3aed;font-size:8px;font-weight:900;letter-spacing:.12em}.revision-compare-head h3,.revision-compare-head p{margin:0}.revision-compare-head h3{margin-top:3px;font-size:13px}.revision-compare-head p{margin-top:3px;color:#75879a;font-size:9px}.revision-compare-head a{align-self:center;padding:8px 10px;border-radius:7px;background:#f0eaff;color:#6d28d9;font-size:9px;font-weight:800;text-decoration:none}.revision-compare-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px}.revision-compare-summary>div{padding:10px;border-radius:8px;background:#f6f9fc}.revision-compare-summary span,.revision-compare-summary b,.revision-compare-summary small{display:block}.revision-compare-summary span{color:#718398;font-size:8px}.revision-compare-summary b{margin:4px 0;font-size:11px}.revision-compare-summary small{color:#718398;font-size:8px}.revision-compare-card .increase{color:#c2410c}.revision-compare-card .decrease{color:#16834a}.revision-component-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.revision-component-list div{display:flex;gap:10px;padding:6px 8px;border-radius:6px;background:#fafbfc;font-size:8px}.revision-no-baseline{margin-top:12px;padding:15px;border:1px dashed #cbd8e5;border-radius:8px;color:#77899c;font-size:10px;text-align:center}.deadline-panel{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;padding:13px 16px;border:1px solid #cfe0f1;border-radius:12px;background:#f2f8ff}.deadline-panel h3{margin:0 0 3px;font-size:13px}.deadline-panel p{margin:0;color:#6b7e93;font-size:10px}.deadline-panel form{display:flex;gap:7px}.deadline-panel input{padding:8px 10px;border:1px solid #c8d7e6;border-radius:8px}.deadline-panel button,.comment-form button{border:0;border-radius:8px;background:#0870df;color:#fff;font-size:11px;font-weight:800;cursor:pointer;padding:9px 12px}.collab-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:14px}.collab-card{min-width:0;border:1px solid #d9e4ef;border-radius:14px;background:#fff;overflow:hidden}.collab-card-head{display:flex;align-items:center;justify-content:space-between;padding:15px 17px;border-bottom:1px solid #e2e9f1}.collab-card-head span{color:#0870df}.collab-card-head h3{margin:3px 0 0;font-size:15px}.collab-card-head>b{display:grid;place-items:center;min-width:25px;height:25px;border-radius:20px;background:#eaf3fd;color:#1265bd;font-size:10px}.activity-list,.comment-list{max-height:620px;padding:15px 17px;overflow:auto}.activity-item{position:relative;display:grid;grid-template-columns:16px 1fr;gap:10px;padding-bottom:19px}.activity-item:not(:last-child):before{content:"";position:absolute;top:15px;bottom:0;left:5px;width:1px;background:#d9e5f0}.activity-item>i{z-index:1;width:11px;height:11px;margin-top:3px;border:3px solid #dbeafe;border-radius:50%;background:#1674dc}.activity-title{display:flex;justify-content:space-between;gap:10px}.activity-title strong{font-size:12px}.activity-title time,.activity-item small{color:#8493a4;font-size:9px}.activity-item p{margin:4px 0;color:#60748a;font-size:11px}.comment-form{padding:14px 17px;border-bottom:1px solid #e3eaf2}.comment-form textarea{box-sizing:border-box;width:100%;resize:vertical;padding:10px;border:1px solid #cad8e6;border-radius:9px;outline:0;font:inherit;font-size:11px}.comment-form textarea:focus{border-color:#2580e6;box-shadow:0 0 0 3px #e2f0ff}.comment-form>div{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:7px}.comment-form small{color:#77899e;font-size:9px}.mention-directory{margin:10px 17px;border:1px solid #dce6ef;border-radius:8px}.mention-directory summary{padding:8px 10px;cursor:pointer;color:#47637f;font-size:10px;font-weight:800}.mention-directory>div{display:grid;grid-template-columns:repeat(2,1fr);gap:5px;padding:0 9px 9px}.mention-directory span{padding:6px;border-radius:6px;background:#f2f6fa}.mention-directory b,.mention-directory small{display:block;font-size:9px}.mention-directory small{color:#7b8da0}.comment-item{position:relative;display:grid;grid-template-columns:30px 1fr auto;gap:9px;padding:12px 0;border-bottom:1px solid #e8eef4}.comment-avatar{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#e5f1ff;color:#1266bd;font-size:10px;font-weight:900}.comment-item header{display:flex;justify-content:space-between;gap:8px}.comment-item header strong{font-size:11px}.comment-item time{color:#8998a8;font-size:9px}.comment-item p{margin:5px 0;color:#526b84;font-size:11px;line-height:1.55}.comment-item form button{border:0;background:transparent;color:#9aa8b7;cursor:pointer;font-size:17px}.mention-count{color:#0870df;font-size:9px}.collab-empty{padding:35px 15px;color:#8090a2;font-size:11px;text-align:center}@media(max-width:950px){.collab-grid{grid-template-columns:1fr}.collab-kpis{grid-template-columns:repeat(2,1fr)}.collab-missing{flex-direction:column}.collab-missing>div:last-child{justify-content:flex-start}.revision-compare-summary{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.collab-hero,.deadline-panel,.revision-compare-head{align-items:stretch;flex-direction:column}.collab-hero-actions,.deadline-panel form{display:grid}.collab-kpis,.revision-compare-summary{grid-template-columns:1fr}.mention-directory>div{grid-template-columns:1fr}}
</style>
@endsection
