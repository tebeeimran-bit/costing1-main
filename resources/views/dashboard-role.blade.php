@extends('layouts.app')

@section('title', $profile['title'])
@section('page-title', $profile['title'])
@section('breadcrumb')<span>Dashboard</span>@endsection

@section('content')
<style>
.rd-wrap{display:grid;gap:1rem}.rd-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.2rem;border:1px solid #dbe5f2;border-radius:14px;background:linear-gradient(135deg,#fff,#f4f8ff);box-shadow:0 12px 28px rgba(15,23,42,.05)}.rd-hero h2{margin:0;color:#0f172a;font-size:1.05rem}.rd-hero p{margin:.25rem 0 0;color:#64748b;font-size:.72rem}.rd-action{display:inline-flex;align-items:center;justify-content:center;padding:.65rem .9rem;border-radius:9px;background:#2563eb;color:#fff;font-size:.7rem;font-weight:850;text-decoration:none;white-space:nowrap}.rd-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem}.rd-metric{position:relative;min-height:115px;padding:1rem;border:1px solid #dbe5f2;border-radius:13px;background:#fff;overflow:hidden}.rd-metric:after{content:"";position:absolute;width:75px;height:75px;right:-20px;bottom:-25px;border-radius:50%;background:currentColor;opacity:.08}.rd-metric.blue{color:#2563eb}.rd-metric.indigo{color:#4f46e5}.rd-metric.orange{color:#d97706}.rd-metric.green{color:#059669}.rd-metric.red{color:#dc2626}.rd-metric-label{color:#64748b;font-size:.64rem;font-weight:850;text-transform:uppercase;letter-spacing:.035em}.rd-metric-value{margin:.45rem 0 .25rem;color:#0f172a;font-size:1.45rem;font-weight:950;line-height:1}.rd-metric-note{color:#64748b;font-size:.63rem}.rd-panel{border:1px solid #dbe5f2;border-radius:14px;background:#fff;overflow:hidden}.rd-panel-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1rem;border-bottom:1px solid #e2e8f0}.rd-panel-head h3{margin:0;color:#0f172a;font-size:.86rem}.rd-panel-head span{color:#64748b;font-size:.65rem}.rd-table-wrap{overflow:auto}.rd-table{width:100%;min-width:900px;border-collapse:collapse}.rd-table th{padding:.65rem .75rem;background:#f8fafc;color:#64748b;font-size:.57rem;text-align:left;text-transform:uppercase}.rd-table td{padding:.72rem .75rem;border-top:1px solid #e2e8f0;color:#334155;font-size:.68rem;vertical-align:middle}.rd-project{display:grid;gap:.1rem}.rd-project strong{color:#0f172a}.rd-project small{color:#64748b;font-size:.58rem}.rd-status{display:inline-flex;padding:.25rem .48rem;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:.58rem;font-weight:850}.rd-status.done{background:#dcfce7;color:#15803d}.rd-open{display:inline-flex;padding:.4rem .6rem;border:1px solid #bfdbfe;border-radius:7px;background:#eff6ff;color:#1d4ed8;font-size:.6rem;font-weight:850;text-decoration:none}.rd-empty{text-align:center!important;padding:2rem!important;color:#64748b!important}@media(max-width:1050px){.rd-metrics{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.rd-hero{align-items:stretch;flex-direction:column}.rd-metrics{grid-template-columns:1fr}}
</style>

<div class="rd-wrap">
    <section class="rd-hero">
        <div><h2>Selamat datang, {{ auth()->user()->name }}</h2><p>{{ $profile['subtitle'] }}</p></div>
        <a class="rd-action" href="{{ route($profile['route'], absolute:false) }}">{{ $profile['action'] }}</a>
    </section>

    <section class="rd-metrics">
        @foreach($metrics as $metric)
            <article class="rd-metric {{ $metric['tone'] }}">
                <div class="rd-metric-label">{{ $metric['label'] }}</div>
                <div class="rd-metric-value">@if(!empty($metric['currency']))Rp {{ number_format((float)$metric['value'],0,',','.') }}@else{{ number_format((float)$metric['value'],0,',','.') }}@endif</div>
                <div class="rd-metric-note">{{ $metric['note'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="rd-panel">
        <div class="rd-panel-head"><h3>Project Terbaru</h3><span>{{ $recentProjects->count() }} project ditampilkan</span></div>
        <div class="rd-table-wrap"><table class="rd-table"><thead><tr><th>Project</th><th>Customer</th><th>Model</th><th>No. Assy</th><th>PIC Engineering</th><th>PIC Marketing</th><th>Status</th><th>Update</th><th>Aksi</th></tr></thead><tbody>
        @forelse($recentProjects as $revision)
            @php($project=$revision->project)
            <tr>
                <td><div class="rd-project"><strong>{{ $project?->part_name ?: '-' }}</strong><small>{{ $project?->product?->name ?: '-' }}</small></div></td>
                <td>{{ $project?->customer ?: '-' }}</td><td>{{ $project?->model ?: '-' }}</td><td><strong>{{ $project?->part_number ?: '-' }}</strong></td>
                <td>{{ $revision->pic_engineering ?: '-' }}</td><td>{{ $revision->pic_marketing ?: '-' }}</td>
                <td><span class="rd-status {{ $revision->status===\App\Models\DocumentRevision::STATUS_SUBMITTED_TO_MARKETING?'done':'' }}">{{ $revision->status_label }}</span></td>
                <td>{{ optional($revision->latestSubmission?->last_updated_at ?? $revision->updated_at)->format('d/m/Y H:i') }}</td>
                <td><a class="rd-open" href="{{ route('project', ['search'=>$project?->part_number], false) }}">Lihat Project</a></td>
            </tr>
        @empty<tr><td colspan="9" class="rd-empty">Belum ada project yang sesuai dengan akun ini.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
