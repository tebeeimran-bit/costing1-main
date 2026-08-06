@extends('layouts.app')

@section('title', 'Manajemen User & Hak Akses')
@section('page-title', 'Manajemen User & Hak Akses')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Dashboard</a> / Manajemen User
@endsection

@section('content')
<style>
    .permission-shell{width:100%;max-width:100%;min-width:0;margin:0 auto;display:grid;gap:1rem;overflow:hidden}
    #permissionAccessForm{display:block;width:100%;max-width:100%;min-width:0}
    .permission-card{width:100%;max-width:100%;min-width:0;border:1px solid #dbe5f1;border-radius:14px;box-shadow:0 10px 28px rgba(15,23,42,.045);overflow:hidden}
    .permission-card .card-body{max-width:100%;min-width:0;overflow:hidden}
    .permission-card .card-header{padding:1rem 1.15rem;background:linear-gradient(135deg,#fff,#f7faff);border-bottom:1px solid #e2e8f0}
    .permission-card .card-title{font-size:.92rem;color:#0f172a}
    .permission-help{display:flex;align-items:center;gap:.45rem;color:#64748b;font-size:.7rem}
    .permission-help:before{content:'i';display:grid;place-items:center;width:18px;height:18px;border-radius:50%;background:#dbeafe;color:#2563eb;font-weight:800}
    .access-legend{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem!important;margin-bottom:1rem!important}
    .access-legend>span{min-height:38px;padding:.55rem .65rem;border:1px solid #e2e8f0;border-radius:9px;background:#f8fafc;line-height:1.35}
    .permission-table-wrap{display:block;width:100%;max-width:100%;min-width:0;overflow-x:auto;overscroll-behavior-inline:contain;border:1px solid #e2e8f0;border-radius:10px;scrollbar-color:#94a3b8 #e2e8f0;scrollbar-width:thin}
    .permission-table-wrap::-webkit-scrollbar{height:9px}.permission-table-wrap::-webkit-scrollbar-track{background:#e2e8f0;border-radius:999px}.permission-table-wrap::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:999px;border:2px solid #e2e8f0}
    .permission-table{width:max-content!important;min-width:100%;table-layout:fixed}
    .permission-table thead{background:#f1f5f9}
    .permission-table th{padding:.7rem .65rem!important;white-space:nowrap}
    .permission-table td{padding:.55rem .65rem!important;height:48px}
    .permission-table tbody tr:hover{background:#f8fbff!important}
    .permission-role{position:sticky;left:0;z-index:2;width:150px;min-width:150px;background:#fff;box-shadow:8px 0 12px -12px rgba(15,23,42,.55)}
    .permission-table thead .permission-role{z-index:4;background:#f1f5f9}
    .permission-table tbody tr:hover .permission-role{background:#f8fbff}
    .permission-role span{display:inline-flex!important;align-items:center;white-space:nowrap}
    .permission-module{min-width:132px!important;width:132px!important}
    .permission-select{width:100%;min-width:116px;max-width:145px;height:32px}
    .permission-lock{width:100%;max-width:145px;min-height:32px;justify-content:center;white-space:nowrap}
    .permission-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem 1.15rem;border-top:1px solid #e2e8f0;background:#f8fafc}
    .permission-change-state{font-size:.7rem;color:#64748b}.permission-change-state.is-dirty{color:#b45309;font-weight:700}
    .permission-save{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem .9rem;border:0;border-radius:8px;background:#2563eb;color:#fff;font:700 .72rem inherit;cursor:pointer;box-shadow:0 6px 14px rgba(37,99,235,.2)}
    .permission-save:disabled{background:#cbd5e1;box-shadow:none;cursor:not-allowed}
    @media(max-width:900px){.access-legend{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){.access-legend{grid-template-columns:1fr}.permission-help{display:none}}
</style>
@php
    $roleBadges = [
        'admin' => ['label' => 'Admin', 'style' => 'background: #dbeafe; color: #1e40af; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'admin_costing' => ['label' => 'Admin Costing', 'style' => 'background: #e0f2fe; color: #0369a1; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'coordinator_costing' => ['label' => 'Coordinator Costing', 'style' => 'background: #fef3c7; color: #92400e; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'marketing' => ['label' => 'Marketing', 'style' => 'background: #ccfbf1; color: #0f766e; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'engineering' => ['label' => 'Engineering', 'style' => 'background: #ecfccb; color: #3f6212; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'document_control' => ['label' => 'Document Control', 'style' => 'background: #ede9fe; color: #6d28d9; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'admin_control_project' => ['label' => 'Admin Control Project', 'style' => 'background: #ffedd5; color: #9a3412; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'editor' => ['label' => 'Editor', 'style' => 'background: #fef3c7; color: #92400e; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
        'viewer' => ['label' => 'Viewer', 'style' => 'background: #f1f5f9; color: #475569; padding: 0.125rem 0.5rem; border-radius: 6px; font-weight: 600; font-size: 0.75rem;'],
    ];

    $roleOptions = [
        'admin_costing' => 'Admin Costing',
        'coordinator_costing' => 'Coordinator Costing',
        'marketing' => 'Marketing',
        'engineering' => 'Engineering',
        'document_control' => 'Document Control',
        'admin_control_project' => 'Admin Control Project',
        'editor' => 'Editor',
        'viewer' => 'Viewer',
        'admin' => 'Admin',
    ];
@endphp
<div class="permission-shell">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; flex-shrink: 0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.875rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Role Legend --}}
    <form id="permissionAccessForm" method="POST" action="{{ route('permissions.update-access') }}">
    @csrf
    <div class="card permission-card">
        <div class="card-header">
            <h3 class="card-title">Hak Akses Setiap Role</h3>
            <span class="permission-help">Perubahan akses tersimpan otomatis saat opsi dipilih</span>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            {{-- Legend --}}
            <div class="access-legend" style="display: flex; gap: 1.25rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <span style="font-size: 0.75rem; color: #15803d; display: flex; align-items: center; gap: 0.3rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg> Akses penuh — bisa lihat & edit
                </span>
                <span style="font-size: 0.75rem; color: #d97706; display: flex; align-items: center; gap: 0.3rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg> Lihat saja — hanya baca, tidak bisa edit
                </span>
                <span style="font-size: 0.75rem; color: #dc2626; display: flex; align-items: center; gap: 0.3rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Tidak ada akses — halaman diblokir (403)
                </span>
                <span style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 0.3rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Terkunci — tidak dapat diubah
                </span>
            </div>
            <div class="permission-table-wrap">
            <table class="permission-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th class="permission-role" style="text-align: left; padding: 0.625rem 0.75rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em; min-width: 90px;">Role</th>
                        @foreach($modules as $key => $label)
                        <th class="permission-module" style="text-align: left; padding: 0.625rem 0.75rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em; min-width: 160px;">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr style="border-bottom: 1px solid #f1f5f9; {{ $role === 'admin' ? 'background: #f8fafc;' : '' }}">
                        <td class="permission-role" style="padding: 0.625rem 0.75rem;">
                            @php $roleBadge = $roleBadges[$role] ?? ['label' => ucwords(str_replace('_', ' ', $role)), 'style' => $roleBadges['viewer']['style']]; @endphp
                            <span style="{{ $roleBadge['style'] }}">{{ $roleBadge['label'] }}</span>
                        </td>
                        @foreach($modules as $moduleKey => $moduleLabel)
                            @php
                                $isLocked = $role === 'admin' || $moduleKey === 'user_management';
                                $access = ($role === 'admin' || $moduleKey === 'user_management' && $role !== 'admin')
                                    ? ($role === 'admin' ? 'full' : 'none')
                                    : ($permissionMatrix[$role][$moduleKey] ?? 'none');
                            @endphp
                            <td style="padding: 0.5rem 0.75rem;">
                                @if($isLocked)
                                    {{-- Terkunci: tampilkan badge saja --}}
                                    <span class="permission-lock" style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.775rem; color: #94a3b8; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.25rem 0.6rem;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        {{ $role === 'admin' ? 'Akses penuh' : 'Tidak ada akses' }}
                                    </span>
                                @else
                                    {{-- Dapat diubah: dropdown select --}}
                                        <select class="permission-select" name="permissions[{{ $role }}][{{ $moduleKey }}]" data-original="{{ $access }}"
                                            style="padding: 0.3rem 0.5rem; border-radius: 6px; font-size: 0.775rem; font-family: inherit; outline: none; cursor: pointer;
                                                border: 1.5px solid {{ $access === 'full' ? '#86efac' : ($access === 'view' ? '#fcd34d' : '#fca5a5') }};
                                                background: {{ $access === 'full' ? '#f0fdf4' : ($access === 'view' ? '#fffbeb' : '#fef2f2') }};
                                                color: {{ $access === 'full' ? '#15803d' : ($access === 'view' ? '#b45309' : '#dc2626') }};">
                                            <option value="full" {{ $access === 'full' ? 'selected' : '' }} style="color: #15803d; background: #f0fdf4;">✓ Akses penuh</option>
                                            <option value="view" {{ $access === 'view' ? 'selected' : '' }} style="color: #b45309; background: #fffbeb;">● Lihat saja</option>
                                            <option value="none" {{ $access === 'none' ? 'selected' : '' }} style="color: #dc2626; background: #fef2f2;">✗ Tidak ada akses</option>
                                        </select>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <div class="permission-actions">
            <span id="permissionChangeState" class="permission-change-state">Belum ada perubahan.</span>
            <button id="permissionSaveButton" class="permission-save" type="submit" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                Simpan Perubahan
            </button>
        </div>
    </div>
    </form>

    {{-- User List --}}
    <div class="card permission-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Daftar User ({{ $users->count() }})</h3>
            <button onclick="document.getElementById('addUserModal').style.display='flex'" class="btn btn-primary" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #1e40af, #2563eb); color: #fff; border: none; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.375rem; font-family: inherit;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah User
            </button>
        </div>
        <div class="card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0; background: #f8fafc;">
                            <th style="text-align: left; padding: 0.75rem 1rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">#</th>
                            <th style="text-align: left; padding: 0.75rem 1rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Nama</th>
                            <th style="text-align: left; padding: 0.75rem 1rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Email</th>
                            <th style="text-align: left; padding: 0.75rem 1rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Role</th>
                            <th style="text-align: left; padding: 0.75rem 1rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Dibuat</th>
                            <th style="text-align: center; padding: 0.75rem 1rem; color: #1e293b; font-weight: 700; text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
                                <td style="padding: 0.75rem 1rem; color: #64748b;">{{ $index + 1 }}</td>
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span style="background: #dbeafe; color: #1e40af; padding: 0 0.375rem; border-radius: 4px; font-size: 0.6875rem; font-weight: 600; margin-left: 0.375rem;">Anda</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #475569;">{{ $user->email }}</td>
                                <td style="padding: 0.75rem 1rem;">
                                    @php $userRoleBadge = $roleBadges[$user->role] ?? ['label' => ucwords(str_replace('_', ' ', $user->role)), 'style' => $roleBadges['viewer']['style']]; @endphp
                                    <span style="{{ $userRoleBadge['style'] }}">{{ $userRoleBadge['label'] }}</span>
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #64748b;">{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    <div style="display: flex; gap: 0.375rem; justify-content: center;">
                                        <button onclick="openEditModal({{ $user->id }}, '{{ e($user->name) }}', '{{ e($user->email) }}', '{{ $user->role }}')" style="padding: 0.375rem 0.625rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 0.75rem; color: #475569; font-family: inherit; font-weight: 500; transition: all 0.15s;" onmouseenter="this.style.background='#e2e8f0'" onmouseleave="this.style.background='#f1f5f9'">Edit</button>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('permissions.destroy', $user->id) }}" onsubmit="return confirm('Hapus user {{ e($user->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="padding: 0.375rem 0.625rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer; font-size: 0.75rem; color: #dc2626; font-family: inherit; font-weight: 500; transition: all 0.15s;" onmouseenter="this.style.background='#fee2e2'" onmouseleave="this.style.background='#fef2f2'">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem; text-align: center; color: #94a3b8;">Belum ada user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add User Modal --}}
<div id="addUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.4); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #fff; border-radius: 14px; width: 100%; max-width: 460px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.0625rem; font-weight: 700; color: #1e293b;">Tambah User Baru</h3>
            <button onclick="document.getElementById('addUserModal').style.display='none'" style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1.25rem; padding: 0.25rem;">&times;</button>
        </div>
        <form method="POST" action="{{ route('permissions.store') }}">
            @csrf
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Nama</label>
                    <input type="text" name="name" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none;" placeholder="Nama lengkap">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Email</label>
                    <input type="email" name="email" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none;" placeholder="email@dharma-electrindo.com">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Password</label>
                    <input type="password" name="password" required minlength="6" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none;" placeholder="Minimal 6 karakter">
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Role</label>
                    <select name="role" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none; background: #fff;">
                        @foreach($roleOptions as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="document.getElementById('addUserModal').style.display='none'" style="padding: 0.5rem 1rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; cursor: pointer; font-family: inherit; font-weight: 500;">Batal</button>
                <button type="submit" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #1e40af, #2563eb); color: #fff; border: none; border-radius: 8px; font-size: 0.8125rem; cursor: pointer; font-family: inherit; font-weight: 600;">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit User Modal --}}
<div id="editUserModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.4); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #fff; border-radius: 14px; width: 100%; max-width: 460px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.0625rem; font-weight: 700; color: #1e293b;">Edit User</h3>
            <button onclick="document.getElementById('editUserModal').style.display='none'" style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1.25rem; padding: 0.25rem;">&times;</button>
        </div>
        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Nama</label>
                    <input type="text" name="name" id="editName" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Email</label>
                    <input type="email" name="email" id="editEmail" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Password <span style="color: #94a3b8; font-weight: 400;">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password" minlength="6" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none;" placeholder="Password baru">
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem;">Role</label>
                    <select name="role" id="editRole" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-family: inherit; outline: none; background: #fff;">
                        @foreach($roleOptions as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="document.getElementById('editUserModal').style.display='none'" style="padding: 0.5rem 1rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; cursor: pointer; font-family: inherit; font-weight: 500;">Batal</button>
                <button type="submit" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #1e40af, #2563eb); color: #fff; border: none; border-radius: 8px; font-size: 0.8125rem; cursor: pointer; font-family: inherit; font-weight: 600;">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openEditModal(id, name, email, role) {
    document.getElementById('editUserForm').action = '/permissions/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editUserModal').style.display = 'flex';
}

const permissionSelects = Array.from(document.querySelectorAll('#permissionAccessForm .permission-select'));
const permissionSaveButton = document.getElementById('permissionSaveButton');
const permissionChangeState = document.getElementById('permissionChangeState');

function refreshPermissionChangeState() {
    const changedCount = permissionSelects.filter(select => select.value !== select.dataset.original).length;
    permissionSaveButton.disabled = changedCount === 0;
    permissionChangeState.classList.toggle('is-dirty', changedCount > 0);
    permissionChangeState.textContent = changedCount > 0
        ? `${changedCount} perubahan belum disimpan.`
        : 'Belum ada perubahan.';
}

permissionSelects.forEach(select => select.addEventListener('change', refreshPermissionChangeState));
</script>
@endsection
