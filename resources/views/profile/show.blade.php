@extends('layouts.app')
@section('title', 'Profile User')
@section('page-title', 'Profile User')
@section('breadcrumb')<span>Profile User</span>@endsection

@section('content')
<style>
.profile-page{width:min(100%,1040px);margin:0 auto}.profile-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.profile-heading h2{margin:0;color:#0f172a;font-size:1.08rem}.profile-heading p{margin:.25rem 0 0;color:#64748b;font-size:.78rem}.profile-back{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 .85rem;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#334155;font-size:.74rem;font-weight:800;text-decoration:none}.profile-layout{display:grid;grid-template-columns:330px minmax(0,1fr);gap:1rem;align-items:start}.profile-panel{overflow:hidden;border:1px solid #dbe5f2;border-radius:14px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.05)}
.profile-identity{padding:1.35rem;background:linear-gradient(145deg,#103b80,#2563eb);color:#fff;text-align:center}.profile-avatar{display:grid;width:72px;height:72px;margin:0 auto .8rem;place-items:center;border:3px solid rgba(255,255,255,.35);border-radius:22px;background:rgba(255,255,255,.14);font-size:1.8rem;font-weight:900}.profile-identity h3{margin:0;font-size:1.12rem}.profile-identity p{margin:.3rem 0 0;color:#dbeafe;font-size:.78rem;overflow-wrap:anywhere}.profile-role{display:inline-flex;margin-top:.65rem;padding:.28rem .65rem;border:1px solid rgba(255,255,255,.3);border-radius:999px;background:rgba(255,255,255,.15);font-size:.66rem;font-weight:850;text-transform:uppercase}.profile-details{padding:.55rem 1rem}.profile-detail{display:grid;grid-template-columns:72px minmax(0,1fr);gap:.75rem;padding:.75rem 0;border-bottom:1px solid #e2e8f0;font-size:.76rem}.profile-detail:last-child{border-bottom:0}.profile-detail span{color:#64748b;font-weight:700}.profile-detail strong{color:#1e293b;font-weight:750;overflow-wrap:anywhere}
.password-panel-head{display:flex;align-items:center;gap:.8rem;padding:1rem 1.15rem;border-bottom:1px solid #e2e8f0;background:#f8fafc}.password-panel-icon{display:grid;width:38px;height:38px;flex:0 0 auto;place-items:center;border-radius:10px;background:#dbeafe;color:#2563eb}.password-panel-head h3{margin:0;color:#0f172a;font-size:.9rem}.password-panel-head p{margin:.2rem 0 0;color:#64748b;font-size:.7rem}.profile-password{padding:1.15rem}.profile-password-grid{display:grid;gap:.85rem}.profile-password-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem}.profile-password-field label{display:block;margin-bottom:.38rem;color:#334155;font-size:.71rem;font-weight:800}.profile-password-field input{box-sizing:border-box;width:100%;height:42px;padding:0 .75rem;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#0f172a;font:inherit;font-size:.79rem;outline:none}.profile-password-field input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.11)}.profile-password-hint{color:#94a3b8;font-size:.65rem}.profile-password-error{margin-top:.3rem;color:#dc2626;font-size:.68rem;font-weight:700}.profile-password-alert{margin-bottom:1rem;padding:.7rem .85rem;border-radius:8px;font-size:.74rem;font-weight:700}.profile-password-alert.success{border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d}.profile-password-alert.error{border:1px solid #fecaca;background:#fef2f2;color:#b91c1c}.profile-password-actions{display:flex;justify-content:flex-end;padding-top:1rem;margin-top:1rem;border-top:1px solid #e2e8f0}.profile-password-actions button{min-height:40px;padding:.55rem 1rem;border:1px solid #2563eb;border-radius:8px;background:#2563eb;color:#fff;font-size:.73rem;font-weight:850;box-shadow:0 7px 16px rgba(37,99,235,.18);cursor:pointer}
@media(max-width:850px){.profile-layout{grid-template-columns:1fr}.profile-identity{padding:1rem}.profile-avatar{width:60px;height:60px;border-radius:18px;font-size:1.5rem}}@media(max-width:560px){.profile-heading{align-items:stretch;flex-direction:column}.profile-password-row{grid-template-columns:1fr}.profile-password-actions button{width:100%}}
</style>

<div class="profile-page">
    <div class="profile-heading">
        <div><h2>Pengaturan Akun</h2><p>Lihat informasi akun dan kelola keamanan password Anda.</p></div>
        <a href="{{ route('dashboard', absolute: false) }}" class="profile-back">← Kembali ke Dashboard</a>
    </div>
    <div class="profile-layout">
        <aside class="profile-panel">
            <div class="profile-identity">
                <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                <h3>{{ auth()->user()->name ?? 'User' }}</h3><p>{{ auth()->user()->email ?? '-' }}</p>
                <span class="profile-role">{{ auth()->user()->role ?? 'user' }}</span>
            </div>
            <div class="profile-details">
                <div class="profile-detail"><span>Nama</span><strong>{{ auth()->user()->name ?? '-' }}</strong></div>
                <div class="profile-detail"><span>Email</span><strong>{{ auth()->user()->email ?? '-' }}</strong></div>
                <div class="profile-detail"><span>Role</span><strong>{{ auth()->user()->role ?? '-' }}</strong></div>
            </div>
        </aside>
        <section class="profile-panel">
            <div class="password-panel-head">
                <div class="password-panel-icon"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
                <div><h3>Ganti Password</h3><p>Perbarui password secara berkala untuk menjaga keamanan akun.</p></div>
            </div>
            <form class="profile-password" method="POST" action="{{ route('profile.password.update', absolute: false) }}">
                @csrf @method('PUT')
                @if(session('password_success'))<div class="profile-password-alert success">{{ session('password_success') }}</div>@endif
                @if($errors->any())<div class="profile-password-alert error">Password belum dapat diperbarui. Periksa kembali isian Anda.</div>@endif
                <div class="profile-password-grid">
                    <div class="profile-password-field"><label for="current_password">Password Saat Ini</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" placeholder="Masukkan password saat ini" required>@error('current_password')<div class="profile-password-error">{{ $message }}</div>@enderror</div>
                    <div class="profile-password-row">
                        <div class="profile-password-field"><label for="password">Password Baru</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="8" placeholder="Minimal 8 karakter" required>@error('password')<div class="profile-password-error">{{ $message }}</div>@enderror</div>
                        <div class="profile-password-field"><label for="password_confirmation">Konfirmasi Password Baru</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" placeholder="Ulangi password baru" required></div>
                    </div>
                    <div class="profile-password-hint">Password baru harus minimal 8 karakter dan berbeda dari password saat ini.</div>
                </div>
                <div class="profile-password-actions"><button type="submit">Simpan Password Baru</button></div>
            </form>
        </section>
    </div>
</div>
@endsection
