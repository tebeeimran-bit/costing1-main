@php
    use App\Models\CostingData;
    use App\Models\DocumentRevision;
    use App\Models\MaterialBreakdown;
    use App\Models\UnpricedPart;

    /*
     * Notification Bell Project
     * - Dokumen project: muncul kalau belum ada satupun A00/A04/A05.
     * - Project belum costing: muncul hanya kalau data costing belum lengkap
     *   (belum ada material atau belum ada cycle time).
     * - Project belum full priced: muncul kalau sudah costing tetapi masih ada
     *   part yang belum ada harga atau masih estimate.
     */

    $latestRevisionIds = DocumentRevision::query()
        ->selectRaw('MAX(id) as id')
        ->whereNotNull('document_project_id')
        ->groupBy('document_project_id');

    $latestProjectRevisions = DocumentRevision::with('project')
        ->whereIn('id', $latestRevisionIds)
        ->get();

    $notificationItems = collect();
    foreach (auth()->user()?->notifications()->latest()->limit(30)->get() ?? [] as $databaseNotification) {
        $data = $databaseNotification->data;
        $notificationItems->push(['type'=>'bulky','title'=>$data['title']??'Pembaruan Bulky COGM','line'=>($data['a00_number']??'A00').' - '.($data['event']??'updated'),'description'=>$data['message']??'Terdapat pembaruan Bulky COGM.','button_label'=>'Buka','url'=>$data['url']??'#','color'=>'blue','notification_id'=>$databaseNotification->id,'is_unread'=>is_null($databaseNotification->read_at)]);
    }

    $normalizeUniquePartCount = function ($rows): int {
        return $rows
            ->map(function ($row, $index) {
                $partNo = trim((string) ($row->part_no ?? ''));

                return $partNo !== '' ? strtoupper($partNo) : ('ROW-' . ($index + 1));
            })
            ->unique()
            ->count();
    };

    $hasCycleTimeRows = function ($cycleTimes): bool {
        if (is_string($cycleTimes)) {
            $decoded = json_decode($cycleTimes, true);
            $cycleTimes = is_array($decoded) ? $decoded : [];
        } elseif ($cycleTimes instanceof \Illuminate\Support\Collection) {
            $cycleTimes = $cycleTimes->toArray();
        } elseif (!is_array($cycleTimes)) {
            $cycleTimes = [];
        }

        return collect($cycleTimes)->contains(function ($row) {
            if (!is_array($row)) {
                return false;
            }

            return collect($row)->contains(function ($value) {
                $value = trim((string) $value);

                return $value !== '' && $value !== '0' && $value !== '0.0' && $value !== '0,0';
            });
        });
    };

    foreach ($latestProjectRevisions as $revision) {
        $project = $revision->project;

        if (! $project) {
            continue;
        }

        $customerName = trim((string) ($project->customer ?? '-'));
        $modelName = trim((string) ($project->model ?? '-'));

        $hasA00 = ($revision->a00 ?? null) === 'ada';
        $hasA04 = ($revision->a04 ?? null) === 'ada';
        $hasA05 = ($revision->a05 ?? null) === 'ada';
        $revisionStatus = (string) ($revision->status ?? '');

        // Status workflow adalah sumber utama. Project yang sudah selesai dikirim
        // atau sudah terminal tidak boleh muncul lagi sebagai pekerjaan aktif.
        if ($revisionStatus === DocumentRevision::STATUS_SUBMITTED_TO_MARKETING || $hasA04 || $hasA05) {
            continue;
        }

        if ($revisionStatus === DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL) {
            if (in_array(auth()->user()?->role, ['admin', 'coordinator_costing'], true)) {
                $notificationItems->push([
                    'type' => 'approval',
                    'title' => 'COGM menunggu approval',
                    'line' => $customerName . ' - ' . $modelName . ' - Menunggu coordinator',
                    'description' => 'Costing sudah disubmit dan menunggu keputusan Coordinator Costing.',
                    'button_label' => 'Buka Inbox Costing',
                    'url' => route('costing.inbox', ['status' => 'active'], false),
                    'color' => 'orange',
                ]);
            }
            continue;
        }

        if ($revisionStatus === DocumentRevision::STATUS_APPROVED_BY_COORDINATOR) {
            if (in_array(auth()->user()?->role, ['admin', 'coordinator_costing'], true)) {
                $notificationItems->push([
                    'type' => 'approval',
                    'title' => 'COGM siap dikirim',
                    'line' => $customerName . ' - ' . $modelName . ' - Sudah approved',
                    'description' => 'COGM sudah disetujui dan siap dikirim ke PIC Marketing.',
                    'button_label' => 'Kirim ke Marketing',
                    'url' => route('costing.inbox', ['status' => 'active'], false),
                    'color' => 'blue',
                ]);
            }
            continue;
        }

        /*
         * Pemberitahuan dokumen project:
         * Notifikasi dokumen hilang kalau minimal salah satu A00/A04/A05 sudah ada.
         */
        if (! $hasA00 && ! $hasA04 && ! $hasA05) {
            $notificationItems->push([
                'type' => 'document',
                'title' => 'Dokumen project belum ada',
                'line' => $customerName . ' - ' . $modelName . ' - A00 belum ada',
                'description' => 'Minimal salah satu dokumen A00, A04, atau A05 harus terisi.',
                'button_label' => 'Cek Dokumen',
                'url' => Route::has('database.project-documents')
                    ? route('database.project-documents', absolute: false)
                    : '#',
                'color' => 'orange',
            ]);
        }

        $costingData = CostingData::query()
            ->where('tracking_revision_id', $revision->id)
            ->latest('id')
            ->first();

        $costingUrl = Route::has('form')
            ? route('form', array_filter([
                'id' => $costingData?->id,
                'tracking_revision_id' => $revision->id,
            ], fn ($value) => $value !== null && $value !== ''), false)
            : '#';

        $materialRows = collect();
        if ($costingData) {
            $materialRows = MaterialBreakdown::query()
                ->where('costing_data_id', $costingData->id)
                ->get(['part_no', 'amount1', 'cn_type']);
        }

        $hasMaterialData = $materialRows->isNotEmpty();
        $hasCycleTimeData = $costingData ? $hasCycleTimeRows($costingData->cycle_times ?? []) : false;

        /*
         * Project belum costing hanya untuk project yang memang belum punya
         * data Material atau Cycle Time. Kalau status project sudah costing
         * tapi masih ada harga kosong/estimate, jangan tampil sebagai belum costing.
         */
        if (! $hasMaterialData || ! $hasCycleTimeData) {
            $notificationItems->push([
                'type' => 'project',
                'title' => 'Project belum costing',
                'line' => $customerName . ' - ' . $modelName . ' - Belum costing',
                'description' => 'Project masih perlu dilengkapi di Form Costing.',
                'button_label' => 'Cek Project',
                'url' => $costingUrl,
                'color' => 'blue',
            ]);

            continue;
        }

        $openUnpricedParts = UnpricedPart::query()
            ->where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->get(['part_number', 'cn_type']);

        $estimatePriceRows = $openUnpricedParts->filter(function ($row) {
            return strtoupper(trim((string) ($row->cn_type ?? ''))) === 'E';
        });

        $missingPriceCount = $openUnpricedParts
            ->pluck('part_number')->map(fn ($part) => strtoupper(trim((string) $part)))->filter()->unique()->count();
        $estimatePriceCount = $estimatePriceRows
            ->pluck('part_number')->map(fn ($part) => strtoupper(trim((string) $part)))->filter()->unique()->count();

        /*
         * Project sudah costing tapi belum full priced.
         * Ini menggantikan notifikasi "Project belum costing" untuk project
         * yang statusnya sudah costing namun masih ada issue harga.
         */
        if ($missingPriceCount > 0 || $estimatePriceCount > 0) {
            $issues = collect();

            if ($missingPriceCount > 0) {
                $issues->push($missingPriceCount . ' part belum ada harga');
            }

            if ($estimatePriceCount > 0) {
                $issues->push($estimatePriceCount . ' part masih estimate');
            }

            $notificationItems->push([
                'type' => 'pricing',
                'title' => 'Project belum full priced',
                'line' => $customerName . ' - ' . $modelName . ' - ' . $issues->implode(', '),
                'description' => 'Status dokumen sudah costing, tetapi harga material belum sepenuhnya final.',
                'button_label' => 'Cek Harga',
                'url' => $costingUrl,
                'color' => 'purple',
            ]);
        }
    }

    $taskTypes = ['approval', 'document', 'project', 'pricing'];
    $notificationItems = $notificationItems->map(function ($item) use ($taskTypes) {
        $item['category'] = in_array($item['type'], $taskTypes, true) ? 'task' : 'activity';
        $item['is_unread'] = $item['is_unread'] ?? true;
        return $item;
    })->sortBy(function ($item) {
        $typeOrder = [
            'approval' => 10,
            'pricing' => 20,
            'project' => 30,
            'document' => 40,
            'bulky' => 50,
        ];

        return ($item['category'] === 'task' ? '0-' : '1-')
            . str_pad((string) ($typeOrder[$item['type']] ?? 99), 2, '0', STR_PAD_LEFT);
    })->values();
    $notificationCount = $notificationItems->count();
    $taskNotificationCount = $notificationItems->where('category', 'task')->count();
    $activityNotificationCount = $notificationItems->where('category', 'activity')->count();
    $unreadActivityCount = $notificationItems->where('category', 'activity')->where('is_unread', true)->count();
    $notificationBadgeCount = $taskNotificationCount + $unreadActivityCount;
