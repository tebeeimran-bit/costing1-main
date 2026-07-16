@php
    $tourContext = $tourContext ?? 'main';
    $tourNextUrl = $tourNextUrl ?? null;
@endphp

<div id="costing-guided-tour"
    class="costing-tour is-hidden"
    data-context="{{ $tourContext }}"
    @if($tourNextUrl) data-next-url="{{ $tourNextUrl }}" @endif
    aria-hidden="true">
    <div class="costing-tour-backdrop"></div>
    <div class="costing-tour-spotlight" aria-hidden="true"></div>
    <section class="costing-tour-card" role="dialog" aria-modal="true" aria-labelledby="costing-tour-title">
        <div class="costing-tour-progress">
            <span class="costing-tour-eyebrow">PANDUAN APLIKASI</span>
            <span id="costing-tour-counter"></span>
        </div>
        <h2 id="costing-tour-title"></h2>
        <p id="costing-tour-description"></p>
        <div class="costing-tour-actions">
            <button type="button" class="costing-tour-button ghost" data-tour-action="skip">Lewati</button>
            <div class="costing-tour-navigation">
                <button type="button" class="costing-tour-button secondary" data-tour-action="previous">Sebelumnya</button>
                <button type="button" class="costing-tour-button primary" data-tour-action="next">Selanjutnya</button>
            </div>
        </div>
    </section>
</div>

