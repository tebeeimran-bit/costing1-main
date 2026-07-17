@extends('layouts.app')

@section('title', 'My Tasks')
@section('page-title', 'My Tasks')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>My Tasks</span>
@endsection

@section('content')
<div class="task-page">
    <section class="task-hero">
        <div><span class="task-eyebrow">PUSAT PEKERJAAN</span><h2>Yang perlu Anda kerjakan sekarang</h2><p>Task otomatis dan manual tersusun rapi berdasarkan project.</p></div>
        <div class="task-total"><strong>{{ $filteredTasks->count() }}</strong><span>Task ditampilkan</span></div>
    </section>

    <details class="task-create" {{ $errors->any() ? 'open' : '' }}>
        <summary><span>+ Tambah Task Manual</span><small>Task wajib ditautkan ke project</small></summary>
        <form method="POST" action="{{ route('my-tasks.store', absolute: false) }}">
            @csrf
            <label class="wide">Project <b>*</b><select name="document_project_id" required><option value="">Pilih project...</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('document_project_id') == $project->id)>{{ $project->part_number }} — {{ $project->part_name }} ({{ $project->customer }})</option>@endforeach</select></label>
            <label class="wide">Judul task <b>*</b><input name="title" value="{{ old('title') }}" maxlength="255" required placeholder="Contoh: Konfirmasi harga material"></label>
            <label>Penanggung jawab<select name="assignee_id">@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assignee_id', auth()->id()) == $assignee->id)>{{ $assignee->name }}</option>@endforeach</select></label>
            <label>Kategori<select name="category"><option value="general">Umum</option>@foreach(['documents'=>'Dokumen','pricing'=>'Harga Part','costing'=>'Costing','approval'=>'Approval','marketing'=>'Marketing'] as $value=>$text)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $text }}</option>@endforeach</select></label>
            <label>Prioritas<select name="priority"><option value="normal">Normal</option><option value="medium" @selected(old('priority') === 'medium')>Segera</option><option value="high" @selected(old('priority') === 'high')>Tinggi</option></select></label>
            <label>Deadline<input type="date" name="due_at" value="{{ old('due_at') }}"></label>
            <label class="wide">Deskripsi<textarea name="description" rows="2" placeholder="Detail pekerjaan atau catatan...">{{ old('description') }}</textarea></label>
            @if($errors->any())<div class="task-form-errors wide">{{ $errors->first() }}</div>@endif
            <button type="submit">Simpan Task</button>
        </form>
    </details>

    <form class="task-search" method="GET" action="{{ route('my-tasks', absolute: false) }}">
        @if($category !== 'all')<input type="hidden" name="category" value="{{ $category }}">@endif
        <span>⌕</span><input type="search" name="q" value="{{ $search }}" placeholder="Cari project, part number, customer, atau task..."><button type="submit">Cari</button>
        @if($search !== '')<a href="{{ route('my-tasks', $category === 'all' ? [] : ['category'=>$category], false) }}">Reset</a>@endif
    </form>

    @php $labels = ['all'=>'Semua','general'=>'Umum','documents'=>'Dokumen','pricing'=>'Harga Part','costing'=>'Costing','approval'=>'Approval','marketing'=>'Marketing']; @endphp
    <nav class="task-filters" aria-label="Filter task">
        @foreach($labels as $key => $label)
            @php $count = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
            <a href="{{ route('my-tasks', $key === 'all' ? [] : ['category'=>$key], false) }}" class="{{ $category === $key ? 'active' : '' }}">{{ $label }} <span>{{ $count }}</span></a>
        @endforeach
    </nav>

    <div class="task-list">
        @forelse($groupedTasks as $projectTasks)
            @php $projectHead = $projectTasks->first(); @endphp
            <details class="task-project-group">
                <summary><div class="project-summary-main"><span class="project-icon" title="Logo customer">@if($projectHead->customer_logo)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($projectHead->customer_logo) }}" alt="Logo {{ $projectHead->customer }}">@else{{ strtoupper(substr($projectHead->customer, 0, 1)) }}@endif</span><div><small>PROJECT</small><h3>{{ $projectHead->part_number }} · {{ $projectHead->project }}</h3><p>{{ $projectHead->customer }} · {{ $projectHead->model }}</p></div></div><div class="project-summary-side"><b>{{ $projectTasks->count() }} task</b><i>⌄</i></div></summary>
                <div class="project-task-items">
                @foreach($projectTasks as $task)
                <article class="task-card priority-{{ $task->priority }}">
                    <div class="task-priority"><span></span>{{ $task->priority === 'high' ? 'Perlu perhatian' : ($task->priority === 'medium' ? 'Segera ditinjau' : 'Normal') }}@if($task->is_manual)<em>Manual</em>@endif</div>
                    <div class="task-main">
                        <div class="task-category">{{ $labels[$task->category] ?? ucfirst($task->category) }}</div><h3>{{ $task->title }}</h3><p>{{ $task->description }}</p>
                        <div class="task-meta"><span>{{ $task->is_manual ? 'Task manual' : $task->status }}</span><span>{{ $task->revision }}</span><span>Diperbarui {{ $task->updated_at?->diffForHumans() }}</span></div>
                    </div>
                    <div class="task-side">
                        <div class="task-progress"><div><span>Progress</span><b>{{ $task->progress }}%</b></div><div class="task-progress-track"><i style="width:{{ $task->progress }}%"></i></div></div>
                        <span class="task-status">{{ $task->status }}</span>
                        @unless($task->is_manual)<div class="task-completeness level-{{ $task->completeness['level'] }}"><span>Kelengkapan data</span><b>{{ $task->completeness['score'] }}%</b><small>{{ count($task->completeness['missing']) }} item perlu dilengkapi</small></div>@endunless
                        <div class="task-deadline {{ $task->deadline['is_overdue'] ? 'is-overdue' : '' }}"><span>{{ $task->deadline['is_custom'] ? 'Deadline' : 'SLA deadline' }}</span><b>{{ $task->deadline['due_at']?->format('d M Y') ?? 'Belum diatur' }}</b><small>{{ $task->deadline['label'] }} · Aging {{ $task->deadline['aging_days'] }} hari</small></div>
                        @if($task->is_manual)<form class="manual-task-actions" method="POST" action="{{ route('my-tasks.update', $task->manual_task_id, false) }}">@csrf @method('PATCH')<select name="progress" aria-label="Progress">@foreach([0,25,50,75,100] as $progress)<option value="{{ $progress }}" @selected($task->progress == $progress)>{{ $progress }}%</option>@endforeach</select><input type="hidden" name="status" value="open"><button>Simpan</button><button name="status" value="completed" class="complete">Selesai</button></form>@endif
                        <a class="task-action" href="{{ $task->url }}">Buka Project <span>→</span></a>
                        @if($task->collaboration_url)<a class="task-collaboration" href="{{ $task->collaboration_url }}">Activity &amp; Comments</a>@endif
                    </div>
                </article>
                @endforeach
                </div>
            </details>
        @empty
            <div class="task-empty"><div>✓</div><h3>Tidak ada task pada kategori ini</h3><p>Semua pekerjaan yang menjadi tanggung jawab Anda sudah tertangani.</p></div>
        @endforelse
    </div>
