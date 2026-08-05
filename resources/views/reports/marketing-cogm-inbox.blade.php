@extends('layouts.app')

@section('title', 'Marketing COGM Inbox')
@section('page-title', 'Marketing COGM Inbox')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Marketing COGM Inbox</span>
@endsection

@section('content')
<style>
    .marketing-inbox-card {
        background: #ffffff;
        border: 1px solid #dbe5f2;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.045);
        overflow: hidden;
    }

    .marketing-inbox-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .marketing-inbox-header h3 {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 900;
        color: #0f172a;
    }

    .marketing-inbox-header span {
        color: #64748b;
        font-size: 0.74rem;
        font-weight: 800;
    }

    .marketing-inbox-table-wrap {
        overflow-x: auto;
    }

    .marketing-inbox-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
    }

    .marketing-inbox-table th,
    .marketing-inbox-table td {
        padding: 0.78rem 0.9rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
        font-size: 0.78rem;
    }

    .marketing-inbox-table th {
        background: #ffffff;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .marketing-inbox-table td {
        color: #334155;
        font-weight: 700;
    }

    .marketing-inbox-table tr:last-child td {
        border-bottom: 0;
    }
    .marketing-inbox-table tbody tr[data-href]{cursor:pointer;transition:background .15s}.marketing-inbox-table tbody tr[data-href]:hover{background:#eff6ff}

    .inbox-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.42rem 0.72rem;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.68rem;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .inbox-action:hover {
        border-color: #93c5fd;
        background: #dbeafe;
        color: #1e40af;
    }

    .inbox-cogm {
        color: #047857;
        font-weight: 950;
        white-space: nowrap;
    }

    .inbox-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.28rem 0.6rem;
        background: #dcfce7;
        color: #15803d;
        font-size: 0.68rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .inbox-empty {
        padding: 2rem;
        text-align: center;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 750;
    }
</style>

<div class="marketing-inbox-card">
    <div class="marketing-inbox-header">
        <h3>COGM Approved yang Dikirim ke Marketing</h3>
        <span>{{ $submissions->total() }} submission</span>
    </div>

    <div class="marketing-inbox-table-wrap">
        <table class="marketing-inbox-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Model</th>
                    <th>PIC Marketing</th>
                    <th style="text-align:right;">COGM</th>
                    <th>Submitted By</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    @php
                        $revision = $submission->revision;
                        $project = $revision?->project;
                    @endphp
                    <tr data-href="{{ route('marketing.cogm-costing.show',$submission,absolute:false) }}" tabindex="0" aria-label="Lihat Form Costing {{ $project?->part_number }}">
                        <td>
                            <strong>{{ $project?->part_number ?? '-' }}</strong><br>
                            <span style="color:#64748b;">{{ $project?->part_name ?? '-' }}</span>
                        </td>
                        <td>{{ $project?->customer ?? '-' }}</td>
                        <td>{{ $project?->model ?? '-' }}</td>
                        <td>{{ $submission->pic_marketing ?? '-' }}</td>
                        <td style="text-align:right;"><span class="inbox-cogm">Rp {{ number_format((float) $submission->cogm_value, 0, ',', '.') }}</span></td>
                        <td>{{ $submission->submitted_by ?? '-' }}</td>
                        <td>{{ $submission->submitted_at ? $submission->submitted_at->format('d/m/Y H:i') : '-' }}</td>
                        <td><span class="inbox-pill">Submitted to Marketing</span></td>
                        <td style="text-align:right;">
                            <a class="inbox-action" href="{{ route('marketing.cogm-costing.show', $submission, absolute: false) }}">Lihat Form Costing</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9"><div class="inbox-empty">Belum ada COGM approved yang dikirim ke Marketing.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 1rem;">
    {{ $submissions->onEachSide(1)->links() }}
</div>
<script>
document.querySelectorAll('.marketing-inbox-table tbody tr[data-href]').forEach(function(row){
    const open=function(){ window.location.href=row.dataset.href; };
    row.addEventListener('click',open);
    row.addEventListener('keydown',function(event){if(event.key==='Enter'||event.key===' '){event.preventDefault();open();}});
});
</script>
@endsection
