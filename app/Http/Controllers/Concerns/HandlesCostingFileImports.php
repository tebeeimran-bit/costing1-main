<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\Customer;
use App\Models\CogmSubmission;
use App\Models\Material;
use App\Models\CostingData;
use App\Models\CostingExcelTemplate;
use App\Models\UnpricedPart;
use App\Models\DocumentRevision;
use App\Models\CycleTimeTemplate;
use App\Models\MaterialBreakdown;
use App\Models\Plant;
use App\Models\BusinessCategory;
use App\Models\Wire;
use App\Models\WireRate;
use App\Models\ExchangeRate;
use App\Models\Pic;
use App\Models\ProjectA00Form;
use App\Models\ProjectWorkflowTask;
use App\Http\Requests\StoreCostingRequest;
use App\Http\Requests\UpdateStatusProjectRequest;
use App\Services\Costing\CostingImportService;
use App\Services\Costing\CostingMaterialService;
use App\Services\Costing\CostingPersistenceService;
use App\Services\Costing\CostingResponseService;
use App\Services\Costing\CostingStatusService;
use App\Services\Costing\MissingProjectInformationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

trait 
HandlesCostingFileImports
{
    public function importUmh(Request $request, CostingImportService $importService, CostingMaterialService $materialService, CostingPersistenceService $persistenceService, CostingStatusService $statusService, CostingResponseService $responseService)
    {
        if ($request->hasFile('import_umh_file') && !$request->hasFile('import_cycle_time_file')) {
            $request->files->set('import_cycle_time_file', $request->file('import_umh_file'));
        }

        $request->merge([
            'update_section' => 'cycle_time',
            'import_cycle_time' => 1,
        ]);

        $storeRequest = $this->toStoreCostingRequest($request);

        return $this->store($storeRequest, $importService, $materialService, $persistenceService, $statusService, $responseService);
    }

    public function importCycleTime(Request $request, CostingImportService $importService, CostingMaterialService $materialService, CostingPersistenceService $persistenceService, CostingStatusService $statusService, CostingResponseService $responseService)
    {
        $request->merge([
            'update_section' => 'cycle_time',
            'import_cycle_time' => 1,
        ]);

        $storeRequest = $this->toStoreCostingRequest($request);

        return $this->store($storeRequest, $importService, $materialService, $persistenceService, $statusService, $responseService);
    }

    public function downloadCycleTimeTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cycle Time');

        $sheet->setCellValue('B16', 'NO');
        $sheet->setCellValue('C16', 'PROCESS');
        $sheet->setCellValue('F16', 'QTY');
        $sheet->setCellValue('G16', 'TIME (HOUR)');

        $sampleRows = [
            ['no' => 1, 'process' => 'Cutting, Stripping, Crimping', 'qty' => 120, 'time_hour' => 0.40],
            ['no' => 2, 'process' => 'Twisting', 'qty' => 120, 'time_hour' => 0.30],
            ['no' => 3, 'process' => 'HF Sealer', 'qty' => 120, 'time_hour' => 0.25],
        ];

        $startRow = 17;
        foreach ($sampleRows as $index => $sample) {
            $row = $startRow + $index;
            $sheet->setCellValue('B' . $row, $sample['no']);
            $sheet->setCellValue('C' . $row, $sample['process']);
            $sheet->setCellValue('F' . $row, $sample['qty']);
            $sheet->setCellValue('G' . $row, $sample['time_hour']);
        }

        foreach (['A', 'B', 'C', 'F', 'G'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'cycle_time_tpl_');
        if ($tmpPath === false) {
            abort(500, 'Gagal membuat file template sementara.');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download(
            $tmpPath,
            'cycle-time-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function loadPartlistMaterialRows(?int $trackingRevisionId, $uploadedPartlistFile = null): array
    {
        set_time_limit(180);

        $sourcePath = null;
        $extension = '';

        if ($uploadedPartlistFile) {
            $sourcePath = $uploadedPartlistFile->getPathname();
            $extension = strtolower((string) $uploadedPartlistFile->getClientOriginalExtension());
        } else {
            if (!$trackingRevisionId) {
                return ['rows' => [], 'error' => 'Pilih file partlist terlebih dahulu.'];
            }

            $revision = DocumentRevision::find($trackingRevisionId);
            if (!$revision || empty($revision->partlist_file_path)) {
                return ['rows' => [], 'error' => 'File partlist pada revisi ini tidak tersedia.'];
            }

            if (!Storage::exists($revision->partlist_file_path)) {
                return ['rows' => [], 'error' => 'File partlist tidak ditemukan di storage.'];
            }

            $sourcePath = Storage::path($revision->partlist_file_path);
            $extension = strtolower((string) pathinfo($revision->partlist_file_path, PATHINFO_EXTENSION));
        }

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return ['rows' => [], 'error' => 'Format partlist tidak didukung untuk import otomatis.'];
        }

        if (!$sourcePath || !is_readable($sourcePath)) {
            return ['rows' => [], 'error' => 'File partlist tidak dapat diakses oleh server.'];
        }

        $fileSize = @filesize($sourcePath);
        if ($fileSize === false || $fileSize <= 0) {
            return ['rows' => [], 'error' => 'File partlist kosong atau rusak.'];
        }

        try {
            $rows = $this->parsePartlistXlsx((string) $sourcePath);
            if (count($rows) === 0) {
                $diag = $this->diagnosePartlistFile((string) $sourcePath);
                return [
                    'rows' => [],
                    'error' => 'Data partlist tidak terdeteksi dari file. Pastikan data ada di kolom D-J mulai baris 12 (NO di kolom D). ' . $diag,
                ];
            }
            return ['rows' => $rows, 'error' => null];
        } catch (\Throwable $e) {
            return ['rows' => [], 'error' => 'Gagal membaca file partlist: ' . $e->getMessage()];
        }
    }

    private function loadCycleTimeRows($uploadedCycleTimeFile): array
    {
        set_time_limit(180);

        if (!$uploadedCycleTimeFile) {
            return ['rows' => [], 'error' => 'File Cycle Time belum dipilih.'];
        }

        $sourcePath = $uploadedCycleTimeFile->getPathname();
        $extension = strtolower((string) $uploadedCycleTimeFile->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return ['rows' => [], 'error' => 'Format file Cycle Time tidak didukung untuk import otomatis.'];
        }

        if (!$sourcePath || !is_readable($sourcePath)) {
            return ['rows' => [], 'error' => 'File Cycle Time tidak dapat diakses oleh server.'];
        }

        $fileSize = @filesize($sourcePath);
        if ($fileSize === false || $fileSize <= 0) {
            return ['rows' => [], 'error' => 'File Cycle Time kosong atau rusak.'];
        }

        try {
            $rows = $this->parseCycleTimeXlsx((string) $sourcePath);
            return ['rows' => $rows, 'error' => null];
        } catch (\Throwable $e) {
            return ['rows' => [], 'error' => 'Gagal membaca file Cycle Time: ' . $e->getMessage()];
        }
    }

    private function parseCycleTimeXlsx(string $filePath): array
    {
        if (!class_exists(IOFactory::class)) {
            throw new \RuntimeException('Parser PhpSpreadsheet tidak tersedia.');
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $bestCycleTimes = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (!$sheet instanceof Worksheet) {
                continue;
            }

            $rows = $this->extractCycleTimesFromTemplateSheet($sheet);
            if (count($rows) > count($bestCycleTimes)) {
                $bestCycleTimes = $rows;
            }
        }

        return $bestCycleTimes;
    }

    private function extractCycleTimesFromTemplateSheet(Worksheet $sheet): array
    {
        /*
         * FORMAT IMPORT UMH YANG DIPAKAI:
         * - Data langsung mulai baris 17
         * - Kolom B17 ke bawah = NO
         * - Kolom C17 ke bawah = PROCESS
         * - Kolom F17 ke bawah = QTY
         * - Kolom G17 ke bawah = TIME (HOUR)
         *
         * Tidak membaca kolom I / Area of Process.
         */
        $highestDataRow = (int) $sheet->getHighestDataRow();
        $highestRow = (int) $sheet->getHighestRow();
        $scanEnd = min(max($highestDataRow, $highestRow, 17), 5000);

        $cycleTimes = [];
        $emptyStreak = 0;

        for ($row = 17; $row <= $scanEnd; $row++) {
            $noRaw = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
            $process = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            $qtyRaw = trim((string) $sheet->getCell('F' . $row)->getFormattedValue());
            $timeHourRaw = trim((string) $sheet->getCell('G' . $row)->getFormattedValue());

            $hasSignal = $noRaw !== ''
                || $process !== ''
                || $qtyRaw !== ''
                || $timeHourRaw !== '';

            if (!$hasSignal) {
                $emptyStreak++;

                if ($emptyStreak >= 10) {
                    break;
                }

                continue;
            }

            $emptyStreak = 0;

            /*
             * Stop kalau sudah masuk section lain di bawah Process Cost,
             * misalnya IV. Tooling Depreciation.
             */
            $upperProcess = strtoupper($process);
            if (
                preg_match('/^[IVX]+\./', $upperProcess) === 1
                || str_contains($upperProcess, 'TOOLING')
                || str_contains($upperProcess, 'DEPRECIATION')
                || str_contains($upperProcess, 'SUMMARY')
            ) {
                break;
            }

            if ($process === '') {
                continue;
            }

            $no = $noRaw !== '' ? $this->toFloatValue($noRaw) : count($cycleTimes) + 1;
            $qty = $qtyRaw !== '' ? $this->toFloatValue($qtyRaw) : 0;
            $timeHour = $timeHourRaw !== '' ? $this->toFloatValue($timeHourRaw) : 0;
            $timeSec = $timeHour > 0 ? $timeHour * 3600 : 0;
            $timeSecPerQty = ($qty > 0 && $timeSec > 0) ? ($timeSec / $qty) : 0;
            $costPerSec = 10.33;
            $costPerUnit = $timeSecPerQty > 0 ? ($timeSecPerQty * $costPerSec) : 0;

            $cycleTimes[] = [
                'no' => $no,
                'row_no' => $no,
                'process' => $process,
                'qty' => $qty,
                'time_hour' => $timeHour,
                'time_sec' => $timeSec,
                'time_sec_per_qty' => $timeSecPerQty,
                'cost_per_sec' => $costPerSec,
                'cost_per_unit' => $costPerUnit,
                'area_of_process' => null,
            ];
        }

        return $cycleTimes;
    }

    private function normalizeAreaOfProcess(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if ($raw === 'PP - Preparation' || $raw === 'FA - Final Assy') {
            return $raw;
        }

        $normalized = strtoupper(preg_replace('/\s+/', ' ', $raw));

        if (in_array($normalized, ['PP', 'PREPARATION', 'PP PREPARATION', 'PP - PREPARATION'], true)) {
            return 'PP - Preparation';
        }

        if (in_array($normalized, ['FA', 'FINAL ASSY', 'FINAL ASSY', 'FA FINAL ASSY', 'FA - FINAL ASSY'], true)) {
            return 'FA - Final Assy';
        }

        return null;
    }

}
