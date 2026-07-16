@extends('layouts.app')

@section('title', 'My Tasks')
@section('page-title', 'My Tasks')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span><span>My Tasks</span>
@endsection

@section('content')
<div class="task-page">
    <section class="task-hero">
        <div><span class="task-eyebrow">PUSAT PEKERJAAN</span><h2>Yang perlu Anda kerjakan sekarang</h2><p>Tugas disusun otomatis berdasarkan role, status workflow, dan kendala tiap project.</p></div>
        <div class="task-total"><strong>{{ $filteredTasks->count() }}</strong><span>Tugas ditampilkan</span></div>
    </section>

    @php $labels = ['all' => 'Semua', 'documents' => 'Dokumen', 'pricing' => 'Harga Part', 'costing' => 'Costing', 'approval' => 'Approval', 'marketing' => 'Marketing']; @endphp
    <nav class="task-filters" aria-label="Filter tugas">
        @foreach($labels as $key => $label)
            @php $count = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
            <a href="{{ route('my-tasks', $key === 'all' ? [] : ['category' => $key], false) }}" class="{{ $category === $key ? 'active' : '' }}">{{ $label }} <span>{{ $count }}</span></a>
        @endforeach
    </nav>

    <div class="task-list">
        @forelse($filteredTasks as $task)
            <article class="task-card priority-{{ $task->priority }}">
                <div class="task-priority"><span></span>{{ $task->priority === 'high' ? 'Perlu perhatian' : ($task->priority === 'medium' ? 'Segera ditinjau' : 'Normal') }}</div>
                <div class="task-main">
                    <div class="task-category">{{ $labels[$task->category] ?? ucfirst($task->category) }}</div>
                    <h3>{{ $task->title }}</h3>
                    <p>{{ $task->description }}</p>
                    <div class="task-meta">
                        <span><b>{{ $task->part_number }}</b> · {{ $task->project }}</span>
                        <span>{{ $task->customer }}</span><span>{{ $task->model }}</span><span>{{ $task->revision }}</span>
                    </div>
                </div>
                <div class="task-side">
                    <div class="task-progress"><div><span>Progress</span><b>{{ $task->progress }}%</b></div><div class="task-progress-track"><i style="width: {{ $task->progress }}%"></i></div></div>
                    <span class="task-status">{{ $task->status }}</span>
                    <div class="task-deadline {{ $task->deadline['is_overdue'] ? 'is-overdue' : '' }}">
                        <span>{{ $task->deadline['is_custom'] ? 'Deadline' : 'SLA deadline' }}</span>
                        <b>{{ $task->deadline['due_at']->format('d M Y') }}</b>
                        <small>{{ $task->deadline['label'] }} · Aging {{ $task->deadline['aging_days'] }} day(s)</small>
                    </div>
                    <a class="task-action" href="{{ $task->url }}">Buka Tugas <span>→</span></a>
                    <a class="task-collaboration" href="{{ $task->collaboration_url }}">Activity &amp; Comments</a>
                </div>
            </article>
        @empty
            <div class="task-empty"><div>✓</div><h3>Tidak ada tugas pada kategori ini</h3><p>Semua pekerjaan yang menjadi tanggung jawab role Anda sudah tertangani.</p></div>
        @endforelse
    </div>
</div>

<style>
    .task-page{max-width:1380px;margin:0 auto}.task-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:26px 30px;border-radius:16px;background:linear-gradient(125deg,#073b82,#0969e8);color:#fff;box-shadow:0 14px 30px rgba(3,75,160,.18)}.task-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;color:#bfdbfe}.task-hero h2{margin:5px 0;font-size:25px}.task-hero p{margin:0;color:#dbeafe}.task-total{min-width:130px;padding:14px 18px;border:1px solid rgba(255,255,255,.25);border-radius:12px;background:rgba(255,255,255,.1);text-align:center}.task-total strong,.task-total span{display:block}.task-total strong{font-size:28px}.task-total span{font-size:11px;color:#dbeafe}.task-filters{display:flex;gap:8px;overflow:auto;margin:18px 0;padding-bottom:3px}.task-filters a{display:flex;align-items:center;gap:8px;white-space:nowrap;padding:9px 13px;border:1px solid #d5e1ef;border-radius:10px;background:#fff;color:#49627e;font-size:13px;font-weight:700;text-decoration:none}.task-filters a span{display:grid;place-items:center;min-width:21px;height:21px;border-radius:20px;background:#edf3fa;font-size:11px}.task-filters a.active{border-color:#0b69e3;background:#eaf3ff;color:#075ac5}.task-list{display:grid;gap:12px}.task-card{position:relative;display:grid;grid-template-columns:135px minmax(0,1fr) 280px;gap:20px;align-items:center;padding:18px 20px;border:1px solid #dbe5f0;border-left:4px solid #60a5fa;border-radius:14px;background:#fff;box-shadow:0 5px 16px rgba(15,50,85,.05)}.task-card.priority-high{border-left-color:#ef4444}.task-card.priority-medium{border-left-color:#f59e0b}.task-priority{align-self:start;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase}.task-priority span{display:inline-block;width:8px;height:8px;margin-right:6px;border-radius:50%;background:#60a5fa}.priority-high .task-priority span{background:#ef4444}.priority-medium .task-priority span{background:#f59e0b}.task-category{margin-bottom:4px;color:#0b69e3;font-size:11px;font-weight:800;text-transform:uppercase}.task-main h3{margin:0 0 5px;color:#102a4c;font-size:17px}.task-main p{margin:0;color:#61738a;font-size:13px}.task-meta{display:flex;flex-wrap:wrap;gap:8px 14px;margin-top:12px;color:#667b93;font-size:11px}.task-meta span+span{padding-left:14px;border-left:1px solid #d7e1ed}.task-side{display:grid;gap:8px}.task-progress>div:first-child{display:flex;justify-content:space-between;color:#52677e;font-size:11px}.task-progress-track{height:6px;margin-top:5px;border-radius:10px;background:#e6eef7;overflow:hidden}.task-progress-track i{display:block;height:100%;border-radius:inherit;background:#0b77ec}.task-status{color:#5a6f87;font-size:11px}.task-deadline{display:grid;grid-template-columns:1fr auto;gap:2px 8px;padding:7px 9px;border-radius:8px;background:#f1f6fb;color:#5a6f87;font-size:10px}.task-deadline b{color:#213f61}.task-deadline small{grid-column:1/-1}.task-deadline.is-overdue{background:#fff0f0;color:#b42318}.task-deadline.is-overdue b{color:#b42318}.task-action{display:flex;justify-content:space-between;padding:9px 12px;border-radius:9px;background:#0869e2;color:#fff;font-size:12px;font-weight:800;text-decoration:none}.task-collaboration{text-align:center;color:#1667bd;font-size:11px;font-weight:800;text-decoration:none}.task-empty{padding:60px 20px;border:1px dashed #bfd0e2;border-radius:15px;background:#fff;text-align:center;color:#657991}.task-empty div{display:grid;place-items:center;width:45px;height:45px;margin:auto;border-radius:50%;background:#dcfce7;color:#15803d;font-size:22px}.task-empty h3{margin:12px 0 4px;color:#233c5b}.task-empty p{margin:0}@media(max-width:900px){.task-card{grid-template-columns:1fr}.task-priority{order:-1}.task-side{grid-template-columns:1fr}.task-hero{align-items:flex-start;flex-direction:column}.task-total{width:100%}.task-meta span+span{padding-left:0;border:0}}
</style>
@endsection
