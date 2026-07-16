@extends('layouts.app')

@section('title', 'Help Center')
@section('page-title', 'Help Center')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a><span class="breadcrumb-separator">/</span><span>Help Center</span>
@endsection

@section('content')
@php
    $roleLabels = ['admin'=>'Admin','admin_costing'=>'Admin Costing','coordinator_costing'=>'Coordinator Costing','marketing'=>'Marketing','editor'=>'Editor','viewer'=>'Viewer'];
@endphp
<div class="help-page">
    <section class="help-hero">
        <div><span>PANDUAN COSTING SYSTEM</span><h2>Apa yang ingin Anda pelajari?</h2><p>Panduan praktis sesuai alur kerja dan role <b>{{ $roleLabels[$role] ?? ucfirst($role) }}</b>.</p></div>
        <label class="help-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="helpSearch" type="search" placeholder="Cari topik bantuan..."></label>
    </section>

    <section class="help-shortcuts help-topic" data-help-text="shortcut keyboard pencarian global navigasi cepat">
        <h3>Shortcut yang paling berguna</h3>
        <div class="help-shortcut-grid">
            <div><kbd>Ctrl</kbd><kbd>K</kbd><span>Pencarian global &amp; aksi cepat</span></div>
            <div><kbd>G</kbd><kbd>T</kbd><span>Buka My Tasks</span></div>
            <div><kbd>G</kbd><kbd>P</kbd><span>Buka Project</span></div>
            <div><kbd>G</kbd><kbd>D</kbd><span>Buka Dashboard</span></div>
            <div><kbd>?</kbd><span>Buka Help Center</span></div>
        </div>
    </section>

    <section class="help-workflow help-topic" data-help-text="alur kerja utama project dokumen harga costing approval marketing">
        <h3>Alur kerja utama</h3>
        <div class="help-flow">
            @foreach([['1','Project','Buat identitas project dan revisi.'],['2','Dokumen','Lengkapi Partlist dan UMH.'],['3','Harga Part','Selesaikan semua part tanpa harga.'],['4','Costing','Lengkapi material, cycle time, dan resume.'],['5','Approval','Kirim untuk diperiksa Coordinator.'],['6','Marketing','Kirim COGM yang sudah disetujui.']] as $step)
                <div><b>{{ $step[0] }}</b><span><strong>{{ $step[1] }}</strong><small>{{ $step[2] }}</small></span></div>
            @endforeach
        </div>
    </section>

    <section class="help-sla-explainer help-topic" data-help-text="deadline sla service level agreement aging overdue jatuh tempo sisa hari penjelasan contoh">
        <div class="help-explainer-head">
            <div><span>PENGELOLAAN WAKTU</span><h3>Penjelasan Deadline, SLA &amp; Aging</h3></div>
            <p>Ketiga indikator ini membantu Anda memahami kapan tugas harus selesai dan sudah berapa lama tugas berada pada tahap saat ini.</p>
        </div>
        <div class="help-term-grid">
            <article>
                <b class="term-icon deadline">D</b>
                <div><h4>Deadline</h4><p>Tanggal terakhir sebuah tugas harus diselesaikan. Contohnya, deadline <strong>20 Juli 2026</strong> berarti tugas harus selesai paling lambat pada tanggal tersebut.</p></div>
            </article>
            <article>
                <b class="term-icon sla">S</b>
                <div><h4>SLA</h4><p><strong>Service Level Agreement</strong> adalah standar waktu yang diberikan untuk menyelesaikan satu tahap workflow. Contohnya, tahap Approval memiliki SLA default <strong>1 hari</strong>.</p></div>
            </article>
            <article>
                <b class="term-icon aging">A</b>
                <div><h4>Aging</h4><p>Jumlah hari sebuah tugas berada pada tahap saat ini tanpa pembaruan. <strong>Aging 4 hari</strong> berarti tugas belum mengalami perkembangan selama empat hari.</p></div>
            </article>
        </div>
        <div class="help-sla-example">
            <strong>Contoh</strong>
            <p>Sebuah costing masuk tahap Approval pada <b>16 Juli</b>. SLA Approval adalah <b>1 hari</b>, sehingga deadline otomatisnya <b>17 Juli</b>. Jika pada <b>19 Juli</b> belum selesai, tugas akan ditampilkan sebagai <b>terlambat 2 hari</b>.</p>
        </div>
        <div class="help-sla-defaults">
            <span>SLA default:</span><b>Dokumen · 3 hari</b><b>Harga Part · 2 hari</b><b>Costing · 3 hari</b><b>Approval · 1 hari</b><b>Marketing · 2 hari</b>
        </div>
    </section>

    <section class="help-guides">
        <article class="help-topic" data-help-text="project my tasks tindakan berikutnya prioritas progress">
            <h3>Memulai pekerjaan</h3>
            <p>Buka <a href="{{ route('my-tasks', absolute: false) }}">My Tasks</a> untuk melihat pekerjaan sesuai role. Dahulukan kartu prioritas merah, kemudian pilih Buka Tugas.</p>
        </article>
        <article class="help-topic" data-help-text="autosave draft pulihkan form costing simpan">
            <h3>Draft dan autosave</h3>
            <p>Perubahan pada Form Costing otomatis disimpan sebagai draft pribadi. Saat kembali, pilih Pulihkan Draft. Tombol simpan utama mengesahkan data dan membersihkan draft.</p>
        </article>
        <article class="help-topic" data-help-text="validasi error perbaiki sekarang kolom salah">
            <h3>Memperbaiki data yang tidak valid</h3>
            <p>Jika penyimpanan gagal, ringkasan validasi menampilkan semua masalah. Pilih Perbaiki Sekarang untuk langsung menuju kolom yang salah.</p>
        </article>
        <article class="help-topic" data-help-text="pencarian global customer part material ctrl k">
            <h3>Mencari data dengan cepat</h3>
            <p>Tekan Ctrl+K dari halaman mana pun. Cari berdasarkan part number, project, customer, model, atau material, lalu gunakan tombol panah dan Enter tanpa mouse.</p>
        </article>
        <article class="help-topic" data-help-text="favorit terakhir dibuka halaman terbaru">
            <h3>Favorit dan halaman terakhir</h3>
            <p>Gunakan panel Ctrl+K untuk menyimpan halaman yang sering digunakan sebagai favorit. Lima halaman terakhir juga tersimpan otomatis pada perangkat ini.</p>
        </article>
        <article class="help-topic" data-help-text="panduan interaktif tour halaman menu bantuan">
            <h3>Panduan interaktif</h3>
            <p>Pilih tombol Panduan di header untuk memulai tur halaman aktif. Kotak panduan ditempatkan di samping elemen yang disorot tanpa menutupinya.</p>
        </article>
        <article class="help-topic" data-help-text="deadline sla aging overdue prioritas tugas deadline khusus">
            <h3>Deadline, SLA, dan aging</h3>
            <p>My Tasks menampilkan tanggal jatuh tempo, sisa waktu, aging, dan status keterlambatan setiap pekerjaan. Buka Activity &amp; Comments untuk menetapkan deadline khusus atau kosongkan agar kembali menggunakan SLA tahap tersebut.</p>
        </article>
        <article class="help-topic" data-help-text="activity timeline audit trail riwayat status costing">
            <h3>Timeline aktivitas</h3>
            <p>Audit trail mencatat pembuatan revisi, perubahan status, pembaruan costing, perubahan deadline, dan aktivitas diskusi lengkap dengan pengguna serta waktunya.</p>
        </article>
        <article class="help-topic" data-help-text="komentar mention diskusi tim handle notifikasi">
            <h3>Komentar dan mention</h3>
            <p>Gunakan @handle dalam diskusi project untuk menyebut anggota tim. Mention terbaru muncul pada lonceng notifikasi mereka dan langsung mengarah ke diskusi.</p>
        </article>
        <article class="help-topic" data-help-text="notification center read unread sembunyikan preferensi notifikasi">
            <h3>Notification Center</h3>
            <p>Klik lonceng di header lalu pilih Lihat Semua. Anda dapat menandai notifikasi sudah dibaca, menyembunyikannya, atau memilih kategori notifikasi melalui bagian Preferensi.</p>
        </article>
        <article class="help-topic" data-help-text="kelengkapan data score skor missing item perbaikan project">
            <h3>Skor kelengkapan data</h3>
            <p>Skor 0–100% menunjukkan seberapa lengkap identitas, PIC, dokumen, material, harga, dan Cycle Time sebuah project. Buka daftar item yang kurang untuk langsung menuju halaman perbaikan.</p>
        </article>
        <article class="help-topic" data-help-text="bulk actions update massal deadline pic export csv pilihan revisi">
            <h3>Bulk Actions</h3>
            <p>Pada halaman Project, buka detail group lalu centang beberapa revisi. Gunakan toolbar untuk memperbarui deadline atau PIC secara massal, maupun mengunduh revisi terpilih sebagai CSV.</p>
        </article>
        <article id="sla-performance-help" class="help-topic" data-help-text="sla performance dashboard compliance kepatuhan overdue aging pic tahap">
            <h3>Membaca SLA Performance</h3>
            <p>Kepatuhan SLA adalah persentase pekerjaan aktif yang belum melewati deadline. Gunakan ringkasan per tahap untuk menemukan hambatan, lalu tabel Performa PIC untuk menentukan siapa yang perlu segera dikoordinasikan.</p>
        </article>
        <article class="help-topic" data-help-text="uat feedback laporkan bug masalah usability screenshot">
            <h3>Melaporkan masalah</h3>
            <p>Pilih Help &amp; Support lalu Laporkan Masalah. Jelaskan kendala dan lampirkan screenshot bila perlu; sistem otomatis menyertakan halaman serta informasi browser agar Admin lebih mudah menindaklanjuti.</p>
        </article>
        <article class="help-topic" data-help-text="revisi perbandingan approval cost material perubahan">
            <h3>Perbandingan revisi</h3>
            <p>Pada Activity &amp; Comments, bagian Perbandingan Revisi menunjukkan perubahan total COGM, komponen biaya, dan material dibanding revisi sebelumnya. Coordinator dapat membuka perbandingan lengkap sebelum memberi keputusan approval.</p>
        </article>
    </section>

    <section class="help-faq help-topic" data-help-text="faq pertanyaan draft harga approval akses permission">
        <h3>Pertanyaan umum</h3>
        <details><summary>Mengapa project belum bisa masuk tahap approval?</summary><p>Pastikan semua dokumen lengkap, data costing tersedia, dan tidak ada part tanpa harga yang masih terbuka. Periksa tindakan berikutnya pada Project atau My Tasks.</p></details>
        <details><summary>Apakah autosave sama dengan penyimpanan final?</summary><p>Tidak. Autosave hanya melindungi draft pribadi agar data tidak hilang. Gunakan Simpan Data Costing untuk mengesahkan data dan memperbarui workflow.</p></details>
        <details><summary>Mengapa saya tidak bisa membuka suatu menu?</summary><p>Akses menu mengikuti role dan permission. Hubungi Admin jika pekerjaan Anda membutuhkan akses tambahan.</p></details>
    </section>
    <div class="help-no-result" id="helpNoResult" hidden>Topik tidak ditemukan. Coba gunakan kata pencarian yang lebih singkat.</div>
</div>

<style>
    .help-page{max-width:1250px;margin:0 auto;color:#193451}.help-hero{display:flex;align-items:center;justify-content:space-between;gap:25px;padding:27px 30px;border-radius:16px;background:linear-gradient(120deg,#063d86,#0876ec);color:#fff}.help-hero>div>span{color:#bfdbfe;font-size:10px;font-weight:900;letter-spacing:.13em}.help-hero h2{margin:5px 0;font-size:25px}.help-hero p{margin:0;color:#dbeafe;font-size:13px}.help-search{display:flex;align-items:center;gap:8px;width:min(390px,100%);padding:11px 13px;border-radius:10px;background:#fff;color:#47708f}.help-search svg{width:18px}.help-search input{width:100%;border:0;outline:0;color:#1a3553}.help-shortcuts,.help-workflow,.help-faq,.help-sla-explainer{margin-top:16px;padding:20px;border:1px solid #d8e4ef;border-radius:14px;background:#fff}.help-page h3{margin:0 0 14px;font-size:15px}.help-shortcut-grid{display:flex;flex-wrap:wrap;gap:8px}.help-shortcut-grid>div{display:flex;align-items:center;gap:5px;padding:9px 11px;border-radius:9px;background:#f1f6fb;color:#5a6f86;font-size:11px}.help-page kbd{padding:3px 6px;border:1px solid #cbd8e5;border-bottom-width:2px;border-radius:5px;background:#fff;color:#255078;font:800 10px/1 inherit}.help-flow{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}.help-flow>div{display:flex;gap:9px;padding:11px;border:1px solid #dfE8f1;border-radius:10px;background:#f8fafc}.help-flow>div>b{display:grid;place-items:center;flex:0 0 25px;height:25px;border-radius:50%;background:#0c70df;color:#fff;font-size:10px}.help-flow span,.help-flow strong,.help-flow small{display:block}.help-flow strong{font-size:11px}.help-flow small{margin-top:3px;color:#708298;font-size:9px;line-height:1.35}.help-explainer-head{display:flex;align-items:flex-start;justify-content:space-between;gap:25px}.help-explainer-head span{color:#0870df;font-size:9px;font-weight:900;letter-spacing:.12em}.help-explainer-head h3{margin:4px 0}.help-explainer-head>p{max-width:520px;margin:0;color:#667b91;font-size:11px;line-height:1.55}.help-term-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px}.help-term-grid article{display:flex;gap:11px;padding:14px;border:1px solid #dce7f1;border-radius:11px;background:#f8fbfe}.term-icon{display:grid;place-items:center;flex:0 0 29px;height:29px;border-radius:8px;color:#fff;font-size:11px}.term-icon.deadline{background:#176fd0}.term-icon.sla{background:#7c3aed}.term-icon.aging{background:#d97706}.help-term-grid h4{margin:0 0 5px;font-size:12px}.help-term-grid p{margin:0;color:#63778d;font-size:10px;line-height:1.55}.help-sla-example{margin-top:11px;padding:12px 14px;border-left:4px solid #ef4444;border-radius:8px;background:#fff5f5}.help-sla-example strong{color:#b42318;font-size:11px}.help-sla-example p{margin:4px 0 0;color:#754848;font-size:10px;line-height:1.55}.help-sla-defaults{display:flex;flex-wrap:wrap;gap:7px;margin-top:11px}.help-sla-defaults span,.help-sla-defaults b{padding:6px 9px;border-radius:7px;background:#edf4fb;color:#49647f;font-size:9px}.help-sla-defaults span{background:#173f6c;color:#fff}.help-guides{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px}.help-guides article{padding:17px;border:1px solid #d8e4ef;border-radius:13px;background:#fff}.help-guides h3{margin-bottom:7px}.help-guides p,.help-faq p{margin:0;color:#61758c;font-size:12px;line-height:1.65}.help-guides a{color:#086bdc;font-weight:800}.help-faq details{border-top:1px solid #e3eaf2;padding:12px 2px}.help-faq summary{cursor:pointer;font-size:12px;font-weight:800}.help-faq details p{padding-top:8px}.help-no-result{margin-top:16px;padding:35px;border:1px dashed #c5d4e3;border-radius:13px;background:#fff;text-align:center;color:#71849a}@media(max-width:980px){.help-flow,.help-term-grid{grid-template-columns:repeat(3,1fr)}.help-guides{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.help-hero,.help-explainer-head{align-items:stretch;flex-direction:column}.help-flow,.help-guides,.help-term-grid{grid-template-columns:1fr}.help-search{width:auto}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input=document.getElementById('helpSearch'), topics=Array.from(document.querySelectorAll('.help-topic')), empty=document.getElementById('helpNoResult');
    input?.addEventListener('input',function(){const query=this.value.trim().toLowerCase();let visible=0;topics.forEach(topic=>{const match=!query||(topic.dataset.helpText+' '+topic.textContent).toLowerCase().includes(query);topic.hidden=!match;if(match)visible++;});empty.hidden=visible!==0;});
});
</script>
@endsection
