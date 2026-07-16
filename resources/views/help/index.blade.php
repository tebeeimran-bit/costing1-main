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
            <span>SLA default:</span><b>Dokumen Â· 3 hari</b><b>Harga Part Â· 2 hari</b><b>Costing Â· 3 hari</b><b>Approval Â· 1 hari</b><b>Marketing Â· 2 hari</b>
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
            <p>Skor 0â€“100% menunjukkan seberapa lengkap identitas, PIC, dokumen, material, harga, dan Cycle Time sebuah project. Buka daftar item yang kurang untuk langsung menuju halaman perbaikan.</p>
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
    .help-page{max-width:1250px;margin:0 auto;color:#193451}.help-hero{display:flex;align-items:center;justify-content:space-between;gap:25px;padding:27px 30px;border-radius:16px;background:linear-gradient(120deg,#063d86,#0876ec);color:#fff}.help-hero>div>span{color:#bfdbfe;font-size:10px;font-weight:900;letter-spacing:.13em}.help-hero h2{margin:5px 0;font-size:25px}.help-hero p{margin:0;color:#dbeafe;font-size:13px}.help-search{display:flex;align-items:center;gap:8px;width:min(390px,100%);padding:11px 13px;border-radius:10px;background:#fff;color:#47708f}.help-search svg{width:18px}.help-search input{width:100%;border:0;outline:0;color:#1a3553}.help-shortcuts,.help-workflow,.help-faq,.help-sla-explainer{margin-top:16px;padding:20px;border:1px solid #d8e4ef;border-radius:14px;background:#fff}.help-page h3{margin:0 0 14px;font-size:15px}.help-shortcut-grid{display:flex;flex-wrap:wrap;gap:8px}.help-shortcut-grid>div{display:flex;align-items:center;gap:5px;padding:9px 11px;border-radius:9px;background:#f1f6fb;color:#5a6f86;font-size:11px}.help-page kbd{padding:3px 6px;border:1px solid #cbd8e5;border-bottom-width:2px;border-radius:5px;background:#fff;color:#255078;font:800 10px/1 inherit}.help-flow{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}.help-flow>div{display:flex;gap:9px;padding:11px;border:1px solid #dfE8f1;border-radius:10px;background:#f8fafc}.help-flow>div>b{display:grid;place-items:center;flex:0 0 25px;height:25px;border-radius:50%;background:#0c70df;color:#fff;font-size:10px}.help-flow span,.help-flow strong,.help-flow small{display:block}.help-flow strong{font-size:11px}.help-flow small{margin-top:3px;color:#708298;font-size:9px;line-height:1.35}.help-explainer-head{display:flex;align-items:flex-start;justify-content:space-between;gap:25px}.help-explainer-head span{color:#0870df;font-size:9px;font-weight:900;letter-spacing:.12em}.help-explainer-head h3{margin:4px 0}.help-explainer-head>p{max-width:520px;margin:0;color:#667b91;font-size:11px;line-height:1.55}.help-term-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px}.help-term-grid article{display:flex;gap:11px;padding:14px;border:1px solid #dce7f1;border-radius:11px;background:#f8fbfe}.term-icon{display:grid;place-items:center;flex:0 0 29px;height:29px;border-radius:8px;color:#fff;font-size:11px}.term-icon.deadline{background:#176fd0}.term-icon.sla{background:#7c3aed}.term-icon.aging{background:#d97706}.help-term-grid h4{margin:0 0 5px;font-size:12px}.help-term-grid p{margin:0;color:#63778d;font-size:10px;line-height:1.55}.help-sla-example{margin-top:11px;padding:12px 14px;border-left:4px solid #ef4444;border-radius:8px;backã8îÚ$z{-®éÜj×\Ú\Ë]™[‹Ù]Z[YÚİ[Y[‹Y[™Ú[™Y\š[™ÉËÔ™\ÜÛÛ›Û\˜Û\ÜË	Ø[˜[\Ú\Õ™[‘[™Ú[™Y\š[™É×JKO›˜[YJ	Ø[˜[\Ú\Ë]™[‹™[™Ú[™Y\š[™ÉÊNÂˆ›İ]N™Ù]
	ËÛ\Ü˜[‰ËÔ™\ÜÛÛ›Û\˜Û\ÜË	Û\Ü˜[‰×JKO›˜[YJ	Û\Ü˜[‰ÊNÂˆ›İ]N™Ù]
	ËÜÛK\\™›Ü›X[˜ÙIËÛT\™›Ü›X[˜ÙPÛÛ›Û\˜Û\ÜÊKO›˜[YJ	ÜÛK\\™›Ü›X[˜ÙIÊNÂˆJNÂ‚ˆËÈ8¥ 8¥ UPTÑH8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ ˆ›İ]N›ZY]Ø\™J	Ü\›Z\ÜÚ[Û™]X˜\ÙIÊKO™Ü›İ\
[˜İ[Ûˆ

HÂˆËÈ˜]H	ˆİ\œÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ˜]KZİ\œÉËÔ™\ÜÛÛ›Û\˜Û\ÜË	Ü˜]Rİ\œÉ×JKO›˜[YJ	Ü˜]KZİ\œÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKÜ˜]KZİ\œÉËÔ™\ÜÛÛ›Û\˜Û\ÜË	ÜİÜ™Q^Ú[™ÙT˜]I×JKO›˜[YJ	Ü˜]KZİ\œËœİÜ™IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜ˜]KZİ\œËŞÚYIËÔ™\ÜÛÛ›Û\˜Û\ÜË	Ù\İ›ŞQ^Ú[™ÙT˜]I×JKO›˜[YJ	Ü˜]KZİ\œË™\İ›ŞIÊNÂ‚ˆËÈ[œšXÙYˆ›İ]N™Ù]
	ËÙ]X˜\ÙKİ[œšXÙY\\ÉËÔ™\ÜÛÛ›Û\˜Û\ÜË	İ[œšXÙY\É×JKO›˜[YJ	İ[œšXÙY\\ÉÊNÂ‚ˆËÈ]X˜\ÙH[™^	ˆ›ÙXİÈ\İˆ›İ]N™Ù]
	ËÙ]X˜\ÙIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ú[™^	×JKO›˜[YJ	Ù]X˜\ÙIÊNÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ›ÙXİÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ü›ÙXİÉ×JKO›˜[YJ	Ù]X˜\ÙKœ›ÙXİÉÊNÂ‚ˆËÈ\Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ü\É×JKO›˜[YJ	Ù]X˜\ÙKœ\ÉÊNÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ\Ëİ[\]IËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÙİÛ›ØY\Õ[\]I×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë[\]IÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKÜ\ËÚ[\Ü	ËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ú[\Ü\Ñ^Ù[	×JKO›˜[YJ	Ù]X˜\ÙKœ\Ëš[\Ü	ÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜ\ËØ[ËY[]IËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT\Ğ[É×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë™\İ›ŞKX[ÉÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜ\ËÙ\İ›ŞKX[	ËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT\Ğ[	×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë™\İ›ŞKX[	ÊNÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ\ËØÜ™X]IËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ØÜ™X]T\	×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë˜Ü™X]IÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKÜ\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™T\	×JKO›˜[YJ	Ù]X˜\ÙKœ\ËœİÜ™IÊNÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ\ËŞÚYKÙY]	ËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÙY]\	×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë™Y]	ÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKÜ\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]T\	×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜ\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT\	×JKO›˜[YJ	Ù]X˜\ÙKœ\Ë™\İ›ŞIÊNÂ‚ˆËÈÚ\™\Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKİÚ\™\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İÚ\™\É×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\ÉÊNÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKİÚ\™\Ëİ[\]IËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÙİÛ›ØYÚ\™\Õ[\]I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ë[\]IÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKİÚ\™\ËÚ[\Ü	ËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ú[\ÜÚ\™\Ñ^Ù[	×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ëš[\Ü	ÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKİÚ\™\ËÜİÚ]Ú\˜]K[[Û	ËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÚ]ÚÚ\™T˜]S[Û	×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\ËœİÚ]Ú\˜]K[[Û	ÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKİÚ\™\ËÜ˜]\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™UÚ\™T˜]I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ëœ˜]\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKİÚ\™\ËÜ˜]\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]UÚ\™T˜]I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ëœ˜]\Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKİÚ\™\ËÜ˜]\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞUÚ\™T˜]I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ëœ˜]\Ë™\İ›ŞIÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKİÚ\™\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™UÚ\™I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKİÚ\™\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]UÚ\™I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKİÚ\™\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞUÚ\™I×JKO›˜[YJ	Ù]X˜\ÙKÚ\™\Ë™\İ›ŞIÊNÂ‚ˆËÈX™\Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKİX™\ÉËÕX™\ĞÛÛ›Û\˜Û\ÜË	Ú[™^	×JKO›˜[YJ	Ù]X˜\ÙKX™\ÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKİX™\ÉËÕX™\ĞÛÛ›Û\˜Û\ÜË	ÜİÜ™I×JKO›˜[YJ	Ù]X˜\ÙKX™\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKİX™\ËŞİX™_IËÕX™\ĞÛÛ›Û\˜Û\ÜË	İ\]I×JKO›˜[YJ	Ù]X˜\ÙKX™\Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKİX™\ËŞİX™_IËÕX™\ĞÛÛ›Û\˜Û\ÜË	Ù\İ›ŞI×JKO›˜[YJ	Ù]X˜\ÙKX™\Ë™\İ›ŞIÊNÂ‚ˆËÈİ\İÛY\œÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKØİ\İÛY\œÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Øİ\İÛY\œÉ×JKO›˜[YJ	Ù]X˜\ÙK˜İ\İÛY\œÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKØİ\İÛY\œÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™Pİ\İÛY\‰×JKO›˜[YJ	Ù]X˜\ÙK˜İ\İÛY\œËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKØİ\İÛY\œËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]Pİ\İÛY\‰×JKO›˜[YJ	Ù]X˜\ÙK˜İ\İÛY\œË\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKØİ\İÛY\œËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞPİ\İÛY\‰×JKO›˜[YJ	Ù]X˜\ÙK˜İ\İÛY\œË™\İ›ŞIÊNÂ‚ˆËÈŞXÛH[YH[\]\Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKØŞXÛK][YK][\]\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ØŞXÛU[YU[\]\É×JKO›˜[YJ	Ù]X˜\ÙK˜ŞXÛK][YK][\]\ÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKØŞXÛK][YK][\]\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™PŞXÛU[YU[\]I×JKO›˜[YJ	Ù]X˜\ÙK˜ŞXÛK][YK][\]\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKØŞXÛK][YK][\]\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]PŞXÛU[YU[\]I×JKO›˜[YJ	Ù]X˜\ÙK˜ŞXÛK][YK][\]\Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKØŞXÛK][YK][\]\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞPŞXÛU[YU[\]I×JKO›˜[YJ	Ù]X˜\ÙK˜ŞXÛK][YK][\]\Ë™\İ›ŞIÊNÂ‚ˆËÈ\Ú[™\ÜÈØ]YÛÜšY\Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKØ\Ú[™\ÜËXØ]YÛÜšY\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ø\Ú[™\ÜĞØ]YÛÜšY\É×JKO›˜[YJ	Ù]X˜\ÙK˜\Ú[™\ÜËXØ]YÛÜšY\ÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKØ\Ú[™\ÜËXØ]YÛÜšY\ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™P\Ú[™\ÜĞØ]YÛÜI×JKO›˜[YJ	Ù]X˜\ÙK˜\Ú[™\ÜËXØ]YÛÜšY\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKØ\Ú[™\ÜËXØ]YÛÜšY\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]P\Ú[™\ÜĞØ]YÛÜI×JKO›˜[YJ	Ù]X˜\ÙK˜\Ú[™\ÜËXØ]YÛÜšY\Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKØ\Ú[™\ÜËXØ]YÛÜšY\ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞP\Ú[™\ÜĞØ]YÛÜI×JKO›˜[YJ	Ù]X˜\ÙK˜\Ú[™\ÜËXØ]YÛÜšY\Ë™\İ›ŞIÊNÂ‚ˆËÈ[Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ[ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ü[É×JKO›˜[YJ	Ù]X˜\ÙKœ[ÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKÜ[ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™T[	×JKO›˜[YJ	Ù]X˜\ÙKœ[ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKÜ[ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]T[	×JKO›˜[YJ	Ù]X˜\ÙKœ[Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜ[ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT[	×JKO›˜[YJ	Ù]X˜\ÙKœ[Ë™\İ›ŞIÊNÂ‚ˆËÈPÜÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜXÜÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜXÜÉ×JKO›˜[YJ	Ù]X˜\ÙKœXÜÉÊNÂˆ›İ]NœÜİ
	ËÙ]X˜\ÙKÜXÜÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	ÜİÜ™TXÉ×JKO›˜[YJ	Ù]X˜\ÙKœXÜËœİÜ™IÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKÜXÜËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]TXÉ×JKO›˜[YJ	Ù]X˜\ÙKœXÜË\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜXÜËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞTXÉ×JKO›˜[YJ	Ù]X˜\ÙKœXÜË™\İ›ŞIÊNÂ‚ˆËÈ›Ú™XİØİ[Y[Âˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÜ›Ú™XİYØİ[Y[ÉËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ü›Ú™XİØİ[Y[É×JKO›˜[YJ	Ù]X˜\ÙKœ›Ú™XİYØİ[Y[ÉÊNÂˆ›İ]N™Ù]
	ËÙ]X˜\ÙKÙØİ[Y[\™XØ\	ËÑØİ[Y[™XØ\ÛÛ›Û\˜Û\ÜË	Ú[™^	×JKO›˜[YJ	Ù]X˜\ÙK™Øİ[Y[\™XØ\	ÊNÂˆ›İ]Nœ]
	ËÙ]X˜\ÙKÜ›Ú™XİYØİ[Y[ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	İ\]T›Ú™XİØİ[Y[	×JKO›˜[YJ	Ù]X˜\ÙKœ›Ú™XİYØİ[Y[Ë\]IÊNÂˆ›İ]N™[]J	ËÙ]X˜\ÙKÜ›Ú™XİYØİ[Y[ËŞÚYIËÑ]X˜\ÙPÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT›Ú™XİØİ[Y[	×JKO›˜[YJ	Ù]X˜\ÙKœ›Ú™XİYØİ[Y[Ë™\İ›ŞIÊNÂˆJNÂ‚ˆËÈ8¥ 8¥ S”UUH8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ ˆ›İ]N›ZY]Ø\™J	Ü\›Z\ÜÚ[Ûš[œ]Ù]IÊKO™Ü›İ\
[˜İ[Ûˆ

HÂˆËÈ›Ü›HÛÜİ[™Âˆ›İ]N™Ù]
	ËÙ›Ü›IËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	Ù›Ü›I×JKO›˜[YJ	Ù›Ü›IÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÜİÜ™IËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	ÜİÜ™I×JKO›˜[YJ	ØÛÜİ[™ËœİÜ™IÊNÂˆ›İ]N™Ù]
	ËØÛÜİ[™ËÙ˜Y	ËĞÛÜİ[™Ñ˜YÛÛ›Û\˜Û\ÜË	ÜÚİÉ×JKO›˜[YJ	ØÛÜİ[™Ë™˜YœÚİÉÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÙ˜Y	ËĞÛÜİ[™Ñ˜YÛÛ›Û\˜Û\ÜË	ÜİÜ™I×JKO›˜[YJ	ØÛÜİ[™Ë™˜YœİÜ™IÊNÂˆ›İ]N™[]J	ËØÛÜİ[™ËÙ˜Y	ËĞÛÜİ[™Ñ˜YÛÛ›Û\˜Û\ÜË	Ù\İ›ŞI×JKO›˜[YJ	ØÛÜİ[™Ë™˜Y™\İ›ŞIÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÛX]\šX[\]ZXÚË]\]IËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	Ü]ZXÚÕ\]SX]\šX[	×JKO›˜[YJ	ØÛÜİ[™Ë›X]\šX[\]ZXÚË]\]IÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÛX]\šX[\™XØ[İ[]IËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	Ü™XØ[İ[]SX]\šX[	×JKO›˜[YJ	ØÛÜİ[™Ë›X]\šX[\™XØ[İ[]IÊNÂˆ›İ]N™Ù]
	ËØÛÜİ[™ËÜİÜ™IË[˜İ[Ûˆ

HÂˆ™]\›ˆ™Y\™Xİ
›İ]J	Ù›Ü›IË×K˜[ÙJJBˆOÚ]
	İØ\›š[™ÉË	Ò[[X[ˆÚ[\[ˆYZÈš\ØHXZØH[™Üİ[™ËˆÚ[ZØ[ˆÚ[\[ˆ]H\šH›Ü›HÛÜİ[™Ë‰ÊNÂˆJKO›˜[YJ	ØÛÜİ[™ËœİÜ™K™Ù]	ÊNÂˆ›İ]N™Ù]
	ËØÛÜİ[™ËÚ[\Ü\\\İ	Ë›ˆ

HOˆ™Y\™Xİ

KOœ›İ]J	Ù›Ü›IÊJKO›˜[YJ	ØÛÜİ[™Ëš[\Ü\\\İ™Ù]	ÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÚ[\Ü\\\İ	ËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	Ú[\Ü\\İ	×JKO›˜[YJ	ØÛÜİ[™Ëš[\Ü\\\İ	ÊNÂˆ›İ]N™Ù]
	ËØÛÜİ[™ËÚ[\ÜXÛÙÛIË›ˆ

HOˆ™Y\™Xİ

KOœ›İ]J	Ù›Ü›IÊJKO›˜[YJ	ØÛÜİ[™Ëš[\ÜXÛÙÛK™Ù]	ÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÚ[\ÜXÛÙÛIËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	Ú[\ÜÛÙÛI×JKO›˜[YJ	ØÛÜİ[™Ëš[\ÜXÛÙÛIÊNÂˆ›İ]N™Ù]
	ËØÛÜİ[™ËÚ[\Ü][Z	Ë›ˆ

HOˆ™Y\™Xİ

KOœ›İ]J	Ù›Ü›IÊJKO›˜[YJ	ØÛÜİ[™Ëš[\Ü][Z™Ù]	ÊNÂˆ›İ]NœÜİ
	ËØÛÜİ[™ËÚ[\Ü][Z	ËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	Ú[\Ü[Z	×JKO›˜[YJ	ØÛÜİ[™Ëš[\Ü][Z	ÊNÂˆ›İ]N›X]Ú
ÉÜÜİ	Ë	Ü]Ú	×K	ËØÛÜİ[™ËÜİ]\Ë\›Ú™XİŞÜ™]š\Ú[Û’YIËĞÛÜİ[™ĞÛÛ›Û\˜Û\ÜË	İ\]Tİ]\Ô›Ú™Xİ	×JKO›˜[YJ	ØÛÜİ[™Ëœİ]\Ë\›Ú™Xİ\]IÊNÂ‚ˆËÈØİ[Y[™XÙZ\Âˆ›İ]N™Ù]
	ËÙØİ[Y[\™XÙZ\ÉËÑØİ[Y[™XÙZ\ÛÛ›Û\˜Û\ÜË	Ú[™^	×JKO›˜[YJ	ÙØİ[Y[\™XÙZ\Ëš[™^	ÊNÂˆ›İ]NœÜİ
	ËÙØİ[Y[\™XÙZ\ÉËÑØİ[Y[™XÙZ\ÛÛ›Û\˜Û\ÜË	ÜİÜ™I×JKO›˜[YJ	ÙØİ[Y[\™XÙZ\ËœİÜ™IÊNÂˆ›İ]N™Ù]
	ËÙØİ[Y[\™XÙZ\ËŞÙØİ[Y[™XÙZ\KŞİ\_IËÑØİ[Y[™XÙZ\ÛÛ›Û\˜Û\ÜË	ÙİÛ›ØY	×JBˆOÚ\™J	İ\IË	Ü\\İ[Z	ÊBˆO›˜[YJ	ÙØİ[Y[\™XÙZ\Ë™İÛ›ØY	ÊNÂ‚ˆËÈ˜XÚÚ[™ÈØİ[Y[È
›Ú™Xİ
Bˆ›İ]N™Ù]
	Ëİ˜XÚÚ[™ËYØİ[Y[ËÛ™]ÉËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	ØÜ™X]I×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë˜Ü™X]IÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËÜ™XÙZ\	ËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	ÜİÜ™T™XÙZ\	×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[ËœİÜ™K\™XÙZ\	ÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKØY]™\œÚ[Û‰ËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	ØY™\œÚ[Û‰×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë˜Y]™\œÚ[Û‰ÊNÂˆ›İ]N™[]J	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKÙ[]K]™\œÚ[Û‰ËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	Ù[]U™\œÚ[Û‰×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë™[]K]™\œÚ[Û‰ÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKÜ›ØÙ\ÜËY›Ü›KZ[œ]	ËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	Ü›ØÙ\ÜÕÑ›Ü›R[œ]	×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ëœ›ØÙ\ÜËY›Ü›KZ[œ]	ÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKİ\]KYš[\ÉËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	İ\]Qš[\É×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë\]KYš[\ÉÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ›Ú™XİKİ\]K\›Ú™XİZ[™›ÉËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	İ\]T›Ú™Xİ[™›É×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë\]K\›Ú™XİZ[™›ÉÊNÂˆ›İ]N™[]J	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ›Ú™XİIËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT›Ú™Xİ	×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë™\İ›ŞK\›Ú™Xİ	ÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKİ[œšXÙY\šXÙIËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	İ\]U[œšXÙY\šXÙI×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë\]K][œšXÙY\šXÙIÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKİ[œšXÙYY[]IËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	Ù[]U[œšXÙY\	×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë™[]K][œšXÙY\\	ÊNÂˆ›İ]NœÜİ
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKİ[œšXÙYX[ËY[]IËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	Ø[Ñ[]U[œšXÙY\É×JKO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë˜[ËY[]K][œšXÙY\\ÉÊNÂˆ›İ]N™Ù]
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKŞİ\_IËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	ÙİÛ›ØY	×JBˆOÚ\™J	İ\IË	Ü\\İ[ZLLLIÊBˆO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë™İÛ›ØY	ÊNÂˆ›İ]N™Ù]
	Ëİ˜XÚÚ[™ËYØİ[Y[ËŞÜ™]š\Ú[ÛŸKÙ^Ü][œšXÙYŞÙ›Ü›X]IËÕ˜XÚÚ[™ÑØİ[Y[ÛÛ›Û\˜Û\ÜË	Ù^Ü[œšXÙY\É×JBˆOÚ\™J	Ù›Ü›X]	Ë	Ù^Ù[‰ÊBˆO›˜[YJ	İ˜XÚÚ[™ËYØİ[Y[Ë™^Ü][œšXÙY	ÊNÂˆJNÂ‚ˆËÈ8¥ 8¥ TÑTˆPSQÑSQS•
YZ[ˆÛ›JH8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ 8¥ ˆ›İ]N›ZY]Ø\™J	Ü\›Z\ÜÚ[Û\Ù\—ÛX[˜YÙ[Y[	ÊKO™Ü›İ\
[˜İ[Ûˆ

HÂˆ›İ]N™Ù]
	ËÜ\›Z\ÜÚ[ÛœÉËĞ]]ÛÛ›Û\˜Û\ÜË	Ü\›Z\ÜÚ[ÛœÉ×JKO›˜[YJ	Ü\›Z\ÜÚ[ÛœÉÊNÂˆ›İ]NœÜİ
	ËÜ\›Z\ÜÚ[ÛœÉËĞ]]ÛÛ›Û\˜Û\ÜË	ÜİÜ™U\Ù\‰×JKO›˜[YJ	Ü\›Z\ÜÚ[ÛœËœİÜ™IÊNÂˆ›İ]NœÜİ
	ËÜ\›Z\ÜÚ[ÛœËİ\]KXXØÙ\ÜÉËĞ]]ÛÛ›Û\˜Û\ÜË	İ\]T\›Z\ÜÚ[Û‰×JKO›˜[YJ	Ü\›Z\ÜÚ[ÛœË\]KXXØÙ\ÜÉÊNÂˆ›İ]Nœ]
	ËÜ\›Z\ÜÚ[ÛœËŞÚYIËĞ]]ÛÛ›Û\˜Û\ÜË	İ\]U\Ù\‰×JKO›˜[YJ	Ü\›Z\ÜÚ[ÛœË\]IÊNÂˆ›İ]N™[]J	ËÜ\›Z\ÜÚ[ÛœËŞÚYIËĞ]]ÛÛ›Û\˜Û\ÜË	Ù\İ›ŞU\Ù\‰×JKO›˜[YJ	Ü\›Z\ÜÚ[ÛœË™\İ›ŞIÊNÂ‚ˆ›İ]N™Ù]
	ËØ\ÜÚ\İ[]˜Z[š[™ÉËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	Ú[™^	×JKO›˜[YJ	Ø\ÜÚ\İ[˜Z[š[™ÉÊNÂˆ›İ]NœÜİ
	ËØ\ÜÚ\İ[]˜Z[š[™ËİÜXÜÉËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	ÜİÜ™UÜXÉ×JKO›˜[YJ	Ø\ÜÚ\İ[ÜXÜËœİÜ™IÊNÂˆ›İ]Nœ]
	ËØ\ÜÚ\İ[]˜Z[š[™ËİÜXÜËŞİÜXßIËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	İ\]UÜXÉ×JKO›˜[YJ	Ø\ÜÚ\İ[ÜXÜË\]IÊNÂˆ›İ]N™[]J	ËØ\ÜÚ\İ[]˜Z[š[™ËİÜXÜËŞİÜXßIËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	Ù\İ›ŞUÜXÉ×JKO›˜[YJ	Ø\ÜÚ\İ[ÜXÜË™\İ›ŞIÊNÂˆ›İ]NœÜİ
	ËØ\ÜÚ\İ[]˜Z[š[™ËÜ[\ÉËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	ÜİÜ™T[I×JKO›˜[YJ	Ø\ÜÚ\İ[œ[\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËØ\ÜÚ\İ[]˜Z[š[™ËÜ[\ËŞÜ[_IËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	İ\]T[I×JKO›˜[YJ	Ø\ÜÚ\İ[œ[\Ë\]IÊNÂˆ›İ]N™[]J	ËØ\ÜÚ\İ[]˜Z[š[™ËÜ[\ËŞÜ[_IËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	Ù\İ›ŞT[I×JKO›˜[YJ	Ø\ÜÚ\İ[œ[\Ë™\İ›ŞIÊNÂˆ›İ]NœÜİ
	ËØ\ÜÚ\İ[]˜Z[š[™Ëİ[\]\ÉËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	ÜİÜ™U[\]I×JKO›˜[YJ	Ø\ÜÚ\İ[[\]\ËœİÜ™IÊNÂˆ›İ]Nœ]
	ËØ\ÜÚ\İ[]˜Z[š[™Ëİ[\]\ËŞİ[\]_IËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	İ\]U[\]I×JKO›˜[YJ	Ø\ÜÚ\İ[[\]\Ë\]IÊNÂˆ›İ]N™[]J	ËØ\ÜÚ\İ[]˜Z[š[™Ëİ[\]\ËŞİ[\]_IËĞÛÜİ[™Ğ\ÜÚ\İ[ÛÛ›Û\˜Û\ÜË	Ù\İ›ŞU[\]I×JKO›˜[YJ	Ø\ÜÚ\İ[[\]\Ë™\İ›ŞIÊNÂˆJNÂŸJNÂ