<style>
    .costing-tour.is-hidden { display: none; }
    .costing-tour { position: fixed; inset: 0; z-index: 12000; font-family: 'Inter', sans-serif; }
    .costing-tour-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .68); }
    .costing-tour-spotlight {
        position: fixed; border: 3px solid #60a5fa; border-radius: 14px;
        box-shadow: 0 0 0 5px rgba(96, 165, 250, .22), 0 16px 42px rgba(15, 23, 42, .28);
        background: rgba(255, 255, 255, .08); pointer-events: none;
        transition: top .2s ease, left .2s ease, width .2s ease, height .2s ease;
    }
    .costing-tour-card {
        position: fixed; width: min(390px, calc(100vw - 28px)); padding: 1.2rem;
        border: 1px solid #dbeafe; border-radius: 16px; background: #fff;
        color: #0f172a; box-shadow: 0 24px 65px rgba(15, 23, 42, .34);
        overflow-y: auto;
    }
    .costing-tour-progress { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: .7rem; }
    .costing-tour-eyebrow { color: #2563eb; font-size: .7rem; font-weight: 850; letter-spacing: .09em; }
    #costing-tour-counter { color: #64748b; font-size: .75rem; font-weight: 700; }
    .costing-tour-card h2 { margin: 0 0 .5rem; color: #0f172a; font-size: 1.12rem; line-height: 1.3; }
    .costing-tour-card p { margin: 0; color: #475569; font-size: .88rem; line-height: 1.65; }
    .costing-tour-actions { display: flex; align-items: center; justify-content: space-between; gap: .7rem; margin-top: 1.1rem; }
    .costing-tour-navigation { display: flex; gap: .5rem; }
    .costing-tour-button {
        min-height: 38px; padding: 0 .85rem; border-radius: 9px; border: 1px solid transparent;
        cursor: pointer; font: inherit; font-size: .78rem; font-weight: 800;
    }
    .costing-tour-button.ghost { padding-left: .25rem; padding-right: .25rem; color: #64748b; background: transparent; }
    .costing-tour-button.secondary { color: #334155; background: #f8fafc; border-color: #cbd5e1; }
    .costing-tour-button.primary { color: #fff; background: #2563eb; box-shadow: 0 7px 16px rgba(37, 99, 235, .24); }
    .costing-tour-button:disabled { display: none; }
    body.costing-tour-active { overflow: hidden; }
    @media (max-width: 640px) {
        .costing-tour-actions { align-items: flex-end; }
        .costing-tour-navigation { flex-wrap: wrap; justify-content: flex-end; }
    }
</style>

<script>
(() => {
    const root = document.getElementById('costing-guided-tour');
    if (!root) return;

    // The application body uses CSS zoom: 0.8. Keep the tour outside that
    // coordinate system so getBoundingClientRect() maps 1:1 to the overlay.
    document.documentElement.appendChild(root);

    const context = root.dataset.context;
    const nextUrl = root.dataset.nextUrl || '';
    const routeName = @json((string) (Route::currentRouteName() ?? ''));
    const userKey = @json((string) (auth()->id() ?? 'guest'));
    const completionKey = `costing-guided-tour-v1-completed-${userKey}`;
    const pageCompletionKey = `costing-page-tour-v1-completed-${userKey}-${routeName}`;
    const continuationKey = `costing-guided-tour-v1-continuation-${userKey}`;
    const card = root.querySelector('.costing-tour-card');
    const spotlight = root.querySelector('.costing-tour-spotlight');
    const title = root.querySelector('#costing-tour-title');
    const description = root.querySelector('#costing-tour-description');
    const counter = root.querySelector('#costing-tour-counter');
    const eyebrow = root.querySelector('.costing-tour-eyebrow');
    const previousButton = root.querySelector('[data-tour-action="previous"]');
    const nextButton = root.querySelector('[data-tour-action="next"]');
    let steps = [];
    let currentIndex = 0;
    let active = false;
    let activeMode = 'menu';

    const selectionSteps = [
        {
            selector: '[data-tour="costing-project"]',
            title: 'Mulai dari Costing Project',
            description: 'Pilih Costing Project untuk mengelola project, dokumen, proses costing, database master, approval, dan laporan.'
        }
    ];

    const mainSteps = [
        { selector: '[data-tour="dashboard"]', title: 'Dashboard', description: 'Pantau ringkasan project, progress costing, status dokumen, approval, dan data penting lainnya dari halaman ini.' },
        { selector: '[data-tour="my-tasks"]', title: 'My Tasks', description: 'View the work that needs your attention. The task list automatically follows your role and workflow status.' },
        { selector: '[data-productivity-open]', title: 'Pencarian Cepat', description: 'Cari project, part, customer, dan material atau buka aksi cepat dengan Ctrl+K.' },
        { selector: '[data-tour="help-center"]', title: 'Help Center', description: 'Pelajari workflow, autosave, validasi, pencarian, shortcut, dan pertanyaan yang sering diajukan.' },
        { selector: '[data-tour="project"]', title: 'Project', description: 'Mulai pekerjaan di sini: buat project, unggah dokumen engineering, kelola revisi, lalu proses data menuju form costing.' },
        { selector: '[data-tour="project-document"]', title: 'Project Document', description: 'Lihat rekap, status, dan revisi seluruh dokumen project yang tersimpan di sistem.' },
        { selector: '[data-tour="cogm-resume"]', title: 'COGM Resume Analysis', description: 'Periksa rangkuman material, process, overhead, administration cost, total COGM, dan status approval.' },
        { selector: '[data-tour="marketing-inbox"]', title: 'Marketing COGM Inbox', description: 'Pantau hasil costing yang telah dikirim kepada Marketing untuk ditinjau dan ditindaklanjuti.' },
        { selector: '[data-tour="trend-analysis"]', title: 'Document Trend Analysis', description: 'Analisis perkembangan dokumen, status project, serta project canceled atau failed berdasarkan periode.' },
        { selector: '[data-tour="compare-costing"]', title: 'Compare Costing', description: 'Bandingkan komponen dan hasil costing antarproject, produk, atau revisi.' },
        { selector: '[data-tour="database"]', title: 'Database Master', description: 'Kelola Part, Wire, Tubes, Customer, Business Category, Plant, PIC, Cycle Time, Rate & Kurs, serta Unpriced Parts.' },
        { selector: '[data-tour="reports"]', title: 'Laporan & Export', description: 'Buka laporan operasional dan ekspor data costing sesuai kebutuhan.' },
        { selector: '[data-tour="sla-performance"]', title: 'SLA Performance', description: 'Pantau kepatuhan deadline, pekerjaan overdue, aging, dan performa setiap PIC.' },
        { selector: '[data-tour="operations-center"]', title: 'Operations Center', description: 'Kelola checklist kesiapan rilis, kalender hari kerja SLA, serta backup dan restore terverifikasi.' },
        { selector: '[data-tour="system-center"]', title: 'System Center', description: 'Pantau error, performa, keamanan login, announcement, dan delegasi approval.' },
        { selector: '[data-tour="export-center"]', title: 'Export Center', description: 'Buat dan jadwalkan export data serta unduh kembali dari riwayat.' },
        { selector: '[data-tour="administration"]', title: 'Administrasi', description: 'Khusus Admin: atur permission pengguna dan materi Costing Assistant melalui Assistant Training.' },
        { selector: '[data-tour="account"]', title: 'Akun Pengguna', description: 'Lihat identitas dan role pengguna, buka profil, atau keluar dari aplikasi melalui bagian ini.' }
    ];

    const pageGuides = {
        'help-center': [
            { selector: '.help-hero', title: 'Cari Bantuan', description: 'Masukkan topik untuk menyaring seluruh materi bantuan yang tersedia.' },
            { selector: '.help-shortcuts', title: 'Shortcut Keyboard', description: 'Gunakan shortcut ini untuk berpindah halaman dan mencari data lebih cepat.' },
            { selector: '.help-workflow', title: 'Alur Kerja Utama', description: 'Ikuti urutan dari Project sampai Marketing agar tidak ada tahap yang terlewat.' },
            { selector: '.help-sla-explainer', title: 'Deadline, SLA & Aging', description: 'Pelajari arti setiap indikator waktu, cara menghitung keterlambatan, dan SLA default setiap tahap workflow.' },
            { selector: '.help-guides', title: 'Panduan Fitur', description: 'Baca petunjuk praktis untuk tugas, autosave, validasi, pencarian, favorit, dan tur interaktif.' },
            { selector: '.help-faq', title: 'Pertanyaan Umum', description: 'Temukan jawaban mengenai approval, autosave, dan hak akses.' }
        ],
        'my-tasks': [
            { selector: '.header-title', title: 'My Tasks', description: 'This page gathers active work that matches your role.' },
            { selector: '.task-hero', title: 'Ringkasan Pekerjaan', description: 'Lihat jumlah tugas aktif dan tujuan halaman kerja personal Anda.' },
            { selector: '.task-filters', title: 'Filter Tahapan', description: 'Saring pekerjaan berdasarkan Dokumen, Harga Part, Costing, Approval, atau Marketing.' },
            { selector: '.task-card', title: 'Task Details', description: 'Each card shows its priority, project, progress, status, and next action.' },
            { selector: '.task-deadline', title: 'Deadline & SLA', description: 'Review the due date, remaining time, and task aging. Overdue work is highlighted in red.' },
            { selector: '.task-completeness', title: 'Kelengkapan Data', description: 'Skor ini menunjukkan persentase data yang sudah lengkap dan jumlah item yang masih harus diperbaiki.' },
            { selector: '.task-action', title: 'Buka Tugas', description: 'Klik tombol ini untuk langsung menuju halaman tempat pekerjaan diselesaikan.' }
        ],
        'project-collaboration.show': [
            { selector: '.collab-hero', title: 'Project Workspace', description: 'Confirm the part, customer, model, and revision before collaborating.' },
            { selector: '.collab-kpis', title: 'Workflow & SLA', description: 'Review progress, status, deadline, remaining time, and aging.' },
            { selector: '.collab-missing', title: 'Data Belum Lengkap', description: 'Gunakan tautan pada setiap item untuk melengkapi data dan menaikkan skor hingga 100%.' },
            { selector: '.deadline-panel', title: 'Custom Deadline', description: 'Set a custom due date or leave it empty to use the default workflow SLA.' },
            { selector: '.activity-panel', title: 'Activity Timeline', description: 'See who changed the revision, costing, deadline, or workflow status and when.' },
            { selector: '.comment-panel', title: 'Comments & Mentions', description: 'Discuss the project and mention teammates with their @handle.' }
        ],
        'notifications.index': [
            { selector: '.notification-hero', title: 'Notification Center', description: 'Lihat jumlah notifikasi yang belum dibaca dan pekerjaan yang membutuhkan perhatian.' },
            { selector: '.notification-list', title: 'Notifikasi Aktif', description: 'Buka tindakan terkait atau sembunyikan notifikasi yang tidak lagi diperlukan.' },
            { selector: '.preferences', title: 'Preferensi Notifikasi', description: 'Pilih kategori notifikasi yang ingin ditampilkan pada akun Anda.' }
        ],
        dashboard: [
            { selector: '.header-title', title: 'Dashboard Costing', description: 'Halaman ini merangkum kondisi project dan hasil costing yang sudah tersimpan.' },
            { selector: '.dashboard-filter-card', title: 'Filter Dashboard', description: 'Pilih periode, business category, customer, dan model, lalu tekan Terapkan untuk memperbarui analisis.' },
            { selector: '.kpi-grid', title: 'Indikator Utama', description: 'Kartu ini menunjukkan jumlah project tracking, project yang sudah costing, serta status A00, A04, dan A05.' },
            { selector: '.bottom-grid', title: 'Ringkasan Status dan Potensi', description: 'Gunakan bagian ini untuk melihat distribusi status project, potensial cost, dan customer utama.' },
            { selector: '.charts-grid', title: 'Grafik Costing', description: 'Grafik membantu membandingkan jumlah project dan komposisi material, labor, serta overhead.' },
            { selector: '#detailCostingTable', title: 'Detail Costing', description: 'Tabel ini berisi detail setiap costing. Gunakan pencarian, pagination, dan kontrol status untuk menelusuri data.' }
        ],
        project: [
            { selector: '.header-title', title: 'Daftar Project', description: 'Halaman Project digunakan untuk membuat, mencari, membuka, dan memantau seluruh project costing.' },
            { selector: '.project-toolbar', title: 'Cari atau Buat Project', description: 'Cari berdasarkan project, customer, model, atau part number. Klik New Project untuk membuat project baru.' },
            { selector: '.project-table', title: 'Informasi Project', description: 'Tabel menampilkan identitas project, PIC, jumlah part, status proses, dan waktu pembaruan terakhir.' },
            { selector: '.workflow-summary', title: 'Progress Workflow', description: 'Progress menunjukkan berapa tahap yang sudah selesai dan berapa item yang masih memerlukan tindakan.' },
            { selector: '.expand-btn', title: 'Lihat Detail Part', description: 'Klik tombol panah untuk membuka daftar part number, revisi dokumen, status costing, dan status approval.' },
            { selector: '.bulk-group-toggle', title: 'Bulk Actions', description: 'Centang revisi yang ingin diproses bersama untuk memperbarui deadline, PIC, atau export CSV.' },
            { selector: '.action-stack', title: 'Aksi Project', description: 'Gunakan aksi di kolom kanan untuk menambah project, melihat part, membuka dokumen, atau melanjutkan proses costing.' }
        ],
        'database.project-documents': [
            { selector: '.header-title', title: 'Project Document', description: 'Halaman ini memusatkan status dan kelengkapan dokumen engineering setiap project.' },
            { selector: '.doc-summary-cards', title: 'Ringkasan Status Dokumen', description: 'Kartu A00, A04, dan A05 menunjukkan jumlah project berdasarkan status terakhir.' },
            { selector: '.engineering-doc-panel', title: 'Dokumen Engineering', description: 'Periksa partlist, drawing, quotation, dan dokumen engineering lain beserta status kelengkapannya.' },
            { selector: ['.engineering-doc-actions', '.btn-folder-storage'], title: 'Penyimpanan Dokumen', description: 'Buka folder dokumen untuk melihat susunan file berdasarkan business category, customer, model, dan revisi.' },
            { selector: ['.project-document-table', '.data-table', 'table'], title: 'Daftar Dokumen Project', description: 'Gunakan tabel untuk mencari, memeriksa, memperbarui, atau menghapus data dokumen sesuai hak akses.' }
        ],
        'resume-cogm': [
            { selector: '.header-title', title: 'COGM Resume Analysis', description: 'Halaman ini menyajikan rangkuman dan analisis hasil COGM seluruh project.' },
            { selector: '.resume-analytics-grid', title: 'Grafik Perkembangan COGM', description: 'Amati perubahan nilai COGM dan komposisi material, labor, serta overhead per periode.' },
            { selector: '.resume-insight-card', title: 'Insight Utama', description: 'Bagian insight merangkum temuan penting yang perlu diperhatikan dari data costing.' },
            { selector: '.resume-two-column', title: 'Ringkasan dan Detail', description: 'Bandingkan ringkasan per customer dengan detail COGM setiap project.' },
            { selector: '.project-table', title: 'Detail COGM Project', description: 'Klik nomor assy untuk membuka Form Costing dan periksa status harga, forecast, serta potensial cost.' }
        ],
        'marketing.cogm-inbox': [
            { selector: '.header-title', title: 'Marketing COGM Inbox', description: 'Halaman ini menampilkan COGM approved yang sudah dikirim kepada Marketing.' },
            { selector: '.marketing-inbox-header', title: 'Ringkasan Inbox', description: 'Header menunjukkan fungsi inbox dan jumlah data yang siap ditinjau.' },
            { selector: '.marketing-inbox-table', title: 'Daftar COGM Masuk', description: 'Periksa project, customer, assy, nilai COGM, pengirim, waktu pengiriman, dan status submission.' }
        ],
        'analisis-tren': [
            { selector: '.header-title', title: 'Document Trend Analysis', description: 'Gunakan halaman ini untuk menganalisis alur project dan performa dokumen engineering.' },
            { selector: ['.trend-filter-panel', '.trend-page form'], title: 'Filter Periode', description: 'Atur periode analisis agar seluruh KPI dan grafik mengikuti rentang data yang dibutuhkan.' },
            { selector: ['.trend-kpi-grid', '.trend-card'], title: 'KPI Status Project', description: 'Kartu KPI merangkum project A00, A04, A05, cancellation rate, dan conversion rate.' },
            { selector: '.trend-main-grid', title: 'Funnel Project', description: 'Funnel menunjukkan alur project masuk, tidak lanjut, dan berhasil masuk tahap costing.' },
            { selector: '.engineering-summary-grid', title: 'Dokumen dan Insight', description: 'Periksa progress dokumen engineering serta insight yang dihasilkan dari tren project.' }
        ],
        'compare.costing': [
            { selector: '.header-title', title: 'Compare Costing', description: 'Halaman ini membandingkan dua assy atau revisi costing secara berdampingan.' },
            { selector: '#compareFilterForm', title: 'Pilih Data Pembanding', description: 'Pilih business category, customer, model, Assy A, dan Assy B, lalu klik Bandingkan Revisi.' },
            { selector: '.kpi-grid', title: 'Identitas Pembanding', description: 'Pastikan Assy A dan Assy B yang ditampilkan sudah sesuai sebelum membaca hasil.' },
            { selector: '.card', title: 'Resume Perbandingan', description: 'Lihat selisih COGM dan komponen biaya utama antara kedua assy.' },
            { selector: '.material-compare-wrap', title: 'Material vs Material', description: 'Tabel membandingkan kebutuhan, harga, supplier, pajak, dan total material pada kedua costing.' }
        ],
        laporan: [
            { selector: '.header-title', title: 'Laporan & Export', description: 'Halaman ini merangkum data costing untuk kebutuhan laporan operasional.' },
            { selector: '.lap-grid', title: 'Rekap Utama', description: 'Bandingkan jumlah project dan komponen biaya berdasarkan customer serta business category.' },
            { selector: '.lap-grid .card', title: 'Rekap per Customer', description: 'Bagian ini menunjukkan material, labor, overhead, dan total COGM setiap customer.' },
            { selector: '.main-content > .card', title: 'Komposisi Biaya', description: 'Gunakan komposisi biaya untuk melihat proporsi komponen costing pada laporan.' }
        ],
        'sla-performance': [
            { selector: '.sla-hero', title: 'SLA Performance', description: 'Dashboard ini menampilkan kondisi kepatuhan SLA untuk seluruh pekerjaan aktif.' },
            { selector: '.sla-kpis', title: 'Indikator Utama', description: 'Lihat jumlah pekerjaan aktif, persentase sesuai SLA, overdue, dan rata-rata aging.' },
            { selector: '.sla-stage-grid', title: 'Performa per Tahap', description: 'Temukan tahap workflow yang paling banyak terlambat dan memiliki aging tertinggi.' },
            { selector: '.sla-filters', title: 'Filter Pekerjaan', description: 'Saring pekerjaan berdasarkan tahap atau status SLA untuk menentukan prioritas.' },
            { selector: '.sla-pic-list', title: 'Performa PIC', description: 'Bandingkan beban aktif dan overdue setiap PIC untuk membantu koordinasi.' }
        ],
        permissions: [
            { selector: '.header-title', title: 'Permission', description: 'Khusus Admin: kelola role, hak akses modul, dan akun pengguna.' },
            { selector: '.main-content .card:first-child', title: 'Role dan Hak Akses', description: 'Tentukan tingkat akses setiap role untuk Dashboard, Input Data, Database, Laporan, dan User Management.' },
            { selector: '.main-content .card:nth-of-type(2)', title: 'Daftar Pengguna', description: 'Lihat role pengguna, edit data akun, atau hapus pengguna yang tidak lagi aktif.' },
            { selector: '.main-content .btn-primary', title: 'Tambah Pengguna', description: 'Klik tombol ini untuk membuat akun baru dan menentukan role awalnya.' }
        ],
        'assistant.training': [
            { selector: '.header-title', title: 'Assistant Training', description: 'Khusus Admin: kelola pengetahuan dan aturan yang digunakan Costing Assistant.' },
            { selector: '.assistant-training-hero', title: 'Ringkasan Training', description: 'Lihat jumlah topic, rule, dan template file yang aktif.' },
            { selector: '.assistant-training-grid', title: 'Tambah Materi Assistant', description: 'Tambahkan topic jawaban, rule validasi, dan template pemeriksaan file dari bagian ini.' },
            { selector: '.assistant-training-list', title: 'Kelola Materi Tersimpan', description: 'Buka item untuk mengubah, mengaktifkan, menonaktifkan, atau menghapus materi training.' }
        ],
        'rate-kurs': [
            { selector: '.header-title', title: 'Rate & Kurs', description: 'Kelola kurs dan rate yang digunakan dalam kalkulasi costing.' },
            { selector: '.rate-cards', title: 'Rate Aktif', description: 'Periksa USD, JPY, dan LME terbaru sebelum melakukan perhitungan costing.' },
            { selector: '.main-content form', title: 'Tambah Exchange Rate', description: 'Masukkan periode, nilai kurs, LME, dan sumber data lalu simpan.' },
            { selector: '.rate-table', title: 'Riwayat Rate', description: 'Tabel menyimpan histori exchange rate dan wire rate per bulan.' }
        ],
        'unpriced-parts': [
            { selector: '.header-title', title: 'Unpriced Parts', description: 'Pantau part yang belum memiliki harga dan berpotensi menghambat penyelesaian costing.' },
            { selector: '.up-cards', title: 'Status Penyelesaian Harga', description: 'Lihat jumlah total part, part resolved, dan part yang masih unresolved.' },
            { selector: '.up-table', title: 'Daftar Part Tanpa Harga', description: 'Gunakan daftar ini untuk menemukan part, project terkait, dan status tindak lanjut harga.' }
        ],
        form: [
            { selector: '.header-title', title: 'Form Costing', description: 'Halaman ini digunakan untuk menghitung seluruh komponen biaya suatu part atau assy.' },
            { selector: ['.project-info-section', '#project-info-section', '.main-content form'], title: 'Informasi Project', description: 'Pastikan customer, model, part number, revisi, dan informasi project sudah benar.' },
            { selector: ['#material-section', '.material-section', '.material-table-container'], title: 'Material Cost', description: 'Masukkan material, kuantitas, harga, mata uang, MOQ, supplier, dan biaya impor.' },
            { selector: ['#process-section', '.process-section', '.cycle-time-section'], title: 'Process dan Cycle Time', description: 'Isi cycle time, mesin, tenaga kerja, dan parameter proses untuk menghitung labor cost.' },
            { selector: ['button[type="submit"]', '.btn-save'], title: 'Simpan Costing', description: 'Periksa kembali seluruh komponen dan total COGM sebelum menyimpan perubahan.' }
        ],
        'tracking-documents.create': [
            { selector: '.header-title', title: 'New Project', description: 'Gunakan halaman ini untuk membuat project dan menerima dokumen engineering baru.' },
            { selector: '.main-content form', title: 'Data Project', description: 'Isi business category, customer, model, nama project, PIC, dan informasi dasar lainnya.' },
            { selector: 'input[type="file"]', title: 'Upload Dokumen', description: 'Unggah partlist, drawing, quotation, dan dokumen pendukung sesuai format yang diminta.' },
            { selector: 'button[type="submit"]', title: 'Simpan Project', description: 'Periksa kembali isian dan dokumen, lalu simpan untuk memulai proses tracking.' }
        ]
    };

    const masterDataNames = {
        database: 'Database Master',
        'database.parts': 'Database Part',
        'database.wires': 'Database Wire',
        'database.tubes': 'Database Tubes',
        'database.customers': 'Database Customer',
        'database.business-categories': 'Business Categories',
        'database.plants': 'Database Plant',
        'database.pics': 'Database PIC',
        'database.cycle-time-templates': 'Cycle Time Template'
    };

    function buildPageSteps() {
        if (pageGuides[routeName]) return pageGuides[routeName];
        if (routeName === 'tracking-documents.index') return pageGuides.project;
        if (routeName === 'analisis-tren.canceled' || routeName === 'analisis-tren.engineering') return pageGuides['analisis-tren'];

        const masterName = masterDataNames[routeName];
        if (masterName) {
            return [
                { selector: '.header-title', title: masterName, description: `Halaman ini digunakan untuk mengelola ${masterName.toLowerCase()} yang dipakai oleh proses costing.` },
                { selector: ['.main-content .btn-primary', '.main-content .btn.btn-primary'], title: 'Tambah atau Import Data', description: 'Gunakan tombol aksi untuk menambahkan data baru atau mengimpor data jika fasilitas import tersedia.' },
                { selector: ['.main-content .material-table-container', '.main-content .data-table', '.main-content .card'], title: 'Daftar Master Data', description: 'Cari dan periksa data master yang sudah tersimpan sebelum digunakan pada costing.' },
                { selector: ['.main-content .btn-action', '.main-content [title="Edit"]'], title: 'Edit dan Hapus', description: 'Gunakan tombol aksi pada baris untuk memperbarui atau menghapus data sesuai hak akses.' }
            ];
        }

        return [
            { selector: '.header-title', title: 'Panduan Halaman', description: 'Judul ini menunjukkan halaman yang sedang Anda gunakan.' },
            { selector: '.main-content > *', title: 'Area Kerja', description: 'Gunakan area ini untuk melihat data dan menjalankan fungsi utama halaman.' }
        ];
    }

    function resolveTarget(selector) {
        const selectors = Array.isArray(selector) ? selector : [selector];
        for (const item of selectors) {
            for (const element of document.querySelectorAll(item)) {
                const style = window.getComputedStyle(element);
                if (style.display !== 'none' && style.visibility !== 'hidden' && element.getClientRects().length) {
                    return element;
                }
            }
        }
        return null;
    }

    function availableSteps(candidates) {
        return candidates
            .map(step => ({ ...step, target: resolveTarget(step.selector) }))
            .filter(step => step.target);
    }

    function positionCurrentStep() {
        if (!active || !steps[currentIndex]) return;
        const target = steps[currentIndex].target?.isConnected
            ? steps[currentIndex].target
            : resolveTarget(steps[currentIndex].selector);
        if (!target) return;

        target.scrollIntoView({ block: 'nearest', behavior: 'auto' });
        window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
            const rect = target.getBoundingClientRect();
            const gap = 12;
            const padding = 7;
            const spotlightTop = Math.max(6, rect.top - padding);
            const spotlightLeft = Math.max(6, rect.left - padding);
            spotlight.style.top = `${spotlightTop}px`;
            spotlight.style.left = `${spotlightLeft}px`;
            spotlight.style.width = `${Math.max(24, Math.min(window.innerWidth - spotlightLeft - 6, rect.width + (padding * 2)))}px`;
            spotlight.style.height = `${Math.max(24, Math.min(window.innerHeight - spotlightTop - 6, rect.height + (padding * 2)))}px`;

            card.style.width = '';
            card.style.maxHeight = 'calc(100vh - 28px)';
            const cardWidth = card.offsetWidth;
            const cardHeight = card.offsetHeight;
            const margin = 14;
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const visibleRect = {
                top: Math.max(margin, rect.top - padding),
                right: Math.min(viewportWidth - margin, rect.right + padding),
                bottom: Math.min(viewportHeight - margin, rect.bottom + padding),
                left: Math.max(margin, rect.left - padding)
            };
            const clamp = (value, minimum, maximum) => Math.max(minimum, Math.min(value, maximum));
            const spaces = {
                right: viewportWidth - visibleRect.right - gap - margin,
                left: visibleRect.left - gap - margin,
                below: viewportHeight - visibleRect.bottom - gap - margin,
                above: visibleRect.top - gap - margin
            };
            const isWideTarget = rect.width > viewportWidth * .48;
            const preferredSides = isWideTarget
                ? ['below', 'above', 'right', 'left']
                : ['right', 'left', 'below', 'above'];
            const fits = side => (side === 'right' || side === 'left')
                ? spaces[side] >= cardWidth
                : spaces[side] >= cardHeight;
            let side = preferredSides.find(fits);

            if (!side) {
                side = preferredSides.reduce((best, candidate) => {
                    const required = candidate === 'right' || candidate === 'left' ? cardWidth : cardHeight;
                    return (spaces[candidate] / required) > (spaces[best] / (best === 'right' || best === 'left' ? cardWidth : cardHeight))
                        ? candidate
                        : best;
                }, preferredSides[0]);
            }

            let left;
            let top;
            if (side === 'below' || side === 'above') {
                const availableHeight = Math.max(150, spaces[side]);
                card.style.maxHeight = `${Math.min(cardHeight, availableHeight)}px`;
                left = clamp(
                    rect.left + ((rect.width - cardWidth) / 2),
                    margin,
                    viewportWidth - cardWidth - margin
                );
                top = side === 'below'
                    ? visibleRect.bottom + gap
                    : visibleRect.top - Math.min(cardHeight, availableHeight) - gap;
            } else {
                left = side === 'right'
                    ? visibleRect.right + gap
                    : visibleRect.left - cardWidth - gap;
                top = clamp(
                    rect.top + ((rect.height - cardHeight) / 2),
                    margin,
                    viewportHeight - cardHeight - margin
                );
            }

            card.style.left = `${left}px`;
            card.style.top = `${top}px`;
        }));
    }

    function render() {
        const step = steps[currentIndex];
        if (!step) return finish();
        title.textContent = step.title;
        description.textContent = step.description;
        eyebrow.textContent = activeMode === 'page' ? 'PANDUAN HALAMAN' : 'PANDUAN APLIKASI';
        counter.textContent = `${currentIndex + 1} dari ${steps.length}`;
        previousButton.disabled = currentIndex === 0;
        nextButton.textContent = currentIndex === steps.length - 1
            ? (context === 'selection' ? 'Mulai Tour' : 'Selesai')
            : 'Selanjutnya';
        positionCurrentStep();
    }

    function start(options = {}) {
        activeMode = context === 'selection' ? 'menu' : (options.mode || 'page');
        const candidates = activeMode === 'page' ? buildPageSteps() : mainSteps;
        steps = availableSteps(context === 'selection' ? selectionSteps : candidates);
        if (!steps.length) return;
        currentIndex = Math.min(options.index || 0, steps.length - 1);
        active = true;
        root.classList.remove('is-hidden');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('costing-tour-active');
        render();
    }

    function close(markCompleted = true) {
        active = false;
        root.classList.add('is-hidden');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('costing-tour-active');
        localStorage.removeItem(continuationKey);
        if (markCompleted) {
            localStorage.setItem(activeMode === 'page' ? pageCompletionKey : completionKey, '1');
        }
    }

    function finish() {
        if (context === 'selection' && nextUrl) {
            localStorage.setItem(continuationKey, 'main');
            window.location.href = nextUrl;
            return;
        }
        const finishedMode = activeMode;
        close(true);
        if (context === 'main' && finishedMode === 'menu' && localStorage.getItem(pageCompletionKey) !== '1') {
            window.setTimeout(() => start({ mode: 'page' }), 350);
        }
    }

    root.addEventListener('click', event => {
        const action = event.target.closest('[data-tour-action]')?.dataset.tourAction;
        if (!action) return;
        if (action === 'skip') return close(true);
        if (action === 'previous' && currentIndex > 0) currentIndex--;
        if (action === 'next') {
            if (currentIndex >= steps.length - 1) return finish();
            currentIndex++;
        }
        render();
    });

    document.addEventListener('keydown', event => {
        if (active && event.key === 'Escape') close(true);
    });
    window.addEventListener('resize', positionCurrentStep);

    window.CostingTour = {
        start() {
            if (context === 'selection') {
                localStorage.removeItem(completionKey);
                start({ mode: 'menu' });
                return;
            }
            localStorage.removeItem(pageCompletionKey);
            start({ mode: 'page' });
        },
        startPage() {
            localStorage.removeItem(pageCompletionKey);
            start({ mode: 'page' });
        },
        startMenu() {
            localStorage.removeItem(completionKey);
            start({ mode: 'menu' });
        },
        reset() {
            localStorage.removeItem(completionKey);
            localStorage.removeItem(pageCompletionKey);
            localStorage.removeItem(continuationKey);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const completed = localStorage.getItem(completionKey) === '1';
        const continuation = localStorage.getItem(continuationKey);
        if (context === 'main' && continuation === 'main') {
            localStorage.removeItem(continuationKey);
            window.setTimeout(() => start({ mode: 'menu' }), 500);
        } else if (!completed && context === 'selection') {
            window.setTimeout(() => start({ mode: 'menu' }), 500);
        } else if (context === 'main' && localStorage.getItem(pageCompletionKey) !== '1') {
            window.setTimeout(() => start({ mode: 'page' }), 650);
        }
    });
})();
</script>
