@extends('layouts.app')

@section('title', 'Database Tubes')
@section('page-title', 'Database Tubes')

@section('breadcrumb')
    <a href="{{ route('database', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Tubes</span>
@endsection

@section('content')
<style>
    .tube-card {
        background: #fff;
        border: 1px solid #dbe4f2;
        border-radius: 16px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .tube-filter {
        display: flex;
        gap: .75rem;
        align-items: center;
        margin-bottom: 1rem;
    }
    .tube-input {
        width: 100%;
        border: 1px solid #cfe0f5;
        border-radius: 10px;
        padding: .62rem .72rem;
        font-size: .82rem;
        font-weight: 700;
        color: #0f172a;
        outline: none;
    }
    .tube-btn {
        height: 39px;
        border: 0;
        border-radius: 10px;
        padding: 0 1rem;
        background: #2563eb;
        color: #fff;
        font-size: .78rem;
        font-weight: 900;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tube-btn.secondary {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cfe0f5;
    }
    .tube-table {
        width: 100%;
        border-collapse: collapse;
    }
    .tube-table th {
        text-align: left;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        padding: .75rem;
        white-space: nowrap;
    }
    .tube-table td {
        border-bottom: 1px solid #eef2f7;
        padding: .75rem;
        font-size: .82rem;
        color: #334155;
        vertical-align: top;
    }
    .tube-badge {
        display: inline-flex;
        padding: .25rem .55rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 850;
        background: #fef3c7;
        color: #92400e;
    }
</style>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="tube-card">
    <form method="GET" action="{{ route('database.tubes', absolute: false) }}" class="tube-filter">
        <input type="text" name="search" value="{{ $search ?? '' }}" class="tube-input" placeholder="Cari tube code, nama, spec, supplier...">
        <button class="tube-btn" type="submit">Terapkan</button>
        <a class="tube-btn secondary" href="{{ route('database.tubes', absolute: false) }}">Reset</a>
    </form>

    <div style="overflow-x:auto;">
        <table class="tube-table">
            <thead>
                <tr>
                    <th>Tube Code</th>
                    <th>Tube Name</th>
                    <th>Spec</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Supplier</th>
                    <th>Effective Date</th>
                    <th>Estimate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tubes as $tube)
                    <tr>
                        <td><strong>{{ $tube->tube_code }}</strong></td>
                        <td>{{ $tube->tube_name ?? '-' }}</td>
                        <td>{{ $tube->spec ?? '-' }}</td>
                        <td>{{ $tube->unit }}</td>
                        <td>{{ $tube->currency }} {{ number_format((float) $tube->price, 2, ',', '.') }} / {{ $tube->price_unit }}</td>
                        <td>{{ $tube->supplier ?? '-' }}</td>
                        <td>{{ optional($tube->effective_date)->format('d M Y') ?? '-' }}</td>
                        <td>
                            @if($tube->is_estimate)
                                <span class="tube-badge">Estimate</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#64748b;padding:2rem;">Belum ada data tubes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        {{ $tubes->links() }}
    </div>
</div>
@endsection
