@extends('layouts.app')
@section('title','Operations Center')
@section('page-title','Operations Center')
@section('breadcrumb')<a href="{{ route('dashboard',absolute:false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>Operations Center</span>@endsection
@section('content')
<div class="ops-page" data-tour="operations-center">
    <section class="ops-hero future-glow-card">
        <div><span>MISSION CONTROL</span><h2>Operations Center</h2><p>Pusat kesiapan rilis, kalender SLA, dan perlindungan data aplikasi.</p></div>
        <div class="ops-live"><i></i> SYSTEM READY</div>
    </section>

    <nav class="ops-tabs" aria-label="Operations modules">
        <button class="active" data-ops-tab="release">Release Readiness</button><button data-ops-tab="calendar">Business Calendar</button><button data-ops-tab="backup">Backup & Restore</button>
    </nav>

    <section class="ops-tab active" data-ops-panel="release">
        <div class="ops-grid-main">
            <div class="ops-stack">
                @forelse($releases as $release)
                    @php($passed=$release->checks->where('status','pass')->count()) @php($total=$release->checks->count()) @php($progress=$total?round($passed/$total*100):0)
                    <article class="ops-card release-card">
                        <header><div><span>{{ strtoupper($release->status) }}</span><h3>{{ $release->name }} <small>{{ $release->version }}</small></h3></div><strong>{{ $progress }}%</strong></header>
                        <div class="ops-progress"><i style="width:{{ $progress }}%"></i></div>
                        <p>{{ $passed }} dari {{ $total }} pemeriksaan lulus · target {{ $release->target_release_at?->format('d M Y H:i') ?: 'belum ditentukan' }}</p>
                        <div class="check-list">
                            @foreach($release->checks as $check)
                                <form method="POST" action="{{ route('operations.checks.update',$check,absolute:false) }}">@csrf @method('PATCH')
                                    <div><b>{{ $check->title }}</b><small>{{ ucfirst($check->category) }} @if($check->tester) · {{ $check->tester->name }} @endif</small></div>
                                    <select name="status" onchange="this.form.submit()"><option value="pending" @selected($check->status==='pending')>Pending</option><option value="pass" @selected($check->status==='pass')>Pass</option><option value="fail" @selected($check->status==='fail')>Fail</option><option value="blocked" @selected($check->status==='blocked')>Blocked</option></select>
                                </form>
                            @endforeach
                        </div>
                        <form class="release-status" method="POST" action="{{ route('operations.releases.update',$release,absolute:false) }}">@csrf @method('PATCH')<select name="status">@foreach(['draft','testing','ready','released','blocked'] as $status)<option value="{{ $status }}" @selected($release->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select><input name="notes" value="{{ $release->notes }}" placeholder="Catatan release"><button>Simpan Status</button></form>
                    </article>
                @empty<div class="ops-empty">Belum ada release cycle. Buat release pertama melalui panel di samping.</div>@endforelse
            </div>
            <aside class="ops-card ops-form"><span>NEW RELEASE</span><h3>Buat Release Cycle</h3><form method="POST" action="{{ route('operations.releases.store',absolute:false) }}">@csrf<label>Nama<input name="name" required placeholder="Production Release"></label><label>Versi<input name="version" placeholder="v1.1.0"></label><label>Target<input type="datetime-local" name="target_release_at"></label><label>Catatan<textarea name="notes" rows="3"></textarea></label><button>Buat Checklist Otomatis</button></form></aside>
        </div>
    </section>

    <section class="ops-tab" data-ops-panel="calendar">
        <div class="ops-grid-main">
            <div class="ops-card"><span>ACTIVE CALENDAR</span><h3>Hari libur perusahaan</h3><p class="ops-muted">Weekend dan tanggal berikut otomatis tidak dihitung sebagai hari kerja pada deadline SLA.</p><div class="holiday-list">@forelse($holidays as $holiday)<article><time>{{ $holiday->holiday_date->format('d M Y') }}</time><b>{{ $holiday->name }}</b><form method="POST" action="{{ route('operations.holidays.destroy',$holiday,absolute:false) }}">@csrf @method('DELETE')<button aria-label="Hapus">×</button></form></article>@empty<div class="ops-empty">Belum ada hari libur khusus.</div>@endforelse</div></div>
            <aside class="ops-card ops-form"><span>BUSINESS DAY RULE</span><h3>Tambah Hari Libur</h3><form method="POST" action="{{ route('operations.holidays.store',absolute:false) }}">@csrf<label>Tanggal<input type="date" name="holiday_date" required></label><label>Nama<input name="name" required placeholder="Libur Nasional"></label><button>Simpan Kalender</button></form></aside>
        </div>
    </section>

    <section class="ops-tab" data-ops-panel="backup">
        <div class="ops-grid-main">
            <div class="ops-card"><span>RECOVERY VAULT</span><h3>Backup terverifikasi</h3><div class="backup-list">@forelse($backups as $backup)<article><div><b>{{ $backup->filename }}</b><small>{{ strtoupper($backup->database_driver) }} · {{ number_format($backup->size_bytes/1024/1024,2) }} MB · {{ $backup->created_at->format('d M Y H:i') }} · {{ strtoupper($backup->status) }}</small></div><div class="ops-actions"><form method="POST" action="{{ route('operations.backups.verify',$backup,absolute:false) }}">@csrf<button>Verify</button></form><a href="{{ route('operations.backups.download',$backup,absolute:false) }}">Download</a><details><summary>Restore</summary><form method="POST" action="{{ route('operations.backups.restore',$backup,absolute:false) }}" onsubmit="return confirm('Restore akan mengganti database aktif. Lanjutkan?')">@csrf<input name="confirmation" placeholder="Ketik RESTORE" required pattern="RESTORE"><button>Restore Aman</button></form></details></div></article>@empty<div class="ops-empty">Belum ada backup tercatat.</div>@endforelse</div></div>
            <aside class="ops-card ops-form"><span>CREATE SNAPSHOT</span><h3>Backup Sekarang</h3><p class="ops-muted">Sistem membuat salinan database, checksum SHA-256, dan safety copy sebelum proses restore.</p><form method="POST" action="{{ route('operations.backups.store',absolute:false) }}">@csrf<label>Catatan<input name="notes" placeholder="Sebelum release v1.1"></label><button>Buat & Verifikasi Backup</button></form></aside>
        </div>
    </section>
</div>
<script>document.querySelectorAll('[data-ops-tab]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-ops-tab],[data-ops-panel]').forEach(e=>e.classList.remove('active'));b.classList.add('active');document.querySelector(`[data-ops-panel="${b.dataset.opsTab}"]`).classList.add('active')}));</script>
<style>
.ops-page{max-width:1480px;margin:auto}.ops-hero{display:flex;align-items:center;justify-content:space-between;padding:26px 30px;border-radius:20px;background:linear-gradient(125deg,#061b3f,#073b89 58%,#006fd6);color:#fff;overflow:hidden}.ops-hero span,.ops-card>span{font-size:10px;font-weight:900;letter-spacing:.15em;color:#71d6ff}.ops-hero h2{margin:5px 0;font-size:28px}.ops-hero p{margin:0;color:#c6dcf7;font-size:12px}.ops-live{font-size:10px;font-weight:900;letter-spacing:.08em}.ops-live i{display:inline-block;width:8px;height:8px;margin-right:7px;border-radius:99px;background:#35f2b4;box-shadow:0 0 16px #35f2b4}.ops-tabs{display:flex;gap:8px;margin:16px 0}.ops-tabs button{padding:10px 15px;border:1px solid #d5e1ee;border-radius:10px;background:#fff;color:#49637e;font:800 11px inherit;cursor:pointer}.ops-tabs button.active{border-color:#0785ef;background:#0785ef;color:#fff;box-shadow:0 8px 22px #0785ef35}.ops-tab{display:none}.ops-tab.active{display:block}.ops-grid-main{display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,.7fr);gap:14px}.ops-stack{display:grid;gap:12px}.ops-card{padding:20px;border:1px solid #d8e5f1;border-radius:16px;background:rgba(255,255,255,.94);box-shadow:0 12px 35px rgba(18,52,88,.07)}.release-card header{display:flex;justify-content:space-between}.release-card header span{color:#087dde;font-size:9px;font-weight:900}.release-card h3{margin:3px 0;font-size:15px}.release-card h3 small{color:#73869a}.release-card header strong{font-size:22px;color:#067fdc}.release-card>p,.ops-muted{color:#70849a;font-size:10px}.ops-progress{height:6px;margin:9px 0;border-radius:99px;background:#e2eaf1}.ops-progress i{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#078cef,#24d0bd)}.check-list{margin-top:13px;border-top:1px solid #e7edf3}.check-list form{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 2px;border-bottom:1px solid #edf2f6}.check-list b,.check-list small{display:block}.check-list b{font-size:10px}.check-list small{color:#788b9e;font-size:8px}.check-list select,.release-status select,.release-status input{min-height:32px;border:1px solid #d3deea;border-radius:8px;padding:0 8px;font-size:9px}.release-status{display:grid;grid-template-columns:130px 1fr auto;gap:7px;margin-top:12px}.release-status button,.ops-form button{border:0;border-radius:9px;background:#087de2;color:#fff;font-weight:850;padding:0 14px;min-height:36px}.ops-form h3,.ops-card h3{margin:5px 0 13px}.ops-form label{display:block;margin-top:10px;color:#5f7489;font-size:9px;font-weight:800}.ops-form input,.ops-form textarea{display:block;width:100%;margin-top:5px;padding:9px;border:1px solid #d2deea;border-radius:9px;font:11px inherit}.ops-form button{width:100%;margin-top:13px}.holiday-list article,.backup-list article{display:flex;align-items:center;gap:15px;padding:12px 0;border-bottom:1px solid #e9eff4}.holiday-list time{min-width:100px;color:#0782df;font-size:10px;font-weight:900}.holiday-list b{flex:1;font-size:11px}.holiday-list button{border:0;background:#fee2e2;color:#d32f2f;border-radius:7px;width:28px;height:28px}.backup-list article{justify-content:space-between}.backup-list b,.backup-list small{display:block}.backup-list b{font-size:10px}.backup-list small{color:#77899a;font-size:8px}.ops-actions{display:flex;align-items:center;gap:6px}.ops-actions button,.ops-actions a,.ops-actions summary{padding:6px 8px;border:1px solid #cfe0ed;border-radius:7px;background:#fff;color:#28618d;text-decoration:none;font-size:8px;font-weight:800;cursor:pointer}.ops-actions details{position:relative}.ops-actions details form{position:absolute;right:0;z-index:2;width:190px;padding:10px;border:1px solid #d5e2ed;border-radius:10px;background:#fff;box-shadow:0 10px 30px #183b5d30}.ops-actions details input{width:100%;padding:7px;border:1px solid #ccdbe8;border-radius:7px}.ops-actions details button{margin-top:6px;color:#c32b2b}.ops-empty{padding:30px;text-align:center;color:#788b9e;font-size:11px}@media(max-width:900px){.ops-grid-main{grid-template-columns:1fr}.ops-hero{align-items:flex-start;flex-direction:column;gap:14px}.release-status{grid-template-columns:1fr}.ops-tabs{overflow:auto}.backup-list article{align-items:flex-start;flex-direction:column}.ops-actions{flex-wrap:wrap}}
</style>
@endsection
