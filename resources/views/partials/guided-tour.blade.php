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
            { selector: '.engineering-doc-panel', title: 'Dokumen Engineering', description: 'ÛM»¶‰ËkºwµçeÑ±”è€Q…µ‰… A•¹Õ¹„œ°‘•ÍÉ¥ÁÑ¥½¸è€-±¥¬Ñ½µ‰½°¥¹¤Õ¹ÑÕ¬µ•µ‰Õ…Ğ…­Õ¸‰…ÉÔ‘…¸µ•¹•¹ÑÕ­…¸É½±”…İ…±¹å„¸œô(€€€€€€€t°(€€€€€€€€…ÍÍ¥ÍÑ…¹Ğ¹ÑÉ…¥¹¥¹œœèl(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”è€ÍÍ¥ÍÑ…¹ĞQÉ…¥¹¥¹œœ°‘•ÍÉ¥ÁÑ¥½¸è€-¡ÕÍÕÌ‘µ¥¸è­•±½±„Á•¹•Ñ…¡Õ…¸‘…¸…ÑÕÉ…¸å…¹œ‘¥Õ¹…­…¸½ÍÑ¥¹œÍÍ¥ÍÑ…¹Ğ¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹…ÍÍ¥ÍÑ…¹ĞµÑÉ…¥¹¥¹œµ¡•É¼œ°Ñ¥Ñ±”è€I¥¹­…Í…¸QÉ…¥¹¥¹œœ°‘•ÍÉ¥ÁÑ¥½¸è€1¥¡…Ğ©Õµ±… Ñ½Á¥Œ°ÉÕ±”°‘…¸Ñ•µÁ±…Ñ”™¥±”å…¹œ…­Ñ¥˜¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹…ÍÍ¥ÍÑ…¹ĞµÑÉ…¥¹¥¹œµÉ¥œ°Ñ¥Ñ±”è€Q…µ‰… 5…Ñ•É¤ÍÍ¥ÍÑ…¹Ğœ°‘•ÍÉ¥ÁÑ¥½¸è€Q…µ‰…¡­…¸Ñ½Á¥Œ©…İ…‰…¸°ÉÕ±”Ù…±¥‘…Í¤°‘…¸Ñ•µÁ±…Ñ”Á•µ•É¥­Í……¸™¥±”‘…É¤‰…¥…¸¥¹¤¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹…ÍÍ¥ÍÑ…¹ĞµÑÉ…¥¹¥¹œµ±¥ÍĞœ°Ñ¥Ñ±”è€-•±½±„5…Ñ•É¤Q•ÉÍ¥µÁ…¸œ°‘•ÍÉ¥ÁÑ¥½¸è€	Õ­„¥Ñ•´Õ¹ÑÕ¬µ•¹Õ‰… °µ•¹…­Ñ¥™­…¸°µ•¹½¹…­Ñ¥™­…¸°…Ñ…Ôµ•¹¡…ÁÕÌµ…Ñ•É¤ÑÉ…¥¹¥¹œ¸œô(€€€€€€€t°(€€€€€€€€É…Ñ”µ­ÕÉÌœèl(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”è€I…Ñ”€˜-ÕÉÌœ°‘•ÍÉ¥ÁÑ¥½¸è€-•±½±„­ÕÉÌ‘…¸É…Ñ”å…¹œ‘¥Õ¹…­…¸‘…±…´­…±­Õ±…Í¤½ÍÑ¥¹œ¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹É…Ñ”µ…É‘Ìœ°Ñ¥Ñ±”è€I…Ñ”­Ñ¥˜œ°‘•ÍÉ¥ÁÑ¥½¸è€A•É¥­Í„UM°)Ad°‘…¸15Ñ•É‰…ÉÔÍ•‰•±Õ´µ•±…­Õ­…¸Á•É¡¥ÑÕ¹…¸½ÍÑ¥¹œ¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹µ…¥¸µ½¹Ñ•¹Ğ™½É´œ°Ñ¥Ñ±”è€Q…µ‰… á¡…¹”I…Ñ”œ°‘•ÍÉ¥ÁÑ¥½¸è€5…ÍÕ­­…¸Á•É¥½‘”°¹¥±…¤­ÕÉÌ°15°‘…¸ÍÕµ‰•È‘…Ñ„±…±ÔÍ¥µÁ…¸¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹É…Ñ”µÑ…‰±”œ°Ñ¥Ñ±”è€I¥İ…å…ĞI…Ñ”œ°‘•ÍÉ¥ÁÑ¥½¸è€Q…‰•°µ•¹å¥µÁ…¸¡¥ÍÑ½É¤•á¡…¹”É…Ñ”‘…¸İ¥É”É…Ñ”Á•È‰Õ±…¸¸œô(€€€€€€€t°(€€€€€€€€Õ¹ÁÉ¥•µÁ…ÉÑÌœèl(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”è€U¹ÁÉ¥•A…ÉÑÌœ°‘•ÍÉ¥ÁÑ¥½¸è€A…¹Ñ…ÔÁ…ÉĞå…¹œ‰•±Õ´µ•µ¥±¥­¤¡…É„‘…¸‰•ÉÁ½Ñ•¹Í¤µ•¹¡…µ‰…ĞÁ•¹å•±•Í…¥…¸½ÍÑ¥¹œ¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹ÕÀµ…É‘Ìœ°Ñ¥Ñ±”è€MÑ…ÑÕÌA•¹å•±•Í…¥…¸!…É„œ°‘•ÍÉ¥ÁÑ¥½¸è€1¥¡…Ğ©Õµ±… Ñ½Ñ…°Á…ÉĞ°Á…ÉĞÉ•Í½±Ù•°‘…¸Á…ÉĞå…¹œµ…Í¥ Õ¹É•Í½±Ù•¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹ÕÀµÑ…‰±”œ°Ñ¥Ñ±”è€…™Ñ…ÈA…ÉĞQ…¹Á„!…É„œ°‘•ÍÉ¥ÁÑ¥½¸è€Õ¹…­…¸‘…™Ñ…È¥¹¤Õ¹ÑÕ¬µ•¹•µÕ­…¸Á…ÉĞ°ÁÉ½©•ĞÑ•É­…¥Ğ°‘…¸ÍÑ…ÑÕÌÑ¥¹‘…¬±…¹©ÕĞ¡…É„¸œô(€€€€€€€t°(€€€€€€€™½É´èl(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”è€½É´½ÍÑ¥¹œœ°‘•ÍÉ¥ÁÑ¥½¸è€!…±…µ…¸¥¹¤‘¥Õ¹…­…¸Õ¹ÑÕ¬µ•¹¡¥ÑÕ¹œÍ•±ÕÉÕ ­½µÁ½¹•¸‰¥…å„ÍÕ…ÑÔÁ…ÉĞ…Ñ…Ô…ÍÍä¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èèlœ¹ÁÉ½©•Ğµ¥¹™¼µÍ•Ñ¥½¸œ°€œÁÉ½©•Ğµ¥¹™¼µÍ•Ñ¥½¸œ°€œ¹µ…¥¸µ½¹Ñ•¹Ğ™½É´t°Ñ¥Ñ±”è€%¹™½Éµ…Í¤AÉ½©•Ğœ°‘•ÍÉ¥ÁÑ¥½¸è€A…ÍÑ¥­…¸ÕÍÑ½µ•È°µ½‘•°°Á…ÉĞ¹Õµ‰•È°É•Ù¥Í¤°‘…¸¥¹™½Éµ…Í¤ÁÉ½©•ĞÍÕ‘… ‰•¹…È¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èèlœµ…Ñ•É¥…°µÍ•Ñ¥½¸œ°€œ¹µ…Ñ•É¥…°µÍ•Ñ¥½¸œ°€œ¹µ…Ñ•É¥…°µÑ…‰±”µ½¹Ñ…¥¹•Èt°Ñ¥Ñ±”è€5…Ñ•É¥…°½ÍĞœ°‘•ÍÉ¥ÁÑ¥½¸è€5…ÍÕ­­…¸µ…Ñ•É¥…°°­Õ…¹Ñ¥Ñ…Ì°¡…É„°µ…Ñ„Õ…¹œ°5=D°ÍÕÁÁ±¥•È°‘…¸‰¥…å„¥µÁ½È¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½ÈèlœÁÉ½•ÍÌµÍ•Ñ¥½¸œ°€œ¹ÁÉ½•ÍÌµÍ•Ñ¥½¸œ°€œ¹å±”µÑ¥µ”µÍ•Ñ¥½¸t°Ñ¥Ñ±”è€AÉ½•ÍÌ‘…¸å±”Q¥µ”œ°‘•ÍÉ¥ÁÑ¥½¸è€%Í¤å±”Ñ¥µ”°µ•Í¥¸°Ñ•¹…„­•É©„°‘…¸Á…É…µ•Ñ•ÈÁÉ½Í•ÌÕ¹ÑÕ¬µ•¹¡¥ÑÕ¹œ±…‰½È½ÍĞ¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èèl‰ÕÑÑ½¹mÑåÁ”ô‰ÍÕ‰µ¥Ğ‰tœ°€œ¹‰Ñ¸µÍ…Ù”t°Ñ¥Ñ±”è€M¥µÁ…¸½ÍÑ¥¹œœ°‘•ÍÉ¥ÁÑ¥½¸è€A•É¥­Í„­•µ‰…±¤Í•±ÕÉÕ ­½µÁ½¹•¸‘…¸Ñ½Ñ…°=4Í•‰•±Õ´µ•¹å¥µÁ…¸Á•ÉÕ‰…¡…¸¸œô(€€€€€€€t°(€€€€€€€€ÑÉ…­¥¹œµ‘½Õµ•¹ÑÌ¹É•…Ñ”œèl(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”è€9•ÜAÉ½©•Ğœ°‘•ÍÉ¥ÁÑ¥½¸è€Õ¹…­…¸¡…±…µ…¸¥¹¤Õ¹ÑÕ¬µ•µ‰Õ…ĞÁÉ½©•Ğ‘…¸µ•¹•É¥µ„‘½­Õµ•¸•¹¥¹••É¥¹œ‰…ÉÔ¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹µ…¥¸µ½¹Ñ•¹Ğ™½É´œ°Ñ¥Ñ±”è€…Ñ„AÉ½©•Ğœ°‘•ÍÉ¥ÁÑ¥½¸è€%Í¤‰ÕÍ¥¹•ÍÌ…Ñ•½Éä°ÕÍÑ½µ•È°µ½‘•°°¹…µ„ÁÉ½©•Ğ°A%°‘…¸¥¹™½Éµ…Í¤‘…Í…È±…¥¹¹å„¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€¥¹ÁÕÑmÑåÁ”ô‰™¥±”‰tœ°Ñ¥Ñ±”è€UÁ±½…½­Õµ•¸œ°‘•ÍÉ¥ÁÑ¥½¸è€U¹… Á…ÉÑ±¥ÍĞ°‘É…İ¥¹œ°ÅÕ½Ñ…Ñ¥½¸°‘…¸‘½­Õµ•¸Á•¹‘Õ­Õ¹œÍ•ÍÕ…¤™½Éµ…Ğå…¹œ‘¥µ¥¹Ñ„¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€‰ÕÑÑ½¹mÑåÁ”ô‰ÍÕ‰µ¥Ğ‰tœ°Ñ¥Ñ±”è€M¥µÁ…¸AÉ½©•Ğœ°‘•ÍÉ¥ÁÑ¥½¸è€A•É¥­Í„­•µ‰…±¤¥Í¥…¸‘…¸‘½­Õµ•¸°±…±ÔÍ¥µÁ…¸Õ¹ÑÕ¬µ•µÕ±…¤ÁÉ½Í•ÌÑÉ…­¥¹œ¸œô(€€€€€€€t(€€€ôì((€€€½¹ÍĞµ…ÍÑ•É…Ñ…9…µ•Ì€ôì(€€€€€€€‘…Ñ…‰…Í”è€…Ñ…‰…Í”5…ÍÑ•Èœ°(€€€€€€€€‘…Ñ…‰…Í”¹Á…ÉÑÌœè€…Ñ…‰…Í”A…ÉĞœ°(€€€€€€€€‘…Ñ…‰…Í”¹İ¥É•Ìœè€…Ñ…‰…Í”]¥É”œ°(€€€€€€€€‘…Ñ…‰…Í”¹ÑÕ‰•Ìœè€…Ñ…‰…Í”QÕ‰•Ìœ°(€€€€€€€€‘…Ñ…‰…Í”¹ÕÍÑ½µ•ÉÌœè€…Ñ…‰…Í”ÕÍÑ½µ•Èœ°(€€€€€€€€‘…Ñ…‰…Í”¹‰ÕÍ¥¹•ÍÌµ…Ñ•½É¥•Ìœè€	ÕÍ¥¹•ÍÌ…Ñ•½É¥•Ìœ°(€€€€€€€€‘…Ñ…‰…Í”¹Á±…¹ÑÌœè€…Ñ…‰…Í”A±…¹Ğœ°(€€€€€€€€‘…Ñ…‰…Í”¹Á¥Ìœè€…Ñ…‰…Í”A%œ°(€€€€€€€€‘…Ñ…‰…Í”¹å±”µÑ¥µ”µÑ•µÁ±…Ñ•Ìœè€å±”Q¥µ”Q•µÁ±…Ñ”œ(€€€ôì((€€€™Õ¹Ñ¥½¸‰Õ¥±‘A…•MÑ•ÁÌ ¤ì(€€€€€€€¥˜€¡Á…•Õ¥‘•ÍmÉ½ÕÑ•9…µ•t¤É•ÑÕÉ¸Á…•Õ¥‘•ÍmÉ½ÕÑ•9…µ•tì(€€€€€€€¥˜€¡É½ÕÑ•9…µ”€ôôô€ÑÉ…­¥¹œµ‘½Õµ•¹ÑÌ¹¥¹‘•àœ¤É•ÑÕÉ¸Á…•Õ¥‘•Ì¹ÁÉ½©•Ğì(€€€€€€€¥˜€¡É½ÕÑ•9…µ”€ôôô€…¹…±¥Í¥ÌµÑÉ•¸¹…¹•±•œñğÉ½ÕÑ•9…µ”€ôôô€…¹…±¥Í¥ÌµÑÉ•¸¹•¹¥¹••É¥¹œœ¤É•ÑÕÉ¸Á…•Õ¥‘•Íl…¹…±¥Í¥ÌµÑÉ•¸tì((€€€€€€€½¹ÍĞµ…ÍÑ•É9…µ”€ôµ…ÍÑ•É…Ñ…9…µ•ÍmÉ½ÕÑ•9…µ•tì(€€€€€€€¥˜€¡µ…ÍÑ•É9…µ”¤ì(€€€€€€€€€€€É•ÑÕÉ¸l(€€€€€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”èµ…ÍÑ•É9…µ”°‘•ÍÉ¥ÁÑ¥½¸è!…±…µ…¸¥¹¤‘¥Õ¹…­…¸Õ¹ÑÕ¬µ•¹•±½±„€‘íµ…ÍÑ•É9…µ”¹Ñ½1½İ•É…Í” ¥ôå…¹œ‘¥Á…­…¤½±• ÁÉ½Í•Ì½ÍÑ¥¹œ¹€ô°(€€€€€€€€€€€€€€€ìÍ•±•Ñ½Èèlœ¹µ…¥¸µ½¹Ñ•¹Ğ€¹‰Ñ¸µÁÉ¥µ…Éäœ°€œ¹µ…¥¸µ½¹Ñ•¹Ğ€¹‰Ñ¸¹‰Ñ¸µÁÉ¥µ…Éät°Ñ¥Ñ±”è€Q…µ‰… …Ñ…Ô%µÁ½ÉĞ…Ñ„œ°‘•ÍÉ¥ÁÑ¥½¸è€Õ¹…­…¸Ñ½µ‰½°…­Í¤Õ¹ÑÕ¬µ•¹…µ‰…¡­…¸‘…Ñ„‰…ÉÔ…Ñ…Ôµ•¹¥µÁ½È‘…Ñ„©¥­„™…Í¥±¥Ñ…Ì¥µÁ½ÉĞÑ•ÉÍ•‘¥„¸œô°(€€€€€€€€€€€€€€€ìÍ•±•Ñ½Èèlœ¹µ…¥¸µ½¹Ñ•¹Ğ€¹µ…Ñ•É¥…°µÑ…‰±”µ½¹Ñ…¥¹•Èœ°€œ¹µ…¥¸µ½¹Ñ•¹Ğ€¹‘…Ñ„µÑ…‰±”œ°€œ¹µ…¥¸µ½¹Ñ•¹Ğ€¹…Ét°Ñ¥Ñ±”è€…™Ñ…È5…ÍÑ•È…Ñ„œ°‘•ÍÉ¥ÁÑ¥½¸è€…É¤‘…¸Á•É¥­Í„‘…Ñ„µ…ÍÑ•Èå…¹œÍÕ‘… Ñ•ÉÍ¥µÁ…¸Í•‰•±Õ´‘¥Õ¹…­…¸Á…‘„½ÍÑ¥¹œ¸œô°(€€€€€€€€€€€€€€€ìÍ•±•Ñ½Èèlœ¹µ…¥¸µ½¹Ñ•¹Ğ€¹‰Ñ¸µ…Ñ¥½¸œ°€œ¹µ…¥¸µ½¹Ñ•¹ĞmÑ¥Ñ±”ô‰‘¥Ğ‰tt°Ñ¥Ñ±”è€‘¥Ğ‘…¸!…ÁÕÌœ°‘•ÍÉ¥ÁÑ¥½¸è€Õ¹…­…¸Ñ½µ‰½°…­Í¤Á…‘„‰…É¥ÌÕ¹ÑÕ¬µ•µÁ•É‰…ÉÕ¤…Ñ…Ôµ•¹¡…ÁÕÌ‘…Ñ„Í•ÍÕ…¤¡…¬…­Í•Ì¸œô(€€€€€€€€€€€tì(€€€€€€€ô((€€€€€€€É•ÑÕÉ¸l(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹¡•…‘•ÈµÑ¥Ñ±”œ°Ñ¥Ñ±”è€A…¹‘Õ…¸!…±…µ…¸œ°‘•ÍÉ¥ÁÑ¥½¸è€)Õ‘Õ°¥¹¤µ•¹Õ¹©Õ­­…¸¡…±…µ…¸å…¹œÍ•‘…¹œ¹‘„Õ¹…­…¸¸œô°(€€€€€€€€€€€ìÍ•±•Ñ½Èè€œ¹µ…¥¸µ½¹Ñ•¹Ğ€ø€¨œ°Ñ¥Ñ±”è€É•„-•É©„œ°‘•ÍÉ¥ÁÑ¥½¸è€Õ¹…­…¸…É•„¥¹¤Õ¹ÑÕ¬µ•±¥¡…Ğ‘…Ñ„‘…¸µ•¹©…±…¹­…¸™Õ¹Í¤ÕÑ…µ„¡…±…µ…¸¸œô(€€€€€€€tì(€€€ô((€€€™Õ¹Ñ¥½¸É•Í½±Ù•Q…É•Ğ¡Í•±•Ñ½È¤ì(€€€€€€€½¹ÍĞÍ•±•Ñ½ÉÌ€ôÉÉ…ä¹¥ÍÉÉ…ä¡Í•±•Ñ½È¤€üÍ•±•Ñ½È€èmÍ•±•Ñ½Étì(€€€€€€€™½È€¡½¹ÍĞ¥Ñ•´½˜Í•±•Ñ½ÉÌ¤ì(€€€€€€€€€€€™½È€¡½¹ÍĞ•±•µ•¹Ğ½˜‘½Õµ•¹Ğ¹ÅÕ•ÉåM•±•Ñ½É±°¡¥Ñ•´¤¤ì(€€€€€€€€€€€€€€€½¹ÍĞÍÑå±”€ôİ¥¹‘½Ü¹•Ñ½µÁÕÑ•‘MÑå±”¡•±•µ•¹Ğ¤ì(€€€€€€€€€€€€€€€¥˜€¡ÍÑå±”¹‘¥ÍÁ±…ä€„ôô€¹½¹”œ€˜˜ÍÑå±”¹Ù¥Í¥‰¥±¥Ñä€„ôô€¡¥‘‘•¸œ€˜˜•±•µ•¹Ğ¹•Ñ±¥•¹ÑI•ÑÌ ¤¹±•¹Ñ ¤ì(€€€€€€€€€€€€€€€€€€€É•ÑÕÉ¸•±•µ•¹Ğì(€€€€€€€€€€€€€€€ô(€€€€€€€€€€€ô(€€€€€€€ô(€€€€€€€É•ÑÕÉ¸¹Õ±°ì(€€€ô((€€€™Õ¹Ñ¥½¸…Ù…¥±…‰±•MÑ•ÁÌ¡…¹‘¥‘…Ñ•Ì¤ì(€€€€€€€É•ÑÕÉ¸…¹‘¥‘…Ñ•Ì(€€€€€€€€€€€€¹µ…À¡ÍÑ•À€ôø€¡ì€¸¸¹ÍÑ•À°Ñ…É•ĞèÉ•Í½±Ù•Q…É•Ğ¡ÍÑ•À¹Í•±•Ñ½È¤ô¤¤(€€€€€€€€€€€€¹™¥±Ñ•È¡ÍÑ•À€ôøÍÑ•À¹Ñ…É•Ğ¤ì(€€€ô((€€€™Õ¹Ñ¥½¸Á½Í¥Ñ¥½¹ÕÉÉ•¹ÑMÑ•À ¤ì(€€€€€€€¥˜€ ……Ñ¥Ù”ñğ€…ÍÑ•ÁÍmÕÉÉ•¹Ñ%¹‘•át¤É•ÑÕÉ¸ì(€€€€€€€½¹ÍĞÑ…É•Ğ€ôÍÑ•ÁÍmÕÉÉ•¹Ñ%¹‘•át¹Ñ…É•Ğü¹¥Í½¹¹•Ñ•(€€€€€€€€€€€€üÍÑ•ÁÍmÕÉÉ•¹Ñ%¹‘•át¹Ñ…É•Ğ(€€€€€€€€€€€€èÉ•Í½±Ù•Q…É•Ğ¡ÍÑ•ÁÍmÕÉÉ•¹Ñ%¹‘•át¹Í•±•Ñ½È¤ì(€€€€€€€¥˜€ …Ñ…É•Ğ¤É•ÑÕÉ¸ì((€€€€€€€Ñ…É•Ğ¹ÍÉ½±±%¹Ñ½Y¥•Ü¡ì‰±½¬è€¹•…É•ÍĞœ°‰•¡…Ù¥½Èè€…ÕÑ¼œô¤ì(€€€€€€€İ¥¹‘½Ü¹É•ÅÕ•ÍÑ¹¥µ…Ñ¥½¹É…µ”  ¤€ôøİ¥¹‘½Ü¹É•ÅÕ•ÍÑ¹¥µ…Ñ¥½¹É…µ”  ¤€ôøì(€€€€€€€€€€€½¹ÍĞÉ•Ğ€ôÑ…É•Ğ¹•Ñ	½Õ¹‘¥¹±¥•¹ÑI•Ğ ¤ì(€€€€€€€€€€€½¹ÍĞ…À€ô€ÄÈì(€€€€€€€€€€€½¹ÍĞÁ…‘‘¥¹œ€ô€Üì(€€€€€€€€€€€½¹ÍĞÍÁ½Ñ±¥¡ÑQ½À€ô5…Ñ ¹µ…à Ø°É•Ğ¹Ñ½À€´Á…‘‘¥¹œ¤ì(€€€€€€€€€€€½¹ÍĞÍÁ½Ñ±¥¡Ñ1•™Ğ€ô5…Ñ ¹µ…à Ø°É•Ğ¹±•™Ğ€´Á…‘‘¥¹œ¤ì(€€€€€€€€€€€ÍÁ½Ñ±¥¡Ğ¹ÍÑå±”¹Ñ½À€ô€‘íÍÁ½Ñ±¥¡ÑQ½ÁõÁá€ì(€€€€€€€€€€€ÍÁ½Ñ±¥¡Ğ¹ÍÑå±”¹±•™Ğ€ô€‘íÍÁ½Ñ±¥¡Ñ1•™ÑõÁá€ì(€€€€€€€€€€€ÍÁ½Ñ±¥¡Ğ¹ÍÑå±”¹İ¥‘Ñ €ô€‘í5…Ñ ¹µ…à ÈĞ°5…Ñ ¹µ¥¸¡İ¥¹‘½Ü¹¥¹¹•É]¥‘Ñ €´ÍÁ½Ñ±¥¡Ñ1•™Ğ€´€Ø°É•Ğ¹İ¥‘Ñ €¬€¡Á…‘‘¥¹œ€¨€È¤¤¥õÁá€ì(€€€€€€€€€€€ÍÁ½Ñ±¥¡Ğ¹ÍÑå±”¹¡•¥¡Ğ€ô€‘í5…Ñ ¹µ…à ÈĞ°5…Ñ ¹µ¥¸¡İ¥¹‘½Ü¹¥¹¹•É!•¥¡Ğ€´ÍÁ½Ñ±¥¡ÑQ½À€´€Ø°É•Ğ¹¡•¥¡Ğ€¬€¡Á…‘‘¥¹œ€¨€È¤¤¥õÁá€ì((€€€€€€€€€€€…É¹ÍÑå±”¹İ¥‘Ñ €ô€œœì(€€€€€€€€€€€…É¹ÍÑå±”¹µ…á!•¥¡Ğ€ô€…±Œ ÄÀÁÙ €´€ÈáÁà¤œì(€€€€€€€€€€€½¹ÍĞ…É‘]¥‘Ñ €ô…É¹½™™Í•Ñ]¥‘Ñ ì(€€€€€€€€€€€½¹ÍĞ…É‘!•¥¡Ğ€ô…É¹½™™Í•Ñ!•¥¡Ğì(€€€€€€€€€€€½¹ÍĞµ…É¥¸€ô€ÄĞì(€€€€€€€€€€€½¹ÍĞÙ¥•İÁ½ÉÑ]¥‘Ñ €ôİ¥¹‘½Ü¹¥¹¹•É]¥‘Ñ ì(€€€€€€€€€€€½¹ÍĞÙ¥•İÁ½ÉÑ!•¥¡Ğ€ôİ¥¹‘½Ü¹¥¹¹•É!•¥¡Ğì(€€€€€€€€€€€½¹ÍĞÙ¥Í¥‰±•I•Ğ€ôì(€€€€€€€€€€€€€€€Ñ½Àè5…Ñ ¹µ…à¡µ…É¥¸°É•Ğ¹Ñ½À€´Á…‘‘¥¹œ¤°(€€€€€€€€€€€€€€€É¥¡Ğè5…Ñ ¹µ¥¸¡Ù¥•İÁ½ÉÑ]¥‘Ñ €´µ…É¥¸°É•Ğ¹É¥¡Ğ€¬Á…‘‘¥¹œ¤°(€€€€€€€€€€€€€€€‰½ÑÑ½´è5…Ñ ¹µ¥¸¡Ù¥•İÁ½ÉÑ!•¥¡Ğ€´µ…É¥¸°É•Ğ¹‰½ÑÑ½´€¬Á…‘‘¥¹œ¤°(€€€€€€€€€€€€€€€±•™Ğè5…Ñ ¹µ…à¡µ…É¥¸°É•Ğ¹±•™Ğ€´Á…‘‘¥¹œ¤(€€€€€€€€€€€ôì(€€€€€€€€€€€½¹ÍĞ±…µÀ€ô€¡Ù…±Õ”°µ¥¹¥µÕ´°µ…á¥µÕ´¤€ôø5…Ñ ¹µ…à¡µ¥¹¥µÕ´°5…Ñ ¹µ¥¸¡Ù…±Õ”°µ…á¥µÕ´¤¤ì(€€€€€€€€€€€½¹ÍĞÍÁ…•Ì€ôì(€€€€€€€€€€€€€€€É¥¡ĞèÙ¥•İÁ½ÉÑ]¥‘Ñ €´Ù¥Í¥‰±•I•Ğ¹É¥¡Ğ€´…À€´µ…É¥¸°(€€€€€€€€€€€€€€€±•™ĞèÙ¥Í¥‰±•I•Ğ¹±•™Ğ€´…À€´µ…É¥¸°(€€€€€€€€€€€€€€€‰•±½ÜèÙ¥•İÁ½ÉÑ!•¥¡Ğ€´Ù¥Í¥‰±•I•Ğ¹‰½ÑÑ½´€´…À€´µ…É¥¸°(€€€€€€€€€€€€€€€…‰½Ù”èÙ¥Í¥‰±•I•Ğ¹Ñ½À€´…À€´µ…É¥¸(€€€€€€€€€€€ôì(€€€€€€€€€€€½¹ÍĞ¥Í]¥‘•Q…É•Ğ€ôÉ•Ğ¹İ¥‘Ñ €øÙ¥•İÁ½ÉÑ]¥‘Ñ €¨€¸Ğàì(€€€€€€€€€€€½¹ÍĞÁÉ•™•ÉÉ•‘M¥‘•Ì€ô¥Í]¥‘•Q…É•Ğ(€€€€€€€€€€€€€€€€ül‰•±½Üœ°€…‰½Ù”œ°€É¥¡Ğœ°€±•™Ğt(€€€€€€€€€€€€€€€€èlÉ¥¡Ğœ°€±•™Ğœ°€‰•±½Üœ°€…‰½Ù”tì(€€€€€€€€€€€½¹ÍĞ™¥ÑÌ€ôÍ¥‘”€ôø€¡Í¥‘”€ôôô€É¥¡ĞœñğÍ¥‘”€ôôô€±•™Ğœ¤(€€€€€€€€€€€€€€€€üÍÁ…•ÍmÍ¥‘•t€øô…É‘]¥‘Ñ (€€€€€€€€€€€€€€€€èÍÁ…•ÍmÍ¥‘•t€øô…É‘!•¥¡Ğì(€€€€€€€€€€€±•ĞÍ¥‘”€ôÁÉ•™•ÉÉ•‘M¥‘•Ì¹™¥¹¡™¥ÑÌ¤ì((€€€€€€€€€€€¥˜€ …Í¥‘”¤ì(€€€€€€€€€€€€€€€Í¥‘”€ôÁÉ•™•ÉÉ•‘M¥‘•Ì¹É•‘Õ” ¡‰•ÍĞ°…¹‘¥‘…Ñ”¤€ôøì(€€€€€€€€€€€€€€€€€€€½¹ÍĞÉ•ÅÕ¥É•€ô…¹‘¥‘…Ñ”€ôôô€É¥¡Ğœñğ…¹‘¥‘…Ñ”€ôôô€±•™Ğœ€ü…É‘]¥‘Ñ €è…É‘!•¥¡Ğì(€€€€€€€€€€€€€€€€€€€É•ÑÕÉ¸€¡ÍÁ…•Ím…¹‘¥‘…Ñ•t€¼É•ÅÕ¥É•¤€ø€¡ÍÁ…•Ím‰•ÍÑt€¼€¡‰•ÍĞ€ôôô€É¥¡Ğœñğ‰•ÍĞ€ôôô€±•™Ğœ€ü…É‘]¥‘Ñ €è…É‘!•¥¡Ğ¤¤(€€€€€€€€€€€€€€€€€€€€€€€€ü…¹‘¥‘…Ñ”(€€€€€€€€€€€€€€€€€€€€€€€€è‰•ÍĞì(€€€€€€€€€€€€€€€ô°ÁÉ•™•ÉÉ•‘M¥‘•ÍlÁt¤ì(€€€€€€€€€€€ô((€€€€€€€€€€€±•Ğ±•™Ğì(€€€€€€€€€€€±•ĞÑ½Àì(€€€€€€€€€€€¥˜€¡Í¥‘”€ôôô€‰•±½ÜœñğÍ¥‘”€ôôô€…‰½Ù”œ¤ì(€€€€€€€€€€€€€€€½¹ÍĞ…Ù…¥±…‰±•!•¥¡Ğ€ô5…Ñ ¹µ…à ÄÔÀ°ÍÁ…•ÍmÍ¥‘•t¤ì(€€€€€€€€€€€€€€€…É¹ÍÑå±”¹µ…á!•¥¡Ğ€ô€‘í5…Ñ ¹µ¥¸¡…É‘!•¥¡Ğ°…Ù…¥±…‰±•!•¥¡Ğ¥õÁá€ì(€€€€€€€€€€€€€€€±•™Ğ€ô±…µÀ (€€€€€€€€€€€€€€€€€€€É•Ğ¹±•™Ğ€¬€ ¡É•Ğ¹İ¥‘Ñ €´…É‘]¥‘Ñ ¤€¼€È¤°(€€€€€€€€€€€€€€€€€€€µ…É¥¸°(€€€€€€€€€€€€€€€€€€€Ù¥•İÁ½ÉÑ]¥‘Ñ €´…É‘]¥‘Ñ €´µ…É¥¸(€€€€€€€€€€€€€€€€¤ì(€€€€€€€€€€€€€€€Ñ½À€ôÍ¥‘”€ôôô€‰•±½Üœ(€€€€€€€€€€€€€€€€€€€€üÙ¥Í¥‰±•I•Ğ¹‰½ÑÑ½´€¬…À(€€€€€€€€€€€€€€€€€€€€èÙ¥Í¥‰±•I•Ğ¹Ñ½À€´5…Ñ ¹µ¥¸¡…É‘!•¥¡Ğ°…Ù…¥±…‰±•!•¥¡Ğ¤€´…Àì(€€€€€€€€€€€ô•±Í”ì(€€€€€€€€€€€€€€€±•™Ğ€ôÍ¥‘”€ôôô€É¥¡Ğœ(€€€€€€€€€€€€€€€€€€€€üÙ¥Í¥‰±•I•Ğ¹É¥¡Ğ€¬…À(€€€€€€€€€€€€€€€€€€€€èÙ¥Í¥‰±•I•Ğ¹±•™Ğ€´…É‘]¥‘Ñ €´…Àì(€€€€€€€€€€€€€€€Ñ½À€ô±…µÀ (€€€€€€€€€€€€€€€€€€€É•Ğ¹Ñ½À€¬€ ¡É•Ğ¹¡•¥¡Ğ€´…É‘!•¥¡Ğ¤€¼€È¤°(€€€€€€€€€€€€€€€€€€€µ…É¥¸°(€€€€€€€€€€€€€€€€€€€Ù¥•İÁ½ÉÑ!•¥¡Ğ€´…É‘!•¥¡Ğ€´µ…É¥¸(€€€€€€€€€€€€€€€€¤ì(€€€€€€€€€€€ô((€€€€€€€€€€€…É¹ÍÑå±”¹±•™Ğ€ô€‘í±•™ÑõÁá€ì(€€€€€€€€€€€…É¹ÍÑå±”¹Ñ½À€ô€‘íÑ½ÁõÁá€ì(€€€€€€€ô¤¤ì(€€€ô((€€€™Õ¹Ñ¥½¸É•¹‘•È ¤ì(€€€€€€€½¹ÍĞÍÑ•À€ôÍÑ•ÁÍmÕÉÉ•¹Ñ%¹‘•átì(€€€€€€€¥˜€ …ÍÑ•À¤É•ÑÕÉ¸™¥¹¥Í  ¤ì(€€€€€€€Ñ¥Ñ±”¹Ñ•áÑ½¹Ñ•¹Ğ€ôÍÑ•À¹Ñ¥Ñ±”ì(€€€€€€€‘•ÍÉ¥ÁÑ¥½¸¹Ñ•áÑ½¹Ñ•¹Ğ€ôÍÑ•À¹‘•ÍÉ¥ÁÑ¥½¸ì(€€€€€€€•å•‰É½Ü¹Ñ•áÑ½¹Ñ•¹Ğ€ô…Ñ¥Ù•5½‘”€ôôô€Á…”œ€ü€A9U8!158œ€è€A9U8A1%-M$œì(€€€€€€€½Õ¹Ñ•È¹Ñ•áÑ½¹Ñ•¹Ğ€ô€‘íÕÉÉ•¹Ñ%¹‘•à€¬€Åô‘…É¤€‘íÍÑ•ÁÌ¹±•¹Ñ¡õ€ì(€€€€€€€ÁÉ•Ù¥½ÕÍ	ÕÑÑ½¸¹‘¥Í…‰±•€ôÕÉÉ•¹Ñ%¹‘•à€ôôô€Àì(€€€€€€€¹•áÑ	ÕÑÑ½¸¹Ñ•áÑ½¹Ñ•¹Ğ€ôÕÉÉ•¹Ñ%¹‘•à€ôôôÍÑ•ÁÌ¹±•¹Ñ €´€Ä(€€€€€€€€€€€€ü€¡½¹Ñ•áĞ€ôôô€Í•±•Ñ¥½¸œ€ü€5Õ±…¤Q½ÕÈœ€è€M•±•Í…¤œ¤(€€€€€€€€€€€€è€M•±…¹©ÕÑ¹å„œì(€€€€€€€Á½Í¥Ñ¥½¹ÕÉÉ•¹ÑMÑ•À ¤ì(€€€ô((€€€™Õ¹Ñ¥½¸ÍÑ…ÉĞ¡½ÁÑ¥½¹Ì€ôíô¤ì(€€€€€€€…Ñ¥Ù•5½‘”€ô½¹Ñ•áĞ€ôôô€Í•±•Ñ¥½¸œ€ü€µ•¹Ôœ€è€¡½ÁÑ¥½¹Ì¹µ½‘”ñğ€Á…”œ¤ì(€€€€€€€½¹ÍĞ…¹‘¥‘…Ñ•Ì€ô…Ñ¥Ù•5½‘”€ôôô€Á…”œ€ü‰Õ¥±‘A…•MÑ•ÁÌ ¤€èµ…¥¹MÑ•ÁÌì(€€€€€€€ÍÑ•ÁÌ€ô…Ù…¥±…‰±•MÑ•ÁÌ¡½¹Ñ•áĞ€ôôô€Í•±•Ñ¥½¸œ€üÍ•±•Ñ¥½¹MÑ•ÁÌ€è…¹‘¥‘…Ñ•Ì¤ì(€€€€€€€¥˜€ …ÍÑ•ÁÌ¹±•¹Ñ ¤É•ÑÕÉ¸ì(€€€€€€€ÕÉÉ•¹Ñ%¹‘•à€ô5…Ñ ¹µ¥¸¡½ÁÑ¥½¹Ì¹¥¹‘•àñğ€À°ÍÑ•ÁÌ¹±•¹Ñ €´€Ä¤ì(€€€€€€€…Ñ¥Ù”€ôÑÉÕ”ì(€€€€€€€É½½Ğ¹±…ÍÍ1¥ÍĞ¹É•µ½Ù” ¥Ìµ¡¥‘‘•¸œ¤ì(€€€€€€€É½½Ğ¹Í•ÑÑÑÉ¥‰ÕÑ” …É¥„µ¡¥‘‘•¸œ°€™…±Í”œ¤ì(€€€€€€€‘½Õµ•¹Ğ¹‰½‘ä¹±…ÍÍ1¥ÍĞ¹…‘ ½ÍÑ¥¹œµÑ½ÕÈµ…Ñ¥Ù”œ¤ì(€€€€€€€É•¹‘•È ¤ì(€€€ô((€€€™Õ¹Ñ¥½¸±½Í”¡µ…É­½µÁ±•Ñ•€ôÑÉÕ”¤ì(€€€€€€€…Ñ¥Ù”€ô™…±Í”ì(€€€€€€€É½½Ğ¹±…ÍÍ1¥ÍĞ¹…‘ ¥Ìµ¡¥‘‘•¸œ¤ì(€€€€€€€É½½Ğ¹Í•ÑÑÑÉ¥‰ÕÑ” …É¥„µ¡¥‘‘•¸œ°€ÑÉÕ”œ¤ì(€€€€€€€‘½Õµ•¹Ğ¹‰½‘ä¹±…ÍÍ1¥ÍĞ¹É•µ½Ù” ½ÍÑ¥¹œµÑ½ÕÈµ…Ñ¥Ù”œ¤ì(€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡½¹Ñ¥¹Õ…Ñ¥½¹-•ä¤ì(€€€€€€€¥˜€¡µ…É­½µÁ±•Ñ•¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹Í•Ñ%Ñ•´¡…Ñ¥Ù•5½‘”€ôôô€Á…”œ€üÁ…•½µÁ±•Ñ¥½¹-•ä€è½µÁ±•Ñ¥½¹-•ä°€œÄœ¤ì(€€€€€€€ô(€€€ô((€€€™Õ¹Ñ¥½¸™¥¹¥Í  ¤ì(€€€€€€€¥˜€¡½¹Ñ•áĞ€ôôô€Í•±•Ñ¥½¸œ€˜˜¹•áÑUÉ°¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹Í•Ñ%Ñ•´¡½¹Ñ¥¹Õ…Ñ¥½¹-•ä°€µ…¥¸œ¤ì(€€€€€€€€€€€İ¥¹‘½Ü¹±½…Ñ¥½¸¹¡É•˜€ô¹•áÑUÉ°ì(€€€€€€€€€€€É•ÑÕÉ¸ì(€€€€€€€ô(€€€€€€€½¹ÍĞ™¥¹¥Í¡•‘5½‘”€ô…Ñ¥Ù•5½‘”ì(€€€€€€€±½Í”¡ÑÉÕ”¤ì(€€€€€€€¥˜€¡½¹Ñ•áĞ€ôôô€µ…¥¸œ€˜˜™¥¹¥Í¡•‘5½‘”€ôôô€µ•¹Ôœ€˜˜±½…±MÑ½É…”¹•Ñ%Ñ•´¡Á…•½µÁ±•Ñ¥½¹-•ä¤€„ôô€œÄœ¤ì(€€€€€€€€€€€İ¥¹‘½Ü¹Í•ÑQ¥µ•½ÕĞ  ¤€ôøÍÑ…ÉĞ¡ìµ½‘”è€Á…”œô¤°€ÌÔÀ¤ì(€€€€€€€ô(€€€ô((€€€É½½Ğ¹…‘‘Ù•¹Ñ1¥ÍÑ•¹•È ±¥¬œ°•Ù•¹Ğ€ôøì(€€€€€€€½¹ÍĞ…Ñ¥½¸€ô•Ù•¹Ğ¹Ñ…É•Ğ¹±½Í•ÍĞ m‘…Ñ„µÑ½ÕÈµ…Ñ¥½¹tœ¤ü¹‘…Ñ…Í•Ğ¹Ñ½ÕÉÑ¥½¸ì(€€€€€€€¥˜€ ……Ñ¥½¸¤É•ÑÕÉ¸ì(€€€€€€€¥˜€¡…Ñ¥½¸€ôôô€Í­¥Àœ¤É•ÑÕÉ¸±½Í”¡ÑÉÕ”¤ì(€€€€€€€¥˜€¡…Ñ¥½¸€ôôô€ÁÉ•Ù¥½ÕÌœ€˜˜ÕÉÉ•¹Ñ%¹‘•à€ø€À¤ÕÉÉ•¹Ñ%¹‘•à´´ì(€€€€€€€¥˜€¡…Ñ¥½¸€ôôô€¹•áĞœ¤ì(€€€€€€€€€€€¥˜€¡ÕÉÉ•¹Ñ%¹‘•à€øôÍÑ•ÁÌ¹±•¹Ñ €´€Ä¤É•ÑÕÉ¸™¥¹¥Í  ¤ì(€€€€€€€€€€€ÕÉÉ•¹Ñ%¹‘•à¬¬ì(€€€€€€€ô(€€€€€€€É•¹‘•È ¤ì(€€€ô¤ì((€€€‘½Õµ•¹Ğ¹…‘‘Ù•¹Ñ1¥ÍÑ•¹•È ­•å‘½İ¸œ°•Ù•¹Ğ€ôøì(€€€€€€€¥˜€¡…Ñ¥Ù”€˜˜•Ù•¹Ğ¹­•ä€ôôô€Í…Á”œ¤±½Í”¡ÑÉÕ”¤ì(€€€ô¤ì(€€€İ¥¹‘½Ü¹…‘‘Ù•¹Ñ1¥ÍÑ•¹•È É•Í¥é”œ°Á½Í¥Ñ¥½¹ÕÉÉ•¹ÑMÑ•À¤ì((€€€İ¥¹‘½Ü¹½ÍÑ¥¹Q½ÕÈ€ôì(€€€€€€€ÍÑ…ÉĞ ¤ì(€€€€€€€€€€€¥˜€¡½¹Ñ•áĞ€ôôô€Í•±•Ñ¥½¸œ¤ì(€€€€€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡½µÁ±•Ñ¥½¹-•ä¤ì(€€€€€€€€€€€€€€€ÍÑ…ÉĞ¡ìµ½‘”è€µ•¹Ôœô¤ì(€€€€€€€€€€€€€€€É•ÑÕÉ¸ì(€€€€€€€€€€€ô(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡Á…•½µÁ±•Ñ¥½¹-•ä¤ì(€€€€€€€€€€€ÍÑ…ÉĞ¡ìµ½‘”è€Á…”œô¤ì(€€€€€€€ô°(€€€€€€€ÍÑ…ÉÑA…” ¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡Á…•½µÁ±•Ñ¥½¹-•ä¤ì(€€€€€€€€€€€ÍÑ…ÉĞ¡ìµ½‘”è€Á…”œô¤ì(€€€€€€€ô°(€€€€€€€ÍÑ…ÉÑ5•¹Ô ¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡½µÁ±•Ñ¥½¹-•ä¤ì(€€€€€€€€€€€ÍÑ…ÉĞ¡ìµ½‘”è€µ•¹Ôœô¤ì(€€€€€€€ô°(€€€€€€€É•Í•Ğ ¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡½µÁ±•Ñ¥½¹-•ä¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡Á…•½µÁ±•Ñ¥½¹-•ä¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡½¹Ñ¥¹Õ…Ñ¥½¹-•ä¤ì(€€€€€€€ô(€€€ôì((€€€‘½Õµ•¹Ğ¹…‘‘Ù•¹Ñ1¥ÍÑ•¹•È =5½¹Ñ•¹Ñ1½…‘•œ°€ ¤€ôøì(€€€€€€€½¹ÍĞ½µÁ±•Ñ•€ô±½…±MÑ½É…”¹•Ñ%Ñ•´¡½µÁ±•Ñ¥½¹-•ä¤€ôôô€œÄœì(€€€€€€€½¹ÍĞ½¹Ñ¥¹Õ…Ñ¥½¸€ô±½…±MÑ½É…”¹•Ñ%Ñ•´¡½¹Ñ¥¹Õ…Ñ¥½¹-•ä¤ì(€€€€€€€¥˜€¡½¹Ñ•áĞ€ôôô€µ…¥¸œ€˜˜½¹Ñ¥¹Õ…Ñ¥½¸€ôôô€µ…¥¸œ¤ì(€€€€€€€€€€€±½…±MÑ½É…”¹É•µ½Ù•%Ñ•´¡½¹Ñ¥¹Õ…Ñ¥½¹-•ä¤ì(€€€€€€€€€€€İ¥¹‘½Ü¹Í•ÑQ¥µ•½ÕĞ  ¤€ôøÍÑ…ÉĞ¡ìµ½‘”è€µ•¹Ôœô¤°€ÔÀÀ¤ì(€€€€€€€ô•±Í”¥˜€ …½µÁ±•Ñ•€˜˜½¹Ñ•áĞ€ôôô€Í•±•Ñ¥½¸œ¤ì(€€€€€€€€€€€İ¥¹‘½Ü¹Í•ÑQ¥µ•½ÕĞ  ¤€ôøÍÑ…ÉĞ¡ìµ½‘”è€µ•¹Ôœô¤°€ÔÀÀ¤ì(€€€€€€€ô•±Í”¥˜€¡½¹Ñ•áĞ€ôôô€µ…¥¸œ€˜˜±½…±MÑ½É…”¹•Ñ%Ñ•´¡Á…•½µÁ±•Ñ¥½¹-•ä¤€„ôô€œÄœ¤ì(€€€€€€€€€€€İ¥¹‘½Ü¹Í•ÑQ¥µ•½ÕĞ  ¤€ôøÍÑ…ÉĞ¡ìµ½‘”è€Á…”œô¤°€ØÔÀ¤ì(€€€€€€€ô(€€€ô¤ì)ô¤ ¤ì(ğ½ÍÉ¥ÁĞø