@endphp

<style>
    .top-notification-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        margin-left: 0.75rem;
        z-index: 2000;
    }

    .top-notification-button {
        width: 42px;
        height: 42px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.13);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        transition: 0.18s ease;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
    }

    .top-notification-button:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-1px);
    }

    .top-notification-button svg {
        width: 20px;
        height: 20px;
    }

    .top-notification-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 999px;
        background: #ef4444;
        color: #ffffff;
        font-size: 0.68rem;
        font-weight: 900;
        line-height: 20px;
        text-align: center;
        border: 2px solid #1d4ed8;
        box-shadow: 0 8px 18px rgba(239, 68, 68, 0.35);
    }

    .top-notification-dropdown {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        width: min(430px, calc(100vw - 24px));
        background: #ffffff;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
        display: none;
        overflow: hidden;
        z-index: 99999;
    }

    .top-notification-dropdown.is-open {
        display: block;
    }

    .top-notification-dropdown::before {
        content: "";
        position: absolute;
        top: -8px;
        right: 18px;
        width: 16px;
        height: 16px;
        background: #ffffff;
        border-left: 1px solid #dbeafe;
        border-top: 1px solid #dbeafe;
        transform: rotate(45deg);
    }

    .top-notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        position: relative;
        z-index: 1;
    }

    .top-notification-title {
        font-size: 0.95rem;
        font-weight: 900;
        color: #0f172a;
    }

    .top-notification-count-text {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 800;
    }

    .top-notification-tabs {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.4rem;
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .top-notification-tab {
        min-width: 0;
        border: 1px solid #d7e1ef;
        border-radius: 9px;
        padding: 0.48rem 0.35rem;
        background: #ffffff;
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 900;
        cursor: pointer;
        white-space: nowrap;
    }

    .top-notification-tab.is-active {
        border-color: #2563eb;
        background: #2563eb;
        color: #ffffff;
    }

    .top-notification-tab-count {
        display: inline-flex;
        justify-content: center;
        min-width: 17px;
        margin-left: 3px;
        padding: 1px 4px;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
        font-size: 0.6rem;
    }

    .top-notification-tab.is-active .top-notification-tab-count {
        background: rgba(255,255,255,.2);
        color: #ffffff;
    }

    .top-notification-filterbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.45rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .top-notification-filter-label {
        color: #64748b;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .top-notification-read-filters {
        display: inline-flex;
        gap: 0.25rem;
    }

    .top-notification-type-filters {
        display: flex;
        min-width: 0;
        gap: 0.2rem;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .top-notification-type-filters::-webkit-scrollbar {
        display: none;
    }

    .top-notification-read-filter,
    .top-notification-type-filter {
        border: 0;
        border-radius: 999px;
        padding: 0.3rem 0.55rem;
        background: transparent;
        color: #64748b;
        font-size: 0.62rem;
        font-weight: 900;
        cursor: pointer;
        white-space: nowrap;
    }

    .top-notification-read-filter.is-active,
    .top-notification-type-filter.is-active {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .top-notification-body {
        max-height: 390px;
        overflow-y: auto;
        padding: 0;
    }

    .top-notification-item {
        display: grid;
        grid-template-columns: 24px minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.55rem;
        min-height: 62px;
        padding: 0.55rem 0.7rem;
        border: 0;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 0;
        margin: 0;
        background: #ffffff;
        transition: background-color 0.15s ease;
    }

    .top-notification-item:hover {
        background: #f8fafc;
    }

    .top-notification-item.is-read {
        background: #ffffff;
        border-color: #e2e8f0;
        opacity: 0.72;
    }

    .top-notification-unread-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        margin-right: 0.35rem;
        border-radius: 999px;
        background: #2563eb;
        vertical-align: 1px;
    }

    .top-notification-item[hidden], .top-notification-empty[hidden] {
        display: none !important;
    }

    .top-notification-type-filters[hidden],
    .top-notification-read-filters[hidden] {
        display: none !important;
    }

    .top-notification-item:last-child {
        border-bottom: 0;
    }

    .top-notification-item.is-orange {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    .top-notification-item.is-blue {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    .top-notification-item.is-purple {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    .top-notification-item.is-orange:hover,
    .top-notification-item.is-blue:hover,
    .top-notification-item.is-purple:hover {
        background: #f8fafc;
    }

    .top-notification-icon {
        width: 24px;
        height: 24px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        font-weight: 900;
        flex-shrink: 0;
    }

    .top-notification-icon.is-orange {
        background: #ffedd5;
        color: #ea580c;
    }

    .top-notification-icon.is-blue {
        background: #dbeafe;
        color: #2563eb;
    }

    .top-notification-icon.is-purple {
        background: #f3e8ff;
        color: #7e22ce;
    }

    .top-notification-content {
        min-width: 0;
    }

    .top-notification-item-title {
        overflow: hidden;
        font-size: 0.72rem;
        font-weight: 900;
        color: #0f172a;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .top-notification-line {
        display: block;
        overflow: hidden;
        max-width: 100%;
        padding: 0;
        border-radius: 0;
        background: transparent !important;
        font-size: 0.65rem;
        font-weight: 800;
        line-height: 1.25;
        margin: 0.18rem 0 0;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .top-notification-line.is-orange {
        background: #ffedd5;
        color: #9a3412;
    }

    .top-notification-line.is-blue {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .top-notification-line.is-purple {
        background: #f3e8ff;
        color: #7e22ce;
    }

    .top-notification-desc {
        overflow: hidden;
        font-size: 0.62rem;
        color: #64748b;
        line-height: 1.25;
        margin: 0.15rem 0 0;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .top-notification-action-wrap {
        align-self: center;
    }

    .top-notification-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        padding: 0.32rem 0.45rem;
        border-radius: 7px;
        text-decoration: none;
        font-size: 0.61rem;
        font-weight: 900;
    }

    .top-notification-action.is-orange {
        background: #ea580c;
        color: #ffffff;
    }

    .top-notification-action.is-blue {
        background: #2563eb;
        color: #ffffff;
    }

    .top-notification-action.is-purple {
        background: #7e22ce;
        color: #ffffff;
    }

    .top-notification-empty {
        padding: 1.25rem;
        text-align: center;
        color: #64748b;
        font-size: 0.82rem;
    }

    .top-notification-footer {
        padding: 0.75rem 1rem;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        color: #2563eb;
        font-size: 0.75rem;
        font-weight: 900;
        background: #f8fafc;
    }

    @media (max-width: 768px) {
        .top-notification-wrapper {
            margin-left: 0.35rem;
        }

        .top-notification-dropdown {
            right: -10px;
            width: calc(100vw - 32px);
        }
    }
</style>

<div class="top-notification-wrapper">
    <button type="button" class="top-notification-button" id="topNotificationButton" aria-label="Buka notifikasi project">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>

        @if($notificationBadgeCount > 0)
            <span class="top-notification-badge">{{ $notificationBadgeCount > 99 ? '99+' : $notificationBadgeCount }}</span>
        @endif
    </button>

    <div class="top-notification-dropdown" id="topNotificationDropdown">
        <div class="top-notification-header">
            <div class="top-notification-title">Notifikasi Project</div>
            <div class="top-notification-count-text">
                {{ $notificationCount }} notifikasi
            </div>
            @if(auth()->user()->unreadNotifications()->exists())<form method="post" action="{{ route('notifications.read-all',absolute:false) }}">@csrf<button type="submit" style="border:0;background:transparent;color:#2563eb;font-size:.68rem;font-weight:900;cursor:pointer">Tandai semua dibaca</button></form>@endif
        </div>

        <div class="top-notification-tabs" role="tablist" aria-label="Jenis notifikasi">
            <button type="button" class="top-notification-tab is-active" data-notification-tab="task">Tugas <span class="top-notification-tab-count">{{ $taskNotificationCount }}</span></button>
            <button type="button" class="top-notification-tab" data-notification-tab="activity">Aktivitas <span class="top-notification-tab-count">{{ $activityNotificationCount }}</span></button>
        </div>

        <div class="top-notification-filterbar">
            <span class="top-notification-filter-label">Tampilkan</span>
            <div class="top-notification-type-filters" id="topNotificationTypeFilters" aria-label="Kategori tugas">
                <button type="button" class="top-notification-type-filter is-active" data-notification-type-filter="all">Semua</button>
                <button type="button" class="top-notification-type-filter" data-notification-type-filter="project">Costing</button>
                <button type="button" class="top-notification-type-filter" data-notification-type-filter="document">Dokumen</button>
                <button type="button" class="top-notification-type-filter" data-notification-type-filter="pricing">Harga</button>
                <button type="button" class="top-notification-type-filter" data-notification-type-filter="approval">Approval</button>
            </div>
            <div class="top-notification-read-filters" id="topNotificationReadFilters" aria-label="Status baca notifikasi" hidden>
                <button type="button" class="top-notification-read-filter is-active" data-notification-read-filter="all">Semua</button>
                <button type="button" class="top-notification-read-filter" data-notification-read-filter="unread">Belum Dibaca</button>
            </div>
        </div>

        <div class="top-notification-body">
            @forelse($notificationItems as $item)
                <div class="top-notification-item is-{{ $item['color'] }}{{ $item['is_unread'] ? '' : ' is-read' }}" data-notification-category="{{ $item['category'] }}" data-notification-type="{{ $item['type'] }}" data-notification-unread="{{ $item['is_unread'] ? '1' : '0' }}">
                    <div class="top-notification-icon is-{{ $item['color'] }}">
                        {{ $item['type'] === 'document' ? '!' : ($item['type'] === 'pricing' ? 'Rp' : 'i') }}
                    </div>

                    <div class="top-notification-content">
                        <div class="top-notification-item-title">@if($item['is_unread'])<span class="top-notification-unread-dot" aria-label="Belum dibaca"></span>@endif{{ $item['title'] }}</div>

                        <div class="top-notification-line is-{{ $item['color'] }}">
                            {{ $item['line'] }}
                        </div>

                        <div class="top-notification-desc">
                            {{ $item['description'] }}
                        </div>
                    </div>

                    <div class="top-notification-action-wrap">
                        @if(!empty($item['notification_id']))<form method="post" action="{{ route('notifications.open',$item['notification_id'],absolute:false) }}">@csrf<button type="submit" class="top-notification-action is-{{ $item['color'] }}" style="border:0;cursor:pointer">Buka</button></form>@else<a href="{{ $item['url'] }}" class="top-notification-action is-{{ $item['color'] }}">Buka</a>@endif
                    </div>
                </div>
            @empty
                <div class="top-notification-empty">
                    Tidak ada notifikasi project.
                </div>
            @endforelse
            <div class="top-notification-empty" id="topNotificationFilteredEmpty" hidden>
                Tidak ada notifikasi pada kategori ini.
            </div>
        </div>

        <div class="top-notification-footer" id="topNotificationFooter">
            {{ $taskNotificationCount > 0 ? $taskNotificationCount . ' tugas perlu ditindaklanjuti' : 'Tidak ada tugas tertunda' }}
        </div>
    </div>
</div>

<script>
    (function () {
        const button = document.getElementById('topNotificationButton');
        const dropdown = document.getElementById('topNotificationDropdown');
        const tabs = Array.from(document.querySelectorAll('[data-notification-tab]'));
        const typeFilters = Array.from(document.querySelectorAll('[data-notification-type-filter]'));
        const readFilters = Array.from(document.querySelectorAll('[data-notification-read-filter]'));
        const typeFilterGroup = document.getElementById('topNotificationTypeFilters');
        const readFilterGroup = document.getElementById('topNotificationReadFilters');
        const items = Array.from(document.querySelectorAll('[data-notification-category]'));
        const filteredEmpty = document.getElementById('topNotificationFilteredEmpty');
        const footer = document.getElementById('topNotificationFooter');
        let activeTab = 'task';
        let activeTypeFilter = 'all';
        let activeReadFilter = 'all';

        if (!button || !dropdown) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();
            dropdown.classList.toggle('is-open');
        });

        dropdown.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        function applyNotificationFilters() {
            let visibleCount = 0;

            items.forEach(function (item) {
                const matchesTab = item.dataset.notificationCategory === activeTab;
                const matchesType = activeTab !== 'task'
                    || activeTypeFilter === 'all'
                    || item.dataset.notificationType === activeTypeFilter;
                const matchesRead = activeTab !== 'activity'
                    || activeReadFilter === 'all'
                    || item.dataset.notificationUnread === '1';
                const visible = matchesTab && matchesType && matchesRead;
                item.hidden = !visible;
                if (visible) visibleCount++;
            });

            if (filteredEmpty) filteredEmpty.hidden = visibleCount > 0;
            if (footer) {
                footer.textContent = activeTab === 'task'
                    ? (visibleCount > 0 ? visibleCount + ' tugas perlu ditindaklanjuti' : 'Tidak ada tugas tertunda')
                    : (visibleCount > 0 ? visibleCount + ' aktivitas project' : 'Belum ada aktivitas project');
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activeTab = tab.dataset.notificationTab || 'task';
                tabs.forEach(function (candidate) {
                    candidate.classList.toggle('is-active', candidate === tab);
                });
                if (typeFilterGroup) typeFilterGroup.hidden = activeTab !== 'task';
                if (readFilterGroup) readFilterGroup.hidden = activeTab !== 'activity';
                applyNotificationFilters();
            });
        });

        typeFilters.forEach(function (filterButton) {
            filterButton.addEventListener('click', function () {
                activeTypeFilter = filterButton.dataset.notificationTypeFilter || 'all';
                typeFilters.forEach(function (candidate) {
                    candidate.classList.toggle('is-active', candidate === filterButton);
                });
                applyNotificationFilters();
            });
        });

        readFilters.forEach(function (filterButton) {
            filterButton.addEventListener('click', function () {
                activeReadFilter = filterButton.dataset.notificationReadFilter || 'all';
                readFilters.forEach(function (candidate) {
                    candidate.classList.toggle('is-active', candidate === filterButton);
                });
                applyNotificationFilters();
            });
        });

        applyNotificationFilters();

        document.addEventListener('click', function () {
            dropdown.classList.remove('is-open');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                dropdown.classList.remove('is-open');
            }
        });
    })();
</script>
