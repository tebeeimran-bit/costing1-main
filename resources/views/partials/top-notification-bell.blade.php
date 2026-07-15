@php
    use App\Models\CostingData;
    use App\Models\DocumentRevision;
    use App\Models\MaterialBreakdown;

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

        $missingPriceRows = $materialRows->filter(function ($row) {
            return (float) ($row->amount1 ?? 0) <= 0;
        });

        $estimatePriceRows = $materialRows->filter(function ($row) {
            return strtoupper(trim((string) ($row->cn_type ?? ''))) === 'E';
        });

        $missingPriceCount = $normalizeUniquePartCount($missingPriceRows);
        $estimatePriceCount = $normalizeUniquePartCount($estimatePriceRows);

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

    $notificationItems = $notificationItems->values();
    $notificationCount = $notificationItems->count();
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

    .top-notification-body {
        max-height: 390px;
        overflow-y: auto;
        padding: 0.75rem;
    }

    .top-notification-item {
        display: grid;
        grid-template-columns: 34px 1fr;
        gap: 0.7rem;
        padding: 0.85rem;
        border-radius: 14px;
        margin-bottom: 0.6rem;
        border: 1px solid #e2e8f0;
    }

    .top-notification-item:last-child {
        margin-bottom: 0;
    }

    .top-notification-item.is-orange {
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .top-notification-item.is-blue {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .top-notification-item.is-purple {
        background: #faf5ff;
        border-color: #e9d5ff;
    }

    .top-notification-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        font-size: 0.8rem;
        font-weight: 900;
        color: #0f172a;
        line-height: 1.25;
        margin-bottom: 0.25rem;
    }

    .top-notification-line {
        display: inline-flex;
        max-width: 100%;
        padding: 0.25rem 0.5rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        line-height: 1.25;
        margin: 0.25rem 0;
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
        font-size: 0.72rem;
        color: #64748b;
        line-height: 1.45;
        margin: 0.25rem 0 0.6rem;
    }

    .top-notification-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 0.65rem;
        border-radius: 9px;
        text-decoration: none;
        font-size: 0.72rem;
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

        @if($notificationCount > 0)
            <span class="top-notification-badge">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
        @endif
    </button>

    <div class="top-notification-dropdown" id="topNotificationDropdown">
        <div class="top-notification-header">
            <div class="top-notification-title">Notifikasi Project</div>
            <div class="top-notification-count-text">
                {{ $notificationCount }} notifikasi
            </div>
        </div>

        <div class="top-notification-body">
            @forelse($notificationItems as $item)
                <div class="top-notification-item is-{{ $item['color'] }}">
                    <div class="top-notification-icon is-{{ $item['color'] }}">
                        {{ $item['type'] === 'document' ? '!' : ($item['type'] === 'pricing' ? 'Rp' : 'i') }}
                    </div>

                    <div class="top-notification-content">
                        <div class="top-notification-item-title">{{ $item['title'] }}</div>

                        <div class="top-notification-line is-{{ $item['color'] }}">
                            {{ $item['line'] }}
                        </div>

                        <div class="top-notification-desc">
                            {{ $item['description'] }}
                        </div>

                        <a href="{{ $item['url'] }}" class="top-notification-action is-{{ $item['color'] }}">
                            {{ $item['button_label'] }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="top-notification-empty">
                    Tidak ada notifikasi project.
                </div>
            @endforelse
        </div>

        <div class="top-notification-footer">
            {{ $notificationCount > 0 ? 'Ada project yang perlu ditindaklanjuti' : 'Semua notifikasi aman' }}
        </div>
    </div>
</div>

<script>
    (function () {
        const button = document.getElementById('topNotificationButton');
        const dropdown = document.getElementById('topNotificationDropdown');

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
