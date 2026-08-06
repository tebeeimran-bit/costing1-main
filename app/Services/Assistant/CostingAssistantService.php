<?php

namespace App\Services\Assistant;

use App\Models\CostingAssistantFileTemplate;
use App\Models\CostingAssistantRule;
use App\Models\CostingAssistantTopic;
use App\Models\DocumentRevision;
use App\Models\DocumentProject;
use App\Models\ExchangeRate;
use App\Models\UnpricedPart;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CostingAssistantService
{
    public function bootstrap(?string $routeName, string $path, ?string $role, ?int $trackingRevisionId = null): array
    {
        $snapshot = $this->snapshot($routeName, $path, $role, $trackingRevisionId);
        $rules = $this->applicableRules($snapshot, '');

        return [
            'snapshot' => $snapshot,
            'rules' => $rules,
            'templates' => $this->templates(),
            'quick_prompts' => $this->quickPrompts($snapshot),
        ];
    }

    public function respond(string $message, ?string $routeName, string $path, ?string $role, ?int $trackingRevisionId = null): array
    {
        $message = trim($message);
        $snapshot = $this->snapshot($routeName, $path, $role, $trackingRevisionId);
        $rules = $this->applicableRules($snapshot, $message);
        $topics = $this->matchingTopics($message, $snapshot, $role);

        $replyParts = [];
        if ($topics->isNotEmpty()) {
            $replyParts[] = $topics->first()['content'];
        }

        if ($rules !== []) {
            $replyParts[] = 'Saya juga menemukan ' . count($rules) . ' catatan dari kondisi data saat ini.';
        }

        if ($replyParts === []) {
            $replyParts[] = $this->fallbackReply($message, $snapshot);
        }

        return [
            'reply' => implode("\n\n", $replyParts),
            'topics' => $topics->take(3)->values()->all(),
            'rules' => $rules,
            'snapshot' => $snapshot,
            'actions' => $this->actionsFromRules($rules),
        ];
    }

    public function inspectFile(UploadedFile $file, ?CostingAssistantFileTemplate $template = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $base = [
            'file_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'size_kb' => round($file->getSize() / 1024, 2),
            'template' => $template?->name,
        ];

        if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return array_merge($base, $this->inspectSpreadsheet($file, $template));
        }

        if ($extension === 'pdf') {
            return array_merge($base, $this->inspectPdf($file, $template));
        }

        return array_merge($base, [
            'status' => 'error',
            'message' => 'Format file belum didukung. Gunakan Excel (.xlsx/.xls/.csv) atau PDF.',
            'issues' => ['Ekstensi tidak dikenali oleh Costing Assistant.'],
        ]);
    }

    public function templates(): array
    {
        return CostingAssistantFileTemplate::query()
            ->where('active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'type', 'name', 'required_columns', 'optional_columns'])
            ->map(fn (CostingAssistantFileTemplate $template) => [
                'id' => $template->id,
                'type' => $template->type,
                'name' => $template->name,
                'required_columns' => $template->required_columns ?? [],
                'optional_columns' => $template->optional_columns ?? [],
            ])
            ->values()
            ->all();
    }

    public function snapshot(?string $routeName, string $path, ?string $role, ?int $trackingRevisionId = null): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthRateExists = ExchangeRate::query()
            ->whereYear('period_date', $currentMonth->year)
            ->whereMonth('period_date', $currentMonth->month)
            ->exists();

        $latestRevisionIds = DocumentRevision::query()
            ->selectRaw('MAX(id)')
            ->whereNotNull('document_project_id')
            ->groupBy('document_project_id');
        $activeRevisionQuery = DocumentRevision::query()->whereIn('id', $latestRevisionIds);
        $activeRevisionIds = (clone $activeRevisionQuery)->pluck('id');
        $currentRevision = $trackingRevisionId
            ? DocumentRevision::with(['project', 'costingData'])->find($trackingRevisionId)
            : null;
        $currentOpenUnpriced = $currentRevision
            ? UnpricedPart::where('document_revision_id', $currentRevision->id)->whereNull('resolved_at')->count()
            : null;

        return [
            'route' => $routeName ?: '-',
            'path' => $path,
            'role' => $role ?: 'guest',
            'module' => $this->moduleFromRoute($routeName, $path),
            'project_count' => DocumentProject::count(),
            'unresolved_unpriced_count' => UnpricedPart::query()->whereIn('document_revision_id', $activeRevisionIds)->whereNull('resolved_at')->count(),
            'pending_pricing_count' => (clone $activeRevisionQuery)->where('status', DocumentRevision::STATUS_PENDING_PRICING)->count(),
            'waiting_approval_count' => (clone $activeRevisionQuery)->where('status', DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL)->count(),
            'approved_count' => (clone $activeRevisionQuery)->where('status', DocumentRevision::STATUS_APPROVED_BY_COORDINATOR)->count(),
            'submitted_marketing_count' => (clone $activeRevisionQuery)->where('status', DocumentRevision::STATUS_SUBMITTED_TO_MARKETING)->count(),
            'current_month_rate_exists' => $currentMonthRateExists,
            'current_period' => $currentMonth->format('Y-m'),
            'current_project' => $currentRevision ? [
                'revision_id' => $currentRevision->id,
                'assy_no' => $currentRevision->project?->part_number ?: '-',
                'customer' => $currentRevision->project?->customer ?: '-',
                'model' => $currentRevision->project?->model ?: '-',
                'status' => $currentRevision->status_label,
                'open_unpriced_count' => $currentOpenUnpriced,
            ] : null,
        ];
    }

    private function applicableRules(array $snapshot, string $message): array
    {
        $severityOrder = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];

        return CostingAssistantRule::query()
            ->where('active', true)
            ->get()
            ->sortBy(fn (CostingAssistantRule $rule) => [
                $severityOrder[$rule->severity] ?? 99,
                $rule->title,
            ])
            ->filter(fn (CostingAssistantRule $rule) => $this->ruleMatches($rule, $snapshot, $message))
            ->map(fn (CostingAssistantRule $rule) => [
                'id' => $rule->id,
                'code' => $rule->code,
                'title' => $rule->title,
                'severity' => $rule->severity,
                'message' => $rule->message,
                'action_label' => $rule->action_label,
                'action_url' => $rule->action_url,
            ])
            ->values()
            ->all();
    }

    private function ruleMatches(CostingAssistantRule $rule, array $snapshot, string $message): bool
    {
        $payload = $rule->condition_payload ?? [];

        return match ($rule->condition_type) {
            'always' => true,
            'keyword' => $this->containsAny($message, Arr::wrap($payload['keywords'] ?? [])),
            'route_is' => $this->routeMatches($snapshot['route'], Arr::wrap($payload['patterns'] ?? [])),
            'role_is' => in_array($snapshot['role'], Arr::wrap($payload['roles'] ?? []), true),
            'unresolved_unpriced_gt' => $snapshot['unresolved_unpriced_count'] > (int) ($payload['count'] ?? 0),
            'waiting_approval_gt' => $snapshot['waiting_approval_count'] > (int) ($payload['count'] ?? 0),
            'missing_exchange_rate_current_month' => !$snapshot['current_month_rate_exists'],
            default => false,
        };
    }

    private function matchingTopics(string $message, array $snapshot, ?string $role)
    {
        $needle = mb_strtolower($message . ' ' . $snapshot['module'] . ' ' . $snapshot['route']);

        return CostingAssistantTopic::query()
            ->where('active', true)
            ->where(function ($query) use ($role) {
                $query->whereNull('role');
                if ($role) {
                    $query->orWhere('role', $role);
                }
            })
            ->get()
            ->map(function (CostingAssistantTopic $topic) use ($needle, $snapshot) {
                $score = 0;
                if ($topic->menu === $snapshot['module']) {
                    $score += 3;
                }

                foreach (($topic->keywords ?? []) as $keyword) {
                    if ($keyword !== '' && str_contains($needle, mb_strtolower((string) $keyword))) {
                        $score += 2;
                    }
                }

                if (str_contains($needle, mb_strtolower($topic->title))) {
                    $score += 1;
                }

                return [
                    'id' => $topic->id,
                    'menu' => $topic->menu,
                    'title' => $topic->title,
                    'content' => $topic->content,
                    'score' => $score,
                ];
            })
            ->filter(fn (array $topic) => $topic['score'] > 0)
            ->sortByDesc('score')
            ->values();
    }

    private function fallbackReply(string $message, array $snapshot): string
    {
        if ($this->containsAny($message, ['status', 'project ini', 'progress'])) {
            $project = $snapshot['current_project'] ?? null;
            if ($project) {
                return 'Project '.$project['assy_no'].' ('.$project['customer'].' - '.$project['model'].') saat ini berstatus "'.$project['status'].'". '
                    .'Part tanpa harga yang masih terbuka: '.$project['open_unpriced_count'].'.';
            }

            return 'Saat ini ada '.$snapshot['project_count'].' project aktual, '
                .$snapshot['waiting_approval_count'].' menunggu approval, dan '
                .$snapshot['submitted_marketing_count'].' sudah dikirim ke Marketing.';
        }

        if ($this->containsAny($message, ['submit', 'belum bisa', 'tidak bisa'])) {
            $project = $snapshot['current_project'] ?? null;
            if ($project && $project['open_unpriced_count'] > 0) {
                return 'Costing belum bisa disubmit karena masih ada '.$project['open_unpriced_count'].' part tanpa harga. Buka Rekapan Part Tanpa Harga, lengkapi harganya, lalu simpan kembali.';
            }

            return 'Sebelum submit, pastikan Material dan Cycle Time tersimpan, tidak ada part tanpa harga, lalu cek kembali total COGM. Setelah itu gunakan Submit Approval di Inbox Costing.';
        }

        if ($this->containsAny($message, ['approval', 'approve'])) {
            return 'Ada '.$snapshot['waiting_approval_count'].' project yang menunggu approval Coordinator dan '.$snapshot['approved_count'].' project yang sudah approved serta siap dikirim ke Marketing.';
        }

        if ($this->containsAny($message, ['harga', 'unpriced', 'estimate'])) {
            return 'Terdapat '.$snapshot['unresolved_unpriced_count'].' part tanpa harga pada revisi project terbaru. Gunakan Inbox New Part Request untuk mengisi harga, lalu Submit agar nilainya masuk ke Material.';
        }

        if ($this->containsAny($message, ['rate', 'kurs'])) {
            return $snapshot['current_month_rate_exists']
                ? 'Rate kurs periode '.$snapshot['current_period'].' sudah tersedia.'
                : 'Rate kurs periode '.$snapshot['current_period'].' belum tersedia. Tambahkan melalui Database > Rate & Kurs sebelum menghitung COGM.';
        }

        if ($this->containsAny($message, ['upload', 'file', 'excel', 'xlsx', 'pdf'])) {
            return 'Gunakan tab File Check untuk validasi file secara lokal. Excel akan dicek header, jumlah baris, kolom wajib, dan data kosong; PDF dicek format dan ukuran dasarnya.';
        }

        return 'Saya belum menemukan jawaban yang tepat. Anda dapat bertanya tentang status project, alasan belum bisa submit, part tanpa harga, approval, rate kurs, atau pengecekan file Excel/PDF.';
    }

    private function quickPrompts(array $snapshot): array
    {
        if (!empty($snapshot['current_project'])) {
            return [
                'Apa status project ini?',
                'Kenapa costing belum bisa submit?',
                'Apa yang harus saya periksa?',
                'Apakah masih ada part tanpa harga?',
            ];
        }

        return [
            'Berapa project yang perlu ditindaklanjuti?',
            'Apa status approval saat ini?',
            'Apakah rate kurs sudah tersedia?',
            'Bagaimana cara mengecek file Excel?',
        ];
    }

    private function actionsFromRules(array $rules): array
    {
        return collect($rules)
            ->filter(fn (array $rule) => !empty($rule['action_label']) && !empty($rule['action_url']))
            ->map(fn (array $rule) => [
                'label' => $rule['action_label'],
                'url' => $rule['action_url'],
            ])
            ->unique('url')
            ->values()
            ->all();
    }

    private function moduleFromRoute(?string $routeName, string $path): string
    {
        $routeName = $routeName ?: '';
        if (Str::startsWith($routeName, 'database') || str_contains($path, '/database')) {
            return 'database';
        }
        if (Str::startsWith($routeName, 'costing') || $routeName === 'form' || str_contains($path, '/form')) {
            return 'form';
        }
        if (str_contains($routeName, 'approval') || str_contains($routeName, 'marketing')) {
            return 'approval';
        }
        if (str_contains($routeName, 'laporan') || str_contains($routeName, 'resume') || str_contains($routeName, 'analisis')) {
            return 'report';
        }
        if (str_contains($routeName, 'tracking') || $routeName === 'project') {
            return 'project';
        }
        return 'general';
    }

    private function routeMatches(string $route, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is((string) $pattern, $route)) {
                return true;
            }
        }

        return false;
    }

    private function containsAny(string $text, array $keywords): bool
    {
        $text = mb_strtolower($text);
        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower(trim((string) $keyword));
            if ($keyword !== '' && str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function inspectSpreadsheet(UploadedFile $file, ?CostingAssistantFileTemplate $template): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestDataRow();
            $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

            $headers = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $raw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
                $headers[] = $this->normalizeHeader($raw);
            }

            $headers = array_values(array_filter($headers));
            $required = array_map(fn ($value) => $this->normalizeHeader((string) $value), $template?->required_columns ?? []);
            $missing = array_values(array_diff($required, $headers));
            $issues = [];

            // Sembunyikan pesan kolom belum ada khusus untuk template Partlist karena headernya tidak di baris 1
            if ($template === null || $template->name !== 'Partlist / Costing Excel') {
                foreach ($missing as $column) {
                    $issues[] = 'Kolom wajib belum ada: ' . $column;
                }
            }

            $emptyRequiredCells = 0;
            $scanLimit = min($highestRow, 201);
            $headerPositions = array_flip($headers);
            foreach ($required as $requiredColumn) {
                if (!isset($headerPositions[$requiredColumn])) {
                    continue;
                }

                $colIndex = $headerPositions[$requiredColumn] + 1;
                for ($row = 2; $row <= $scanLimit; $row++) {
                    $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($colIndex) . $row)->getFormattedValue());
                    if ($value === '') {
                        $emptyRequiredCells++;
                    }
                }
            }

            if ($emptyRequiredCells > 0) {
                $issues[] = 'Ada ' . $emptyRequiredCells . ' cell kosong pada kolom wajib dari ' . max(0, $scanLimit - 1) . ' baris pertama yang dicek.';
            }

            $duplicateInfo = $this->duplicateInfo($sheet, $headers, $template, $highestRow);
            if ($duplicateInfo['duplicates'] > 0) {
                $issues[] = 'Ada ' . $duplicateInfo['duplicates'] . ' duplikasi berdasarkan kolom ' . $duplicateInfo['column'] . ' pada 500 baris pertama.';
            }

            $extractedData = [];
            if ($template !== null && $template->name === 'Partlist / Costing Excel') {
                $assyNo = trim((string) $sheet->getCell('F4')->getFormattedValue());
                $assyName = trim((string) $sheet->getCell('F5')->getFormattedValue());
                $customer = trim((string) $sheet->getCell('F6')->getFormattedValue());
                $model = trim((string) $sheet->getCell('F7')->getFormattedValue());

                if ($assyNo !== '') $extractedData['Assy No.'] = $assyNo;
                if ($assyName !== '') $extractedData['Assy Name'] = $assyName;
                if ($customer !== '') $extractedData['Customer'] = $customer;
                if ($model !== '') $extractedData['Model'] = $model;
            }

            return [
                'status' => $issues === [] ? 'success' : 'warning',
                'message' => $issues === [] ? 'File Excel terlihat valid untuk template yang dipilih.' : 'File Excel terbaca, tetapi ada catatan yang perlu dicek.',
                'sheet_name' => $sheet->getTitle(),
                'total_rows' => max(0, $highestRow - 1),
                'total_columns' => count($headers),
                'headers' => $headers,
                'missing_required_columns' => $missing,
                'empty_required_cells' => $emptyRequiredCells,
                'duplicate_check' => $duplicateInfo,
                'extracted_info' => $extractedData,
                'issues' => $issues,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'File Excel tidak bisa dibaca: ' . $e->getMessage(),
                'issues' => ['Parser Excel gagal membaca file. Pastikan file tidak corrupt dan formatnya sesuai.'],
            ];
        }
    }

    private function inspectPdf(UploadedFile $file, ?CostingAssistantFileTemplate $template): array
    {
        $rules = $template?->validation_rules ?? [];
        $maxSizeMb = (float) ($rules['max_size_mb'] ?? 20);
        $issues = [];

        if (($file->getSize() / 1024 / 1024) > $maxSizeMb) {
            $issues[] = 'Ukuran PDF melebihi batas ' . $maxSizeMb . ' MB.';
        }

        if ($file->getMimeType() !== 'application/pdf') {
            $issues[] = 'MIME type file bukan application/pdf.';
        }

        return [
            'status' => $issues === [] ? 'success' : 'warning',
            'message' => $issues === []
                ? 'PDF lolos pengecekan dasar. Isi dokumen tidak dikirim keluar dan belum diproses OCR.'
                : 'PDF terbaca, tetapi ada catatan format/ukuran.',
            'mime_type' => $file->getMimeType(),
            'issues' => $issues,
        ];
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/i', '_', $header) ?: '';
        return trim($header, '_');
    }

    private function duplicateInfo($sheet, array $headers, ?CostingAssistantFileTemplate $template, int $highestRow): array
    {
        $rules = $template?->validation_rules ?? [];
        $column = $this->normalizeHeader((string) ($rules['unique_by'] ?? ''));
        if ($column === '' || !in_array($column, $headers, true)) {
            return ['column' => $column ?: null, 'duplicates' => 0];
        }

        $colIndex = array_search($column, $headers, true) + 1;
        $seen = [];
        $duplicates = 0;
        $scanLimit = min($highestRow, 501);

        for ($row = 2; $row <= $scanLimit; $row++) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($colIndex) . $row)->getFormattedValue());
            if ($value === '') {
                continue;
            }
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                $duplicates++;
                continue;
            }
            $seen[$key] = true;
        }

        return ['column' => $column, 'duplicates' => $duplicates];
    }
}
