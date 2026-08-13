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
HandlesMaterialEditor
{
    public function exportMaterialEditor(Request $request, CostingImportService $importService)
    {
        // Template v9 memuat sheet MM60 yang besar; beri waktu cukup hanya untuk proses export ini.
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '1536M');

        $validated = $request->validate([
            'materials_json' => ['required', 'string'],
            'cycle_times_json' => ['nullable', 'string'],
            'tracking_revision_id' => ['nullable', 'integer', 'exists:document_revisions,id'],
            'costing_data_id' => ['nullable', 'integer', 'exists:costing_data,id'],
            'assy_no' => ['nullable', 'string', 'max:255'],
            'assy_name' => ['nullable', 'string', 'max:255'],
            'customer' => ['nullable', 'string', 'max:255'],
            'customer_code' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:255'],
            'project_date' => ['nullable', 'date'],
            'sop_mp_date' => ['nullable', 'date'],
            'forecast' => ['nullable', 'numeric'],
            'project_period' => ['nullable', 'numeric'],
            'plant' => ['nullable', 'string', 'max:255'],
            'rate_usd' => ['nullable', 'numeric'],
            'rate_jpy' => ['nullable', 'numeric'],
            'rate_idr' => ['nullable', 'numeric'],
            'rate_lme' => ['nullable', 'numeric'],
            'rate_period' => ['nullable', 'date_format:Y-m-d,Y-m'],
            'export_mode' => ['nullable', 'in:editor,cogm'],
        ]);
        $rows = json_decode($validated['materials_json'], true);
        abort_unless(is_array($rows), 422, 'Data material tidak valid.');
        $cycleRows = json_decode((string) ($validated['cycle_times_json'] ?? '[]'), true);
        abort_unless(is_array($cycleRows), 422, 'Data Cycle Time tidak valid.');
        abort_if(count($rows) > 739, 422, 'Template Costing maksimal menampung 739 baris material.');

        $exportMode = (string) ($validated['export_mode'] ?? 'editor');
        $a00ExportForm = null;
        $a00ExportItem = null;
        $assyCount = 1;
        if (!empty($validated['tracking_revision_id'])) {
            $a00ExportForm = ProjectA00Form::with([
                'items.project',
                'items.projectRevision.plant',
            ])->whereHas('items', fn ($query) => $query
                ->where('document_revision_id', (int) $validated['tracking_revision_id']))->first();
            if ($a00ExportForm) {
                $assyCount = max(1, $a00ExportForm->items->count());
                $a00ExportItem = $a00ExportForm->items->firstWhere(
                    'document_revision_id',
                    (int) $validated['tracking_revision_id']
                );
            }
        }

        $uploadedTemplate = ($assyCount > 1 || $exportMode === 'cogm')
            ? CostingExcelTemplate::where('template_type', 'costing')
                ->where('assy_count', $assyCount)
                ->where('is_active', true)
                ->first()
            : null;
        $templatePath = $uploadedTemplate
            ? Storage::disk('local')->path($uploadedTemplate->file_path)
            : storage_path('app/templates/form-costing-v9.xlsx');
        abort_if(
            $assyCount > 1 && !$uploadedTemplate,
            422,
            "Template Export COGM untuk {$assyCount} assy belum tersedia. Upload melalui Database > Template Excel Costing."
        );
        abort_unless(is_file($templatePath), 500, 'Template Form Costing v9 tidak ditemukan.');
        $spreadsheet = IOFactory::load($templatePath);
        $activeAssyIndex = $a00ExportItem ? max(0, ((int) $a00ExportItem->line_number) - 1) : 0;
        $activeMaterialSheetName = $activeAssyIndex === 0
            ? 'Material Cost'
            : 'Material Cost ('.($activeAssyIndex + 1).')';
        $sheet = $spreadsheet->getSheetByName($activeMaterialSheetName);
        $lookupSheet = $spreadsheet->getSheetByName('Lembar1');
        $umhSheet = $spreadsheet->getSheetByName('UMH ');
        abort_unless($sheet && $lookupSheet && $umhSheet, 500, "Struktur template tidak valid. Sheet {$activeMaterialSheetName}, Lembar1, atau UMH tidak ditemukan.");

        // Template sumber membawa ribuan defined name usang dari workbook eksternal.
        // Excel akan menganggap hasil export rusak jika metadata tersebut ditulis kembali.
        foreach ($spreadsheet->getDefinedNames() as $definedName) {
            if (!str_starts_with($definedName->getName(), '_xlnm.')) {
                $spreadsheet->removeDefinedName(
                    $definedName->getName(),
                    $definedName->getLocalOnly() ? $definedName->getWorksheet() : null
                );
            }
        }

        // Hilangkan referensi workbook eksternal lama yang ditolak Excel.
        $resumeSheet = $spreadsheet->getSheetByName('Resume');
        if ($resumeSheet) {
            $resumeSheet->setCellValue('H44', "='COGM'!H12");
        }

        $formulaColumns = ['A', 'B', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'];
        $baseFormulas = [];
        foreach ($formulaColumns as $column) {
            $baseFormulas[$column] = (string) $sheet->getCell("{$column}18")->getValue();
        }

        foreach (['A', 'B', 'C', 'D', 'F', 'G', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB'] as $column) {
            for ($excelRow = 18; $excelRow <= 756; $excelRow++) {
                $sheet->setCellValue("{$column}{$excelRow}", null);
            }
        }
        $lookupClearStartRow = $exportMode === 'editor' ? 3 : 1;
        for ($excelRow = $lookupClearStartRow; $excelRow <= max(102, $lookupSheet->getHighestRow()); $excelRow++) {
            $lastLookupColumnToClear = $exportMode === 'editor' ? 9 : 16;
            for ($column = 2; $column <= $lastLookupColumnToClear; $column++) {
                $lookupSheet->setCellValue([$column, $excelRow], null);
            }
        }

        $sheet->setCellValueExplicit('F5', (string) ($validated['assy_no'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F6', (string) ($validated['assy_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F7', (string) ($validated['customer'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F8', (string) ($validated['model'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        if (!empty($validated['project_date'])) {
            $sheet->setCellValue('F9', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTimeImmutable($validated['project_date'])));
            $sheet->getStyle('F9')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }
        if (!empty($validated['sop_mp_date'])) {
            $sheet->setCellValue('F11', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTimeImmutable($validated['sop_mp_date'])));
            $sheet->getStyle('F11')->getNumberFormat()->setFormatCode('mmm-yy');
        } else {
            $sheet->setCellValueExplicit('F11', 'NEW', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $sheet->setCellValue('F12', (float) ($validated['forecast'] ?? 0));
        $sheet->setCellValue('F13', (float) ($validated['project_period'] ?? 0));
        $plant = trim((string) preg_replace('/^\s*\d+\s*[\-–—]\s*/u', '', (string) ($validated['plant'] ?? '')));
        $sheet->setCellValueExplicit('F14', strtoupper($plant), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        foreach (['N8' => 'rate_usd', 'N9' => 'rate_jpy', 'N10' => 'rate_idr', 'N11' => 'rate_lme'] as $cell => $field) {
            $sheet->setCellValue($cell, (float) ($validated[$field] ?? ($cell === 'N10' ? 1 : 0)));
        }
        $sheet->getStyle('N8:N11')->getNumberFormat()->setFormatCode('#,##0.00');
        if (!empty($validated['rate_period'])) {
            $ratePeriod = strlen($validated['rate_period']) === 7 ? $validated['rate_period'] . '-01' : $validated['rate_period'];
            $sheet->setCellValue('N12', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTimeImmutable($ratePeriod)));
            $sheet->getStyle('N12')->getNumberFormat()->setFormatCode('mmm-yyyy');
        } else {
            $sheet->setCellValue('N12', null);
        }

        $engineeringExtras = [];
        if (!empty($validated['tracking_revision_id'])) {
            $revision = DocumentRevision::find((int) $validated['tracking_revision_id']);
            $partlistPath = $revision?->partlist_file_path
                ? storage_path('app/private/' . ltrim((string) $revision->partlist_file_path, '/'))
                : null;
            if ($partlistPath && is_file($partlistPath)) {
                $partlist = IOFactory::load($partlistPath)->getSheetByName('PART LIST');
                if ($partlist) {
                    foreach ($rows as $index => $_) {
                        $sourceRow = $index + 12;
                        $engineeringExtras[$index] = array_map(
                            fn ($column) => $partlist->getCell("{$column}{$sourceRow}")->getValue(),
                            ['K', 'L', 'M', 'N', 'O']
                        );
                    }
                }
            }
        }

        foreach ($rows as $index => $row) {
            $excelRow = $index + 18;
            $sheet->setCellValue("C{$excelRow}", $index + 1);
            foreach (['D' => 'part_no', 'F' => 'id_code', 'G' => 'part_name', 'J' => 'unit', 'K' => 'pro_code'] as $column => $field) {
                $sheet->setCellValueExplicit("{$column}{$excelRow}", (string) ($row[$field] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $sheet->setCellValue("I{$excelRow}", $this->parseQuantityValue($row['qty_req'] ?? 0));
            foreach (array_combine(['X', 'Y', 'Z', 'AA', 'AB'], $engineeringExtras[$index] ?? array_fill(0, 5, '')) as $column => $value) {
                $sheet->setCellValue("{$column}{$excelRow}", $value);
            }
        }

        $referenceHelper = \PhpOffice\PhpSpreadsheet\ReferenceHelper::getInstance();
        foreach ($rows as $index => $_) {
            $excelRow = $index + 18;
            foreach ($baseFormulas as $column => $formula) {
                if ($formula !== '') {
                    $sheet->setCellValue("{$column}{$excelRow}", $referenceHelper->updateFormulaReferences($formula, 'A18', 0, $excelRow - 18, $activeMaterialSheetName));
                }
            }
        }

        for ($index = 1; $index <= 15; $index++) {
            $lookupSheet->setCellValue([$index + 1, 1], $index);
        }
        $seenPartNumbers = [];
        $lookupRow = $exportMode === 'editor' ? 3 : 2;
        foreach ($rows as $index => $row) {
            $key = mb_strtolower(trim((string) ($row['part_no'] ?? '')), 'UTF-8');
            if (array_key_exists($key, $seenPartNumbers)) {
                continue;
            }
            $seenPartNumbers[$key] = true;
            $sourceRow = $index + 18;
            for ($offset = 0; $offset < 8; $offset++) {
                $lookupSheet->setCellValue([$offset + 2, $lookupRow], $sheet->getCell([$offset + 4, $sourceRow])->getValue());
            }
            if ($exportMode === 'cogm') {
                foreach (['amount1', 'unit_price_basis', 'currency', 'qty_moq', 'cn_type', 'supplier', 'import_tax'] as $offset => $field) {
                    $lookupSheet->setCellValue([$offset + 10, $lookupRow], $row[$field] ?? '');
                }
            }
            $lookupRow++;
        }

        $umhClearUntil = max(52, $umhSheet->getHighestRow(), 9 + count($cycleRows));
        for ($excelRow = 9; $excelRow <= $umhClearUntil; $excelRow++) {
            for ($column = 1; $column <= 10; $column++) {
                $umhSheet->setCellValue([$column, $excelRow], null);
            }
        }
        foreach ($cycleRows as $index => $cycle) {
            $excelRow = $index + 9;
            if ($excelRow > 52) {
                $umhSheet->duplicateStyle($umhSheet->getStyle('A9:J9'), "A{$excelRow}:J{$excelRow}");
            }
            $umhSheet->setCellValue("A{$excelRow}", $index + 1);
            $umhSheet->setCellValueExplicit("B{$excelRow}", (string) ($cycle['process'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            foreach (['E' => 'qty', 'F' => 'time_hour', 'G' => 'time_sec', 'H' => 'time_sec_per_qty', 'I' => 'cost_per_sec', 'J' => 'cost_per_unit'] as $column => $field) {
                $umhSheet->setCellValue("{$column}{$excelRow}", $this->toFloatValue($cycle[$field] ?? 0));
            }
        }
        $lastCycleRow = count($cycleRows) > 0 ? 8 + count($cycleRows) : 8;
        $totalRow = $lastCycleRow + 1;
        if ($totalRow > 52) {
            $umhSheet->duplicateStyle($umhSheet->getStyle('A9:J9'), "A{$totalRow}:J{$totalRow}");
        }
        if ($lastCycleRow >= 9) {
            $umhSheet->setCellValue("F{$totalRow}", "=SUM(F9:F{$lastCycleRow})");
            $umhSheet->setCellValue("G{$totalRow}", "=SUM(G9:G{$lastCycleRow})");
            $umhSheet->setCellValue("J{$totalRow}", "=SUM(J9:J{$lastCycleRow})");
        }

        if ($a00ExportForm && $assyCount > 1) {
            $this->fillGroupedMaterialSheets(
                $spreadsheet,
                $lookupSheet,
                $a00ExportForm,
                (int) ($validated['tracking_revision_id'] ?? 0),
                $exportMode,
                $importService,
                $validated,
                $rows
            );
        }
        $this->applyMaterialSheetFormulas($sheet, count($rows), $exportMode === 'editor');

        if ($exportMode === 'cogm') {
            // Hasil COGM harus berisi nilai final tanpa bergantung pada kalkulasi
            // VLOOKUP di Lembar1. PhpSpreadsheet tidak selalu dapat menghitung
            // formula lokal template, sehingga getCalculatedValue() dapat kosong.
            // Samakan juga perilakunya dengan VLOOKUP: part number yang berulang
            // selalu memakai data dari kemunculan pertamanya di daftar material.
            $firstRowByPartNumber = [];
            foreach ($rows as $row) {
                $partNumberKey = mb_strtolower(trim((string) ($row['part_no'] ?? '')), 'UTF-8');
                if (!array_key_exists($partNumberKey, $firstRowByPartNumber)) {
                    $firstRowByPartNumber[$partNumberKey] = $row;
                }
            }

            // Jika pengguna sudah menjalankan "Import Hasil Edit", workbook itu
            // adalah sumber kebenaran untuk nilai hasil formula L:R. Ambil cached
            // value yang disimpan Excel agar hasil COGM benar-benar identik dengan
            // file cogm. yang di-import, lalu bekukan sebagai nilai biasa.
            $importedMaterialSheet = null;
            $exportRevisionId = isset($validated['tracking_revision_id'])
                ? (int) $validated['tracking_revision_id']
                : (int) (CostingData::find($validated['costing_data_id'] ?? null)?->tracking_revision_id ?? 0);
            if ($exportRevisionId <= 0 && !empty($validated['assy_no'])) {
                $exportRevisionId = (int) (CostingData::where('assy_no', $validated['assy_no'])
                    ->latest('id')
                    ->value('tracking_revision_id') ?? 0);
            }
            if ($exportRevisionId > 0) {
                $importedRevision = DocumentRevision::find($exportRevisionId);
                $importedWorkbookPath = $importedRevision?->costing_edit_file_path
                    ? Storage::disk('local')->path($importedRevision->costing_edit_file_path)
                    : null;

                if ($importedWorkbookPath && is_file($importedWorkbookPath)) {
                    $importedReader = IOFactory::createReaderForFile($importedWorkbookPath);
                    // Formula harus tetap dimuat agar cached result terakhir dari
                    // Excel dapat dibaca melalui getOldCalculatedValue().
                    $importedReader->setReadDataOnly(false);
                    $importedSheetName = $a00ExportItem?->assy_number ?: $activeMaterialSheetName;
                    $importedReader->setLoadSheetsOnly(array_values(array_unique([$importedSheetName, $activeMaterialSheetName])));
                    $importedWorkbook = $importedReader->load($importedWorkbookPath);
                    $importedMaterialSheet = $importedWorkbook->getSheetByName($importedSheetName)
                        ?: $importedWorkbook->getSheetByName($activeMaterialSheetName);
                }
            }

            foreach ($rows as $index => $row) {
                $excelRow = $index + 18;
                $partNumberKey = mb_strtolower(trim((string) ($row['part_no'] ?? '')), 'UTF-8');
                $lookupRowData = $firstRowByPartNumber[$partNumberKey] ?? $row;

                if ($importedMaterialSheet) {
                    foreach (range('L', 'R') as $column) {
                        $sheet->setCellValue(
                            "{$column}{$excelRow}",
                            $this->materialEditorCellValue(
                                $importedMaterialSheet->getCell("{$column}{$excelRow}")
                            )
                        );
                    }
                    continue;
                }

                foreach (['L' => 'amount1', 'O' => 'qty_moq', 'R' => 'import_tax'] as $column => $field) {
                    $rawValue = trim((string) ($lookupRowData[$field] ?? ''));
                    $sheet->setCellValue(
                        "{$column}{$excelRow}",
                        $rawValue === '' ? null : $this->toFloatValue($rawValue)
                    );
                }

                foreach (['M' => 'unit_price_basis', 'N' => 'currency', 'P' => 'cn_type', 'Q' => 'supplier'] as $column => $field) {
                    $sheet->setCellValueExplicit(
                        "{$column}{$excelRow}",
                        trim((string) ($lookupRowData[$field] ?? '')),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }
            }

            $lookupIndex = $spreadsheet->getIndex($lookupSheet);
            $spreadsheet->removeSheetByIndex($lookupIndex);
        }

        if ($a00ExportForm && $assyCount > 1) {
            foreach ($a00ExportForm->items->sortBy('line_number')->values() as $index => $item) {
                $sourceName = $index === 0 ? 'Material Cost' : 'Material Cost ('.($index + 1).')';
                $materialSheet = $spreadsheet->getSheetByName($sourceName);
                if (!$materialSheet) {
                    continue;
                }
                $safeSheetName = trim((string) preg_replace('~[\\\\/?*\[\]:]+~', '-', (string) $item->assy_number));
                $materialSheet->setTitle(mb_substr($safeSheetName ?: 'Assy '.($index + 1), 0, 31));
            }
        }

        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));
        $sheet->setSelectedCell('D18');
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $temporaryBasePath = tempnam(sys_get_temp_dir(), 'costing-v9-');
        abort_if($temporaryBasePath === false, 500, 'Gagal menyiapkan file export sementara.');
        $temporaryPath = $temporaryBasePath . '.xlsx';
        @unlink($temporaryBasePath);
        $writer->save($temporaryPath);
        $safeFilenamePart = static function (?string $value, string $fallback): string {
            $cleaned = trim((string) preg_replace('/[\\\\\/:*?"<>|]+/', '', (string) $value));
            return $cleaned !== '' ? $cleaned : $fallback;
        };
        $safeAssy = $safeFilenamePart($validated['assy_no'] ?? null, 'NO-ASSY');
        $safeCustomerCode = $safeFilenamePart($validated['customer_code'] ?? null, 'CUSTOMER');
        $filename = $exportMode === 'cogm'
            ? "COGM {$safeAssy} - {$safeCustomerCode}.xlsx"
            : "cogm. {$safeAssy} - {$safeCustomerCode}.xlsx";

        if ($exportMode === 'cogm') {
            $archiveRevisionIds = $a00ExportForm && $assyCount > 1
                ? $a00ExportForm->items->pluck('document_revision_id')->filter()->map(fn ($id) => (int) $id)->all()
                : array_filter([(int) ($validated['tracking_revision_id'] ?? 0)]);
            $archiveContents = file_get_contents($temporaryPath);
            abort_if($archiveContents === false, 500, 'File COGM gagal disiapkan untuk arsip.');
            foreach (DocumentRevision::whereIn('id', $archiveRevisionIds)->get() as $archiveRevision) {
                $archivePath = 'costing/cogm-exports/'.$archiveRevision->id.'/'.now()->format('YmdHis').'-'.Str::uuid().'.xlsx';
                Storage::disk('local')->put($archivePath, $archiveContents);
                if ($archiveRevision->cogm_export_file_path && $archiveRevision->cogm_export_file_path !== $archivePath) {
                    Storage::disk('local')->delete($archiveRevision->cogm_export_file_path);
                }
                $archiveRevision->update([
                    'cogm_export_original_name' => $filename,
                    'cogm_export_file_path' => $archivePath,
                    'cogm_exported_at' => now(),
                ]);
            }
        }

        return response()->download($temporaryPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'X-Costing-Assy-Count' => (string) $assyCount,
            'Access-Control-Expose-Headers' => 'Content-Disposition, X-Costing-Assy-Count'
        ])->deleteFileAfterSend(true);
    }

    private function fillGroupedMaterialSheets(
        Spreadsheet $spreadsheet,
        Worksheet $lookupSheet,
        ProjectA00Form $a00Form,
        int $activeRevisionId,
        string $exportMode,
        CostingImportService $importService,
        array $sharedValues,
        array $activeMaterialRows
    ): void {
        $activePricesByPart = [];
        foreach ($activeMaterialRows as $activeRow) {
            $partKey = mb_strtolower(trim((string) ($activeRow['part_no'] ?? '')), 'UTF-8');
            if ($partKey !== '' && !isset($activePricesByPart[$partKey])) {
                $activePricesByPart[$partKey] = $activeRow;
            }
        }
        $lookupKeys = [];
        $lookupRow = 2;
        while ($lookupRow <= max(2, $lookupSheet->getHighestRow()) && filled($lookupSheet->getCell("B{$lookupRow}")->getValue())) {
            $lookupKeys[mb_strtolower(trim((string) $lookupSheet->getCell("B{$lookupRow}")->getValue()), 'UTF-8')] = true;
            $lookupRow++;
        }

        foreach ($a00Form->items->sortBy('line_number')->values() as $index => $item) {
            $revisionId = (int) $item->document_revision_id;
            if ($revisionId === $activeRevisionId) {
                continue;
            }

            $sheetName = $index === 0 ? 'Material Cost' : 'Material Cost ('.($index + 1).')';
            $sheet = $spreadsheet->getSheetByName($sheetName);
            abort_unless($sheet, 422, "Template tidak memiliki sheet {$sheetName} untuk assy ke-".($index + 1).'.');

            $costing = CostingData::with(['materialBreakdowns' => fn ($query) => $query
                ->orderByRaw('CASE WHEN row_no IS NULL THEN 1 ELSE 0 END')
                ->orderBy('row_no')
                ->orderBy('id')])
                ->where('tracking_revision_id', $revisionId)
                ->latest('id')
                ->first();
            $materials = $costing?->materialBreakdowns ?? collect();
            if ($materials->isEmpty() && $exportMode === 'editor') {
                $partlistImport = $importService->preparePartlistImport(
                    ['tracking_revision_id' => $revisionId],
                    new Request()
                );
                $materials = collect($partlistImport['rows'] ?? [])->map(fn ($row) => (object) $row);
            }
            abort_if(
                $exportMode === 'cogm' && $materials->isEmpty(),
                422,
                'Material untuk assy '.($item->assy_number ?: ($index + 1)).' belum tersimpan. Simpan Form Costing assy tersebut terlebih dahulu.'
            );

            $revision = $item->projectRevision;
            $project = $item->project;
            $sheet->setCellValueExplicit('F5', (string) $item->assy_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F6', (string) $item->assy_name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F7', (string) ($project?->customer ?? $a00Form->customer), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F8', (string) ($item->model ?? $project?->model), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            if ($revision?->received_date) {
                $sheet->setCellValue('F9', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($revision->received_date));
                $sheet->getStyle('F9')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            if ($massProductionDate = $a00Form->resolvedMassProductionDate()) {
                $sheet->setCellValue('F11', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($massProductionDate));
                $sheet->getStyle('F11')->getNumberFormat()->setFormatCode('mmm-yy');
            } else {
                $sheet->setCellValueExplicit('F11', 'NEW', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $sheet->setCellValue('F12', (float) ($costing?->forecast ?? $sharedValues['forecast'] ?? 0));
            $sheet->setCellValue('F13', (float) ($costing?->project_period ?? $sharedValues['project_period'] ?? 0));
            $plant = trim((string) preg_replace('/^\s*\d+\s*[\-â€“â€”]\s*/u', '', (string) ($revision?->plant?->name ?? $costing?->line ?? $sharedValues['plant'] ?? '')));
            $sheet->setCellValueExplicit('F14', strtoupper($plant), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            foreach ([
                'N8' => $costing?->exchange_rate_usd ?? $sharedValues['rate_usd'] ?? 0,
                'N9' => $costing?->exchange_rate_jpy ?? $sharedValues['rate_jpy'] ?? 0,
                'N10' => $sharedValues['rate_idr'] ?? 1,
                'N11' => $costing?->lme_rate ?? $sharedValues['rate_lme'] ?? 0,
            ] as $cell => $value) {
                $sheet->setCellValue($cell, (float) ($value ?? 0));
            }
            if (!empty($sharedValues['rate_period'])) {
                $ratePeriod = strlen($sharedValues['rate_period']) === 7 ? $sharedValues['rate_period'].'-01' : $sharedValues['rate_period'];
                $sheet->setCellValue('N12', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTimeImmutable($ratePeriod)));
                $sheet->getStyle('N12')->getNumberFormat()->setFormatCode('mmm-yyyy');
            }

            $columnsToClear = ['C', 'D', 'F', 'G', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];
            foreach ($columnsToClear as $column) {
                for ($rowNumber = 18; $rowNumber <= 756; $rowNumber++) {
                    $sheet->setCellValue("{$column}{$rowNumber}", null);
                }
            }

            foreach ($materials as $rowIndex => $material) {
                abort_if($rowIndex >= 739, 422, 'Template Costing maksimal menampung 739 baris material per assy.');
                $excelRow = $rowIndex + 18;
                $sheet->setCellValue("C{$excelRow}", $rowIndex + 1);
                foreach (['D' => $material->part_no, 'F' => $material->id_code, 'G' => $material->part_name, 'J' => $material->unit, 'K' => $material->pro_code] as $column => $value) {
                    $sheet->setCellValueExplicit("{$column}{$excelRow}", (string) ($value ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $sheet->setCellValue("I{$excelRow}", $this->parseQuantityValue($material->qty_req ?? 0));
                $partKey = mb_strtolower(trim((string) ($material->part_no ?? '')), 'UTF-8');
                $activePrice = $activePricesByPart[$partKey] ?? [];
                $preferMaterialValue = static fn ($materialValue, $activeValue) => filled($materialValue)
                    ? $materialValue
                    : $activeValue;
                $price = [
                    'amount1' => $preferMaterialValue($material->amount1 ?? null, $activePrice['amount1'] ?? null),
                    'basis' => $preferMaterialValue($material->unit_price_basis_text ?? $material->unit_price_basis ?? null, $activePrice['unit_price_basis'] ?? ''),
                    'currency' => $preferMaterialValue($material->currency ?? null, $activePrice['currency'] ?? ''),
                    'qty_moq' => $preferMaterialValue($material->qty_moq ?? null, $activePrice['qty_moq'] ?? null),
                    'cn_type' => $preferMaterialValue($material->cn_type ?? null, $activePrice['cn_type'] ?? ''),
                    'supplier' => $preferMaterialValue($material->supplier ?? null, $activePrice['supplier'] ?? ''),
                    'import_tax' => $preferMaterialValue($material->import_tax_percent ?? $material->import_tax ?? null, $activePrice['import_tax'] ?? null),
                ];
                $sheet->setCellValue("L{$excelRow}", $price['amount1'] === null || $price['amount1'] === '' ? null : $this->toFloatValue($price['amount1']));
                $sheet->setCellValueExplicit("M{$excelRow}", (string) $price['basis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("N{$excelRow}", (string) $price['currency'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("O{$excelRow}", $price['qty_moq'] === null || $price['qty_moq'] === '' ? null : $this->toFloatValue($price['qty_moq']));
                $sheet->setCellValueExplicit("P{$excelRow}", (string) $price['cn_type'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("Q{$excelRow}", (string) $price['supplier'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("R{$excelRow}", $price['import_tax'] === null || $price['import_tax'] === '' ? null : $this->toFloatValue($price['import_tax']));
                if ($exportMode === 'editor') {
                    if (!isset($lookupKeys[$partKey])) {
                        $lookupKeys[$partKey] = true;
                        foreach (['part_no', null, 'id_code', 'part_name', null, 'qty_req', 'unit', 'pro_code'] as $offset => $field) {
                            $lookupSheet->setCellValue(
                                [$offset + 2, $lookupRow],
                                $field ? ($material->{$field} ?? '') : ''
                            );
                        }
                        $lookupRow++;
                    }
                }
            }
            $this->applyMaterialSheetFormulas($sheet, $materials->count(), $exportMode === 'editor');
        }
    }

    private function applyMaterialSheetFormulas(Worksheet $sheet, int $materialCount, bool $withPriceLookup): void
    {
        $lastRow = min(756, 17 + max(0, $materialCount));
        for ($excelRow = 18; $excelRow <= $lastRow; $excelRow++) {
            $sheet->setCellValue(
                "A{$excelRow}",
                '=IF(B'.$excelRow.'="","",IF(ISNUMBER(SEARCH("WIRE SEAL",B'.$excelRow.')),"Accessories",IF(ISNUMBER(SEARCH("SW SOLDER",B'.$excelRow.')),"Accessories",IF(ISNUMBER(SEARCH("SOLDER",B'.$excelRow.')),"Accessories",IF(ISNUMBER(SEARCH("Wire",B'.$excelRow.')),"Wire",IF(ISNUMBER(SEARCH("Tube",B'.$excelRow.')),"Tube",IF(ISNUMBER(SEARCH("Term",B'.$excelRow.')),"Terminal",IF(ISNUMBER(SEARCH("Conn",B'.$excelRow.')),"Connector",IF(ISNUMBER(SEARCH("Tape",B'.$excelRow.')),"Tape",IF(ISNUMBER(SEARCH("VTA",B'.$excelRow.')),"Tape",IF(ISNUMBER(SEARCH("Crosscheck",B'.$excelRow.')),"Crosscheck Item","Accessories")))))))))))'
            );
            $sheet->setCellValue(
                "B{$excelRow}",
                '=IFERROR(IF(D'.$excelRow.'="","",IFERROR(INDEX(MM60!$C:$C,MATCH(TRUE,ISNUMBER(SEARCH(CONCAT(" ",D'.$excelRow.'),MM60!$C:$C)),0)),IFERROR(INDEX(MM60!$C:$C,MATCH(F'.$excelRow.',MM60!$B:$B,0)),G'.$excelRow.'))),G'.$excelRow.')'
            );

            if ($withPriceLookup) {
                foreach (['L' => 'J', 'M' => 'K', 'N' => 'L', 'O' => 'M', 'P' => 'N', 'Q' => 'O', 'R' => 'P'] as $column => $lookupHeaderColumn) {
                    $sheet->setCellValue(
                        "{$column}{$excelRow}",
                        '=VLOOKUP($D'.$excelRow.',Lembar1!$B:$P,Lembar1!'.$lookupHeaderColumn.'$1,0)'
                    );
                }
            }
        }
    }

    public function rememberSelectedExchangeRate(Request $request)
    {
        $validated = $request->validate([
            'exchange_rate_id' => ['nullable', 'integer', 'exists:exchange_rates,id'],
            'selection_key' => ['required', 'string', 'regex:/^(costing|revision)_\d+$|^new$/'],
        ]);

        $selections = (array) session('costing_rate_selections', []);
        $selections[$validated['selection_key']] = (int) ($validated['exchange_rate_id'] ?? 0);
        session(['costing_rate_selections' => $selections]);

        return response()->json(['success' => true]);
    }

    public function importMaterialEditor(Request $request)
    {
        // Workbook Form Costing memuat sheet MM60 yang sangat besar. Import hanya
        // membutuhkan Material Cost, sehingga beri limit khusus dan jangan muat sheet lain.
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '1536M');

        $validated = $request->validate([
            'material_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
            'tracking_revision_id' => ['nullable', 'integer', 'exists:document_revisions,id'],
            'costing_data_id' => ['nullable', 'integer', 'exists:costing_data,id'],
            'forecast' => ['nullable', 'numeric', 'min:0'],
            'project_period' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate_usd' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate_jpy' => ['nullable', 'numeric', 'min:0'],
            'lme_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $resolvedRevisionId = isset($validated['tracking_revision_id'])
                ? (int) $validated['tracking_revision_id']
                : (int) (CostingData::find($validated['costing_data_id'] ?? null)?->tracking_revision_id ?? 0);
            $a00ImportForm = $resolvedRevisionId > 0
                ? ProjectA00Form::with('items')->whereHas('items', fn ($query) => $query
                    ->where('document_revision_id', $resolvedRevisionId))->first()
                : null;
            $a00ImportItems = $a00ImportForm?->items->sortBy('line_number')->values() ?? collect();
            $importMaterialSheetName = 'Material Cost';
            $legacyImportMaterialSheetName = 'Material Cost';
            if ($resolvedRevisionId > 0) {
                $a00Item = \App\Models\ProjectA00Item::where(
                    'document_revision_id',
                    $resolvedRevisionId
                )->first();
                if ($a00Item && (int) $a00Item->line_number > 1) {
                    $importMaterialSheetName = (string) $a00Item->assy_number;
                    $legacyImportMaterialSheetName = 'Material Cost ('.((int) $a00Item->line_number).')';
                } elseif ($a00Item) {
                    $importMaterialSheetName = (string) $a00Item->assy_number;
                }
            }
            $reader = IOFactory::createReaderForFile($validated['material_file']->getRealPath());
            $reader->setReadDataOnly(true);
            $groupSheetNames = $a00ImportItems->map(fn ($item) => (string) $item->assy_number)
                ->filter()
                ->values()
                ->all();
            $reader->setLoadSheetsOnly(array_values(array_unique(array_merge(
                [$importMaterialSheetName, $legacyImportMaterialSheetName],
                $groupSheetNames
            ))));
            $workbook = $reader->load($validated['material_file']->getRealPath());
            $sheet = $workbook->getSheetByName($importMaterialSheetName)
                ?: $workbook->getSheetByName($legacyImportMaterialSheetName);
            if (!$sheet) {
                return response()->json(['success' => false, 'message' => "Sheet {$importMaterialSheetName} tidak ditemukan. Gunakan file hasil Export Excel dari sistem."], 422);
            }
            $parsedActiveSheet = $this->parseMaterialEditorSheet($sheet);
            if ($parsedActiveSheet['errors']) {
                return response()->json(['success' => false, 'message' => 'File belum dapat diterapkan.', 'errors' => $parsedActiveSheet['errors']], 422);
            }
            $rows = $parsedActiveSheet['rows'];
            if ((float) ($validated['forecast'] ?? 0) <= 0 && $parsedActiveSheet['forecast'] > 0) {
                $validated['forecast'] = $parsedActiveSheet['forecast'];
            }
            if ((float) ($validated['project_period'] ?? 0) <= 0 && $parsedActiveSheet['project_period'] > 0) {
                $validated['project_period'] = $parsedActiveSheet['project_period'];
            }
            if ($resolvedRevisionId > 0 && $a00ImportItems->count() > 1) {
                $parsedSheets = [];
                foreach ($a00ImportItems as $item) {
                    $sheetName = (string) $item->assy_number;
                    $groupSheet = $workbook->getSheetByName($sheetName);
                    if (!$groupSheet) {
                        return response()->json(['success' => false, 'message' => "Sheet {$sheetName} tidak ditemukan. Gunakan file export grup A00 terbaru."], 422);
                    }
                    $parsed = $this->parseMaterialEditorSheet($groupSheet);
                    if ($parsed['errors']) {
                        return response()->json(['success' => false, 'message' => "Sheet {$sheetName} belum dapat diterapkan.", 'errors' => $parsed['errors']], 422);
                    }
                    $parsedSheets[(int) $item->document_revision_id] = $parsed;
                }

                $fileContents = file_get_contents($validated['material_file']->getRealPath());
                abort_if($fileContents === false, 500, 'File hasil edit tidak dapat disimpan.');
                $extension = strtolower((string) $validated['material_file']->getClientOriginalExtension());
                $groupUpdates = [];
                foreach ($a00ImportItems as $item) {
                    $revisionId = (int) $item->document_revision_id;
                    $revision = DocumentRevision::findOrFail($revisionId);
                    $path = 'costing-edits/'.$revision->id.'/'.now()->format('YmdHis').'-'.Str::uuid().'.'.$extension;
                    Storage::disk('local')->put($path, $fileContents);
                    if ($revision->costing_edit_file_path && $revision->costing_edit_file_path !== $path) {
                        Storage::disk('local')->delete($revision->costing_edit_file_path);
                    }
                    $revision->update([
                        'costing_edit_original_name' => $validated['material_file']->getClientOriginalName(),
                        'costing_edit_file_path' => $path,
                        'costing_edit_uploaded_at' => now(),
                    ]);
                    $parsed = $parsedSheets[$revisionId];
                    $context = $validated;
                    if ((float) ($context['forecast'] ?? 0) <= 0 && $parsed['forecast'] > 0) $context['forecast'] = $parsed['forecast'];
                    if ((float) ($context['project_period'] ?? 0) <= 0 && $parsed['project_period'] > 0) $context['project_period'] = $parsed['project_period'];
                    $saved = $this->persistMaterialEditorRows(
                        $revisionId, $parsed['rows'],
                        $revisionId === $resolvedRevisionId ? (int) ($validated['costing_data_id'] ?? 0) : null,
                        $context
                    );
                    $groupUpdates[] = ['revision_id' => $revisionId, 'assy_no' => (string) $item->assy_number, 'rows' => count($parsed['rows']), 'costing_data_id' => $saved->id];
                }
                $activeSaved = collect($groupUpdates)->firstWhere('revision_id', $resolvedRevisionId);
                return response()->json([
                    'success' => true,
                    'message' => 'Hasil edit '.count($groupUpdates).' assy dalam grup A00 berhasil diterapkan.',
                    'rows' => $rows,
                    'costing_data_id' => $activeSaved['costing_data_id'] ?? ($validated['costing_data_id'] ?? null),
                    'group_imported' => true,
                    'group_updates' => $groupUpdates,
                ]);
            }
            if ($resolvedRevisionId > 0) {
                $revision = DocumentRevision::findOrFail($resolvedRevisionId);
                $extension = strtolower((string) $validated['material_file']->getClientOriginalExtension());
                $path = $validated['material_file']->storeAs(
                    'costing-edits/' . $revision->id,
                    now()->format('YmdHis') . '-' . Str::uuid() . '.' . $extension,
                    'local'
                );
                abort_unless($path, 500, 'File hasil edit gagal disimpan.');

                if ($revision->costing_edit_file_path && $revision->costing_edit_file_path !== $path) {
                    Storage::disk('local')->delete($revision->costing_edit_file_path);
                }
                $revision->update([
                    'costing_edit_original_name' => $validated['material_file']->getClientOriginalName(),
                    'costing_edit_file_path' => $path,
                    'costing_edit_uploaded_at' => now(),
                ]);

                $persistedCostingData = $this->persistMaterialEditorRows(
                    (int) $revision->id,
                    $rows,
                    isset($validated['costing_data_id']) ? (int) $validated['costing_data_id'] : null,
                    $validated
                );
            }

            return response()->json([
                'success' => true,
                'message' => count($rows) . ' baris valid dan siap diterapkan.',
                'rows' => $rows,
                'costing_data_id' => isset($persistedCostingData) ? $persistedCostingData->id : ($validated['costing_data_id'] ?? null),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'File Excel tidak dapat dibaca: ' . $e->getMessage()], 422);
        }
    }

    private function materialEditorCellValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): mixed
    {
        // Hindari kalkulasi ulang ribuan formula saat import. Excel menyimpan hasil
        // kalkulasi terakhir di file; untuk input biasa, gunakan nilai sel langsung.
        if ($cell->isFormula()) {
            return $cell->getOldCalculatedValue() ?? '';
        }

        return $cell->getValue();
    }

    private function parseMaterialEditorSheet(Worksheet $sheet): array
    {
        $rows = [];
        $errors = [];
        $seenIds = [];
        $columnMap = [
            'part_no' => 'D', 'id_code' => 'F', 'part_name' => 'G', 'qty_req' => 'I',
            'unit' => 'J', 'pro_code' => 'K', 'amount1' => 'L', 'unit_price_basis' => 'M',
            'currency' => 'N', 'qty_moq' => 'O', 'cn_type' => 'P', 'supplier' => 'Q', 'import_tax' => 'R',
        ];

        for ($excelRow = 18; $excelRow <= $sheet->getHighestRow(); $excelRow++) {
            $rowId = (int) $this->materialEditorCellValue($sheet->getCell("C{$excelRow}"));
            if ($rowId <= 0) {
                break;
            }
            if (isset($seenIds[$rowId])) {
                $errors[] = "Baris {$excelRow}: Row ID {$rowId} duplikat.";
                continue;
            }
            $seenIds[$rowId] = true;
            $row = ['__row_no' => $rowId];
            foreach ($columnMap as $field => $column) {
                $row[$field] = trim((string) $this->materialEditorCellValue($sheet->getCell("{$column}{$excelRow}")));
            }
            $row['currency'] = strtoupper($row['currency']);
            $row['cn_type'] = strtoupper($row['cn_type']);
            if ($row['currency'] !== '' && !in_array($row['currency'], ['IDR', 'USD', 'JPY'], true)) {
                $errors[] = "Baris {$excelRow}: Currency harus IDR, USD, atau JPY.";
            }
            if ($row['cn_type'] !== '' && !in_array($row['cn_type'], ['N', 'C', 'E'], true)) {
                $errors[] = "Baris {$excelRow}: C/N harus N, C, atau E.";
            }
            foreach (['qty_req', 'amount1', 'qty_moq', 'import_tax'] as $numericField) {
                if ($row[$numericField] === '') {
                    continue;
                }
                if (!preg_match('/^-?[\d.,\s]+$/', $row[$numericField])) {
                    $errors[] = "Baris {$excelRow}: {$numericField} harus berupa angka.";
                } else {
                    $row[$numericField] = (string) $this->toFloatValue($row[$numericField]);
                }
            }
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
            'forecast' => $this->toFloatValue($this->materialEditorCellValue($sheet->getCell('F12'))),
            'project_period' => $this->toFloatValue($this->materialEditorCellValue($sheet->getCell('F13'))),
        ];
    }

    private function persistMaterialEditorRows(int $trackingRevisionId, array $rows, ?int $costingDataId = null, array $context = []): CostingData
    {
        $costingData = $costingDataId
            ? CostingData::whereKey($costingDataId)->first()
            : CostingData::where('tracking_revision_id', $trackingRevisionId)->latest('id')->first();

        if (!$costingData) {
            $revision = DocumentRevision::with(['project', 'plant'])->find($trackingRevisionId);
            $project = $revision?->project;
            if (!$revision || !$project || !$project->product_id) {
                throw new \RuntimeException('Proyek aktif belum lengkap sehingga data costing belum dapat dibuat.');
            }

            $customerName = trim((string) ($project->customer ?? ''));
            $customer = Customer::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($customerName)])
                ->orWhereRaw('LOWER(code) = ?', [Str::lower($customerName)])
                ->first();
            if (!$customer) {
                throw new \RuntimeException("Customer {$customerName} belum terdaftar pada master Customer.");
            }

            $latestRate = ExchangeRate::orderByDesc('period_date')->orderByDesc('id')->first();
            $costingData = CostingData::firstOrCreate(
                ['tracking_revision_id' => $trackingRevisionId],
                [
                    'product_id' => (int) $project->product_id,
                    'customer_id' => (int) $customer->id,
                    'period' => (string) ($revision->period ?: now()->format('Y-m')),
                    'line' => (string) ($revision->plant?->code ?? ''),
                    'model' => (string) ($project->model ?? ''),
                    'assy_no' => (string) ($project->part_number ?? ''),
                    'assy_name' => (string) ($project->part_name ?? ''),
                    'exchange_rate_id' => $latestRate?->id,
                    'exchange_rate_usd' => (float) ($latestRate?->usd_to_idr ?? 15500),
                    'exchange_rate_jpy' => (float) ($latestRate?->jpy_to_idr ?? 103),
                    'lme_rate' => (float) ($latestRate?->lme_copper ?? 0),
                    'rate_periode' => $latestRate?->period_date?->format('Y-m-d'),
                    'forecast' => max(0, (int) round((float) ($context['forecast'] ?? 0))),
                    'project_period' => max(0, (int) round((float) ($context['project_period'] ?? 0))),
                    'material_cost' => 0,
                    'labor_cost' => 0,
                    'overhead_cost' => 0,
                    'scrap_cost' => 0,
                    'revenue' => 0,
                    'qty_good' => 0,
                ]
            );
        }
        if (!$costingData) {
            throw new \RuntimeException('Data costing aktif tidak ditemukan. Muat ulang halaman lalu coba kembali.');
        }

        $costingData->fill(array_filter([
            'forecast' => isset($context['forecast']) ? max(0, (int) round((float) $context['forecast'])) : null,
            'project_period' => isset($context['project_period']) ? max(0, (int) round((float) $context['project_period'])) : null,
            'exchange_rate_usd' => isset($context['exchange_rate_usd']) ? max(0, (float) $context['exchange_rate_usd']) : null,
            'exchange_rate_jpy' => isset($context['exchange_rate_jpy']) ? max(0, (float) $context['exchange_rate_jpy']) : null,
            'lme_rate' => isset($context['lme_rate']) ? max(0, (float) $context['lme_rate']) : null,
        ], static fn ($value) => $value !== null));
        $costingData->save();

        $breakdowns = MaterialBreakdown::where('costing_data_id', $costingData->id)
            ->orderBy('id')
            ->get()
            ->values();
        $columns = array_fill_keys(\Illuminate\Support\Facades\Schema::getColumnListing('material_breakdowns'), true);

        DB::transaction(function () use ($rows, $breakdowns, $columns, $costingData) {
            $placeholderMaterial = null;
            foreach ($rows as $incoming) {
                $rowNo = (int) ($incoming['__row_no'] ?? 0);
                if ($rowNo <= 0) {
                    continue;
                }

                $breakdown = $breakdowns->firstWhere('row_no', $rowNo) ?? $breakdowns->get($rowNo - 1);

                $nullableNumber = fn (string $field): ?float => trim((string) ($incoming[$field] ?? '')) === ''
                    ? null
                    : round($this->toFloatValue($incoming[$field]), 6);
                $currency = strtoupper(trim((string) ($incoming['currency'] ?? '')));
                $cnType = strtoupper(trim((string) ($incoming['cn_type'] ?? '')));
                $basisText = trim((string) ($incoming['unit_price_basis'] ?? ''));

                $payload = [
                    'costing_data_id' => $costingData->id,
                    'row_no' => $rowNo,
                    'part_no' => trim((string) ($incoming['part_no'] ?? '')) ?: null,
                    'id_code' => trim((string) ($incoming['id_code'] ?? '')) ?: null,
                    'part_name' => trim((string) ($incoming['part_name'] ?? '')) ?: null,
                    'qty_req' => $this->parseQuantityValue($incoming['qty_req'] ?? 0),
                    'unit' => $this->normalizeUnitValue($incoming['unit'] ?? ''),
                    'pro_code' => trim((string) ($incoming['pro_code'] ?? '')),
                    'amount1' => $nullableNumber('amount1'),
                    'unit_price_basis' => $nullableNumber('unit_price_basis'),
                    'unit_price_basis_text' => $basisText === '' ? null : $basisText,
                    'currency' => $currency === '' ? null : $currency,
                    'qty_moq' => $nullableNumber('qty_moq'),
                    'cn_type' => $cnType === '' ? null : $cnType,
                    'supplier' => trim((string) ($incoming['supplier'] ?? '')) ?: null,
                    'import_tax_percent' => $nullableNumber('import_tax'),
                    'updated_at' => now(),
                ];

                $calculated = $this->calculateCogmMaterialAmount2([
                    'qty_req' => $payload['qty_req'],
                    'qty_moq' => $payload['qty_moq'] ?? 0,
                    'amount1' => $payload['amount1'] ?? 0,
                    'import_tax' => $payload['import_tax_percent'] ?? 0,
                    'cn_type' => $payload['cn_type'] ?? '',
                    'unit' => $payload['unit'] ?? '',
                    'unit_price_basis' => $payload['unit_price_basis_text'] ?? '',
                ], $costingData);
                $payload['amount2'] = $calculated['amount2'];
                $payload['currency2'] = $payload['currency'] ?: 'IDR';
                $payload['unit_price2'] = $calculated['amount2'];
                if (isset($columns['multiply_factor'])) {
                    $payload['multiply_factor'] = $calculated['multiply_factor'];
                }

                $payload = array_intersect_key($payload, $columns);
                if ($breakdown) {
                    $breakdown->newQuery()->whereKey($breakdown->id)->update($payload);
                    continue;
                }

                if (!$placeholderMaterial) {
                    $placeholderMaterial = Material::firstOrCreate(
                        ['material_code' => '__PLACEHOLDER__'],
                        [
                            'material_description' => null,
                            'base_uom' => 'PCS',
                            'currency' => 'IDR',
                            'price' => 0,
                        ]
                    );
                }
                $payload['material_id'] = $placeholderMaterial->id;
                MaterialBreakdown::create($payload);
            }
        });

        $costingData->material_cost = $this->calculateMaterialCostFromBreakdowns(
            $costingData->id,
            (float) $costingData->exchange_rate_usd,
            (float) $costingData->exchange_rate_jpy
        );
        $costingData->save();

        return $costingData;
    }

    public function recalculateMaterial(Request $request)
    {
        /*
         * RECALCULATE MATERIAL:
         * Proses berat dipindahkan ke tombol terpisah.
         * Di sini baru hitung ulang amount2, material_cost, dan total COGM terkait.
         */
        $validated = $request->validate([
            'costing_data_id' => ['required', 'integer', 'exists:costing_data,id'],
        ]);

        $costingData = CostingData::findOrFail((int) $validated['costing_data_id']);

        DB::beginTransaction();

        try {
            $rows = MaterialBreakdown::where('costing_data_id', $costingData->id)
                ->orderBy('row_no')
                ->get();

            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('material_breakdowns');
            $columnMap = array_fill_keys($columns, true);
            $now = now();
            $updatedRows = 0;

            foreach ($rows as $materialBreakdown) {
                $materialRow = [
                    'part_no' => $materialBreakdown->part_no,
                    'id_code' => $materialBreakdown->id_code,
                    'part_name' => $materialBreakdown->part_name,
                    'qty_req' => (float) ($materialBreakdown->qty_req ?? 0),
                    'unit' => $materialBreakdown->unit ?? '',
                    'pro_code' => $materialBreakdown->pro_code,
                    'amount1' => (float) ($materialBreakdown->amount1 ?? 0),
                    'unit_price_basis' => (float) ($materialBreakdown->unit_price_basis ?? 0),
                    'currency' => $materialBreakdown->currency ?? 'IDR',
                    'qty_moq' => (float) ($materialBreakdown->qty_moq ?? 0),
                    'cn_type' => $materialBreakdown->cn_type ?? 'N',
                    'supplier' => $materialBreakdown->supplier ?? '',
                    'import_tax' => (float) ($materialBreakdown->import_tax_percent ?? 0),
                ];

                $calculated = $this->calculateCogmMaterialAmount2($materialRow, $costingData);

                $payload = [
                    'amount2' => $calculated['amount2'],
                    'updated_at' => $now,
                ];

                if (isset($columnMap['multiply_factor'])) {
                    $payload['multiply_factor'] = $calculated['multiply_factor'];
                }

                if (isset($columnMap['currency2'])) {
                    $payload['currency2'] = $materialRow['currency'];
                }

                if (isset($columnMap['unit_price2'])) {
                    $payload['unit_price2'] = $calculated['amount2'];
                }

                $payload = array_intersect_key($payload, $columnMap);

                $materialBreakdown->fill($payload);
                $materialBreakdown->save();

                $updatedRows++;
            }

            $materialCost = $this->calculateMaterialCostFromExistingBreakdowns($costingData);

            $costingData->update([
                'material_cost' => $materialCost,
            ]);

            $revision = $costingData->tracking_revision_id
                ? DocumentRevision::find($costingData->tracking_revision_id)
                : null;

            if ($revision) {
                $importFile = $request->file('import_cogm_file');
                $path = $importFile->store('costing/cogm-imports/'.$revision->id, 'local');

                if ($revision->cogm_import_file_path && $revision->cogm_import_file_path !== $path) {
                    Storage::disk('local')->delete($revision->cogm_import_file_path);
                }

                $revision->update([
                    'cogm_import_original_name' => $importFile->getClientOriginalName(),
                    'cogm_import_file_path' => $path,
                    'cogm_import_uploaded_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'COGM berhasil dihitung ulang.',
                'material_cost' => $materialCost,
                'updated_rows' => $updatedRows,
                'version' => 'material-recalculate-v1',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('CostingController@recalculateMaterial error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung ulang COGM: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function calculateMaterialCostFromExistingBreakdowns(CostingData $costingData): float
    {
        return (float) MaterialBreakdown::where('costing_data_id', $costingData->id)
            ->sum('amount2');
    }



    private function toStoreCostingRequest(Request $request): StoreCostingRequest
    {
        /*
         * Route import-partlist / import-umh / import-cycle-time menerima Illuminate\Http\Request.
         * Method store() membutuhkan StoreCostingRequest karena memakai validated()
         * dan resolvedUpdateSection(), jadi request upload harus dikonversi dulu.
         */
        $storeRequest = StoreCostingRequest::createFrom($request);
        $storeRequest->setContainer(app());
        $storeRequest->setRedirector(app('redirect'));
        $storeRequest->setUserResolver($request->getUserResolver());
        $storeRequest->setRouteResolver($request->getRouteResolver());
        $storeRequest->validateResolved();

        return $storeRequest;
    }

}