</div>

<style>
.task-page{max-width:1380px;margin:0 auto}.task-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:26px 30px;border-radius:16px;background:linear-gradient(125deg,#073b82,#0969e8);color:#fff;box-shadow:0 14px 30px rgba(3,75,160,.18)}.task-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;color:#bfdbfe}.task-hero h2{margin:5px 0;font-size:25px}.task-hero p{margin:0;color:#dbeafe}.task-total{min-width:130px;padding:14px 18px;border:1px solid #ffffff40;border-radius:12px;background:#ffffff1a;text-align:center}.task-total strong,.task-total span{display:block}.task-total strong{font-size:28px}.task-total span{font-size:11px;color:#dbeafe}.task-create{margin:16px 0;border:1px solid #bfd5ec;border-radius:13px;background:#fff;box-shadow:0 5px 16px #0f32550d}.task-create summary{display:flex;align-items:center;justify-content:space-between;padding:15px 18px;cursor:pointer;color:#075ac5;font-weight:800}.task-create summary small{color:#64748b;font-weight:500}.task-create form{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:0 18px 18px}.task-create label{display:grid;gap:5px;color:#415a75;font-size:12px;font-weight:700}.task-create .wide{grid-column:span 2}.task-create input,.task-create select,.task-create textarea{width:100%;padding:9px 10px;border:1px solid #cbd8e6;border-radius:8px;background:#fff;color:#17324f;font:inherit}.task-create button{align-self:end;padding:10px 18px;border:0;border-radius:8px;background:#0869e2;color:#fff;font-weight:800;cursor:pointer}.task-form-errors{padding:8px;border-radius:8px;background:#fff0f0;color:#b42318}.task-filters{display:flex;gap:8px;overflow:auto;margin:16px 0;padding-bottom:3px}.task-filters a{display:flex;align-items:center;gap:8px;white-space:nowrap;padding:9px 13px;border:1px solid #d5e1ef;border-radius:10px;background:#fff;color:#49627e;font-size:13px;font-weight:700;text-decoration:none}.task-filters a span{display:grid;place-items:center;min-width:21px;height:21px;border-radius:20px;background:#edf3fa;font-size:11px}.task-filters a.active{border-color:#0b69e3;background:#eaf3ff;color:#075ac5}.task-list{display:grid;gap:12px}.task-project-group{display:block;border:1px solid #d5e2ef;border-radius:13px;background:#fff;box-shadow:0 4px 14px #0f32550d;overflow:hidden}.task-project-group>summary{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:14px 17px;background:#fff;cursor:pointer;list-style:none;transition:background .18s ease}.task-project-group>summary::-webkit-details-marker{display:none}.task-project-group>summary:hover{background:#f6f9fc}.task-project-group[open]>summary{border-bottom:1px solid #dce6f0;background:#f8fbff}.project-summary-main{display:flex;align-items:center;gap:12px;min-width:0}.project-icon{display:grid;place-items:center;flex:0 0 38px;height:38px;border-radius:10px;background:#0869e2;color:#fff;font-size:15px;font-weight:900}.project-summary-main small{display:block;color:#1771cf;font-size:8px;font-weight:900;letter-spacing:.12em}.project-summary-main h3{margin:1px 0;color:#123c66;font-size:14px;line-height:1.35}.project-summary-main p{margin:0;color:#6a7f94;font-size:10px}.project-summary-side{display:flex;align-items:center;gap:13px}.project-summary-side b{padding:5px 10px;border-radius:20px;background:#eaf3ff;color:#075ac5;font-size:10px}.project-summary-side i{display:grid;place-items:center;width:28px;height:28px;border-radius:8px;background:#edf3f9;color:#315c86;font-size:17px;font-style:normal;transition:transform .18s ease}.task-project-group[open] .project-summary-side i{transform:rotate(180deg)}.project-task-items{display:grid;gap:9px;padding:10px;background:#f2f6fa}.task-card{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:32px;align-items:center;min-height:0;padding:20px 20px 20px 24px;border:1px solid #dbe5f0;border-left:4px solid #60a5fa;border-radius:11px;background:#fff;box-shadow:0 2px 8px #0f32550a}.task-card.priority-high{border-left-color:#ef4444}.task-card.priority-medium{border-left-color:#f59e0b}.task-priority{position:absolute;top:18px;left:24px;display:flex;align-items:center;gap:5px;color:#64748b;font-size:9px;font-weight:800;text-transform:uppercase}.task-priority span{display:block;width:8px;height:8px;border-radius:50%;background:#60a5fa}.priority-high .task-priority span{background:#ef4444}.priority-medium .task-priority span{background:#f59e0b}.task-priority em{margin-left:4px;padding:3px 7px;border-radius:10px;background:#e0f2fe;color:#0369a1;font-size:8px;font-style:normal}.task-main{padding-top:25px}.task-category{margin-bottom:5px;color:#0b69e3;font-size:10px;font-weight:800;text-transform:uppercase}.task-main h3{margin:0 0 5px;color:#102a4c;font-size:17px}.task-main p{max-width:720px;margin:0;color:#61738a;font-size:13px}.task-meta{display:flex;flex-wrap:wrap;gap:6px 12px;margin-top:10px;color:#667b93;font-size:10px}.task-meta span+span{padding-left:12px;border-left:1px solid #d7e1ed}.task-side{display:grid;gap:7px}.task-progress>div:first-child{display:flex;justify-content:space-between;color:#52677e;font-size:10px}.task-progress-track{height:5px;margin-top:4px;border-radius:10px;background:#e6eef7;overflow:hidden}.task-progress-track i{display:block;height:100%;border-radius:inherit;background:#0b77ec}.task-status{color:#5a6f87;font-size:10px}.task-completeness,.task-deadline{display:grid;grid-template-columns:1fr auto;gap:2px 8px;padding:7px 9px;border-radius:8px;background:#f1f6fb;color:#5a6f87;font-size:9px}.task-completeness small,.task-deadline small{grid-column:1/-1}.task-completeness.level-danger,.task-deadline.is-overdue{background:#fff0f0;color:#b42318}.manual-task-actions{display:grid;grid-template-columns:70px 1fr 1fr;gap:5px}.manual-task-actions select,.manual-task-actions button{padding:7px;border:1px solid #cbd8e6;border-radius:7px;background:#fff;color:#31506f;font-size:10px;font-weight:700}.manual-task-actions .complete{border-color:#16a34a;background:#16a34a;color:#fff}.task-action{display:flex;justify-content:space-between;padding:9px 12px;border-radius:9px;background:#0869e2;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.task-collaboration{text-align:center;color:#1667bd;font-size:10px;font-weight:800;text-decoration:none}.task-empty{padding:60px 20px;border:1px dashed #bfd0e2;border-radius:15px;background:#fff;text-align:center;color:#657991}.task-empty h3{margin:12px 0 4px;color:#233c5b}.task-empty p{margin:0}@media(max-width:900px){.task-card{grid-template-columns:1fr;gap:16px}.task-create form{grid-template-columns:1fr 1fr}.task-create .wide{grid-column:1/-1}.task-hero{align-items:flex-start;flex-direction:column}.task-total{width:100%}.task-meta span+span{padding-left:0;border:0}}@media(max-width:560px){.task-create form{grid-template-columns:1fr}.task-create .wide{grid-column:auto}.task-project-group>summary{padding:12px}.project-icon{flex-basis:34px;height:34px}.project-summary-main h3{font-size:12px}.project-summary-side{gap:5px}.project-summary-side b{display:none}.task-card{padding:18px}.task-priority{left:18px}.task-main{padding-top:27px}}
 .task-search{display:flex;align-items:center;gap:9px;margin:14px 0;padding:6px 8px 6px 13px;border:1px solid #cbdbea;border-radius:11px;background:#fff;box-shadow:0 3px 10px #0f32550a}.task-search>span{color:#3672a8;font-size:22px;line-height:1}.task-search input{flex:1;min-width:0;border:0;outline:0;color:#17324f;font:inherit;font-size:13px}.task-search button,.task-search a{padding:8px 13px;border:0;border-radius:7px;background:#0869e2;color:#fff;font-size:11px;font-weight:800;text-decoration:none}.task-search a{background:#edf3f9;color:#386080}.project-icon{cursor:help}
 .project-icon{overflow:hidden}.project-icon img{width:100%;height:100%;object-fit:contain;background:#fff}
</style>
@endsection
