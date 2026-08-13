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
HandlesCogmImport
{
    public function importPartlist(Request $request, CostingImportService $importService, CostingMaterialService $materialService, CostingPersistenceService $persistenceService, CostingStatusService $statusService, CostingResponseService $responseService)
    {
        $request->merge([
            'update_section' => 'material',
            'import_partlist' => 1,
        ]);

        $storeRequest = $this->toStoreCostingRequest($request);
        $response = $this->store($storeRequest, $importService, $materialService, $persistenceService, $statusService, $responseService);

        $redirect = $responseService->resolveRedirectTarget($response) ?: route('form', [], false);
        session()->reflash();

        return $responseService->buildThinRedirectPage($redirect);
    }

    public function importCogm(Request $request)
    {
        $validated = $request->validate([
            'import_cogm_file' => ['required', 'file', 'mimes:xls,xlsx'],
            'costing_data_id' => ['nullable', 'integer', 'exists:costing_data,id'],
            'tracking_revision_id' => ['nullable', 'integer', 'exists:document_revisions,id'],
            'business_category_id' => ['required_without:costing_data_id', 'nullable', 'integer', 'exists:business_categories,id'],
            'customer_id' => ['required_without:costing_data_id', 'nullable', 'integer', 'exists:customers,id'],
            'period' => ['required_without:costing_data_id', 'nullable', 'string'],
            'line' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'assy_no' => ['nullable', 'string'],
            'assy_name' => ['nullable', 'string'],
            'exchange_rate_usd' => ['nullable', 'numeric'],
            'exchange_rate_jpy' => ['nullable', 'numeric'],
            'lme_rate' => ['nullable', 'numeric'],
            'forecast' => ['nullable', 'integer'],
            'project_period' => ['nullable', 'integer'],
        ]);

        try {
            $costingData = $this->resolveCostingDataForCogmImport($request, $validated);
            $rows = $this->parseCogmExcelToMaterialRows($request->file('import_cogm_file')->getPathname());

            if (count($rows) === 0) {
                return back()->with('error', 'Data Material tidak ditemukan di file COGM. Pastikan file memiliki header seperti Part No, Part Name, Qty, Unit, Price, Currency.');
            }

            DB::beginTransaction();

            MaterialBreakdown::where('costing_data_id', $costingData->id)->delete();

            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('material_breakdowns');
            $columnMap = array_fill_keys($columns, true);
            $now = now();

            foreach ($rows as $index => $row) {
                $calculatedCogm = $this->calculateCogmMaterialAmount2($row, $costingData);

                $insert = [
                    'costing_data_id' => $costingData->id,
                    'row_no' => $index + 1,
                    'part_no' => $row['part_no'],
                    'id_code' => $row['id_code'],
                    'part_name' => $row['part_name'],
                    'qty_req' => $row['qty_req'],
                    'pro_code' => $row['pro_code'],
                    'amount1' => $row['amount1'],
                    'unit_price_basis' => $row['unit_price_basis'],
                    'unit_price_basis_text' => $row['unit_price_basis_text'],
                    'currency' => $row['currency'],
                    'qty_moq' => $row['qty_moq'],
                    'cn_type' => $row['cn_type'],
                    'import_tax_percent' => $row['import_tax'],
                    'amount2' => $calculatedCogm['amount2'],
                ];

                if (isset($columnMap['material_id'])) {
                    $insert['material_id'] = $this->resolveCogmMaterialId($row);
                }

                if (isset($columnMap['unit'])) {
                    $insert['unit'] = $row['unit'];
                }

                if (isset($columnMap['supplier'])) {
                    $insert['supplier'] = $row['supplier'];
                }

                if (isset($columnMap['multiply_factor'])) {
                    $insert['multiply_factor'] = $calculatedCogm['multiply_factor'];
                }

                if (isset($columnMap['currency2'])) {
                    $insert['currency2'] = $row['currency'];
                }

                if (isset($columnMap['unit_price2'])) {
                    $insert['unit_price2'] = $calculatedCogm['amount2'];
                }

                if (isset($columnMap['created_at'])) {
                    $insert['created_at'] = $now;
                }

                if (isset($columnMap['updated_at'])) {
                    $insert['updated_at'] = $now;
                }

                $insert = array_intersect_key($insert, $columnMap);

                DB::table('material_breakdowns')->insert($insert);
            }

            $materialCost = $this->calculateMaterialCostFromBreakdowns(
                (int) $costingData->id,
                (float) ($costingData->exchange_rate_usd ?? 15500),
                (float) ($costingData->exchange_rate_jpy ?? 103)
            );

            $costingData->update([
                'material_cost' => $materialCost,
            ]);

            DB::commit();

            return redirect(route('form', [
                'id' => $costingData->id,
                'tracking_revision_id' => $request->input('tracking_revision_id'),
            ], false))
                ->with('success', 'Import COGM berhasil. ' . count($rows) . ' baris material masuk ke tabel Material.');
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('CostingController@importCogm error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'Gagal import COGM: ' . $e->getMessage())->withInput();
        }
    }

    private function resolveCostingDataForCogmImport(Request $request, array $validated): CostingData
    {
        if (!empty($validated['costing_data_id'])) {
            return CostingData::findOrFail((int) $validated['costing_data_id']);
        }

        $trackingRevisionId = $validated['tracking_revision_id'] ?? null;

        if ($trackingRevisionId) {
            $existing = CostingData::where('tracking_revision_id', $trackingRevisionId)
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $businessCategory = BusinessCategory::findOrFail((int) $validated['business_category_id']);
        $productColumns = array_fill_keys(\Illuminate\Support\Facades\Schema::getColumnListing('products'), true);

        $productDefaults = [
            'name' => trim((string) $businessCategory->name),
        ];

        if (isset($productColumns['line'])) {
            $productDefaults['line'] = trim((string) $businessCategory->name);
        }

        $product = Product::firstOrCreate(
            ['code' => trim((string) $businessCategory->code)],
            array_intersect_key($productDefaults, $productColumns)
        );

        $productUpdates = [];
        if (isset($productColumns['name']) && trim((string) $product->name) !== trim((string) $businessCategory->name)) {
            $productUpdates['name'] = trim((string) $businessCategory->name);
        }
        if (isset($productColumns['line']) && trim((string) $product->line) !== trim((string) $businessCategory->name)) {
            $productUpdates['line'] = trim((string) $businessCategory->name);
        }
        if (!empty($productUpdates)) {
            $product->update($productUpdates);
        }

        $costingColumns = array_fill_keys(\Illuminate\Support\Facades\Schema::getColumnListing('costing_data'), true);
        $payload = [
            'product_id' => $product->id,
            'customer_id' => (int) $validated['customer_id'],
            'tracking_revision_id' => $trackingRevisionId,
            'period' => $validated['period'],
            'line' => $validated['line'] ?? null,
            'model' => $validated['model'] ?? null,
            'assy_no' => $validated['assy_no'] ?? null,
            'assy_name' => $validated['assy_name'] ?? null,
            'exchange_rate_usd' => (float) ($validated['exchange_rate_usd'] ?? 15500),
            'exchange_rate_jpy' => (float) ($validated['exchange_rate_jpy'] ?? 103),
            'lme_rate' => $request->filled('lme_rate') ? (float) $validated['lme_rate'] : null,
            'forecast' => (int) ($validated['forecast'] ?? 0),
            'project_period' => (int) ($validated['project_period'] ?? 0),
            'material_cost' => 0,
            'labor_cost' => 0,
            'overhead_cost' => 0,
            'scrap_cost' => 0,
            'revenue' => 0,
            'qty_good' => 0,
            'cycle_times' => [],
        ];

        $payload = array_intersect_key($payload, $costingColumns);

        return CostingData::create($payload);
    }


    private function updateMaterialMasterFromCogm(Material $material, array $row): void
    {
        $unit = $this->normalizeUnitValue($row['unit'] ?? '');
        $supplier = trim((string) ($row['supplier'] ?? ''));
        $currency = strtoupper(trim((string) ($row['currency'] ?? 'IDR')));
        $amount1 = (float) ($row['amount1'] ?? 0);
        $qtyMoq = (float) ($row['qty_moq'] ?? 0);
        $cnType = strtoupper(trim((string) ($row['cn_type'] ?? 'N')));
        $importTax = (float) ($row['import_tax'] ?? 0);

        $updates = [];

        if ($unit !== '') {
            $updates['base_uom'] = $unit;
            $updates['purchase_unit'] = $unit;
        }

        if ($supplier !== '') {
            $updates['maker'] = $supplier;
        }

        if (in_array($currency, ['IDR', 'USD', 'JPY'], true)) {
            $updates['currency'] = $currency;
        }

        if ($amount1 > 0) {
            $updates['price'] = $amount1;
        }

        if ($qtyMoq > 0) {
            $updates['moq'] = $qtyMoq;
        }

        if (in_array($cnType, ['C', 'N'], true)) {
            $updates['cn'] = $cnType;
        }

        if ($importTax > 0) {
            $updates['add_cost_import_tax'] = $importTax;
        }

        if (!empty($updates)) {
            $material->fill($updates);
            $material->save();
        }
    }

    private function resolveCogmMaterialId(array $row): int
    {
        $partNo = trim((string) ($row['part_no'] ?? ''));
        $idCode = trim((string) ($row['id_code'] ?? ''));
        $partName = trim((string) ($row['part_name'] ?? ''));
        $unit = $this->normalizeUnitValue($row['unit'] ?? 'PCS');
        $currency = strtoupper(trim((string) ($row['currency'] ?? 'IDR')));
        $amount1 = (float) ($row['amount1'] ?? 0);
        $qtyMoq = (float) ($row['qty_moq'] ?? 0);
        $cnType = strtoupper(trim((string) ($row['cn_type'] ?? 'N')));
        $supplier = trim((string) ($row['supplier'] ?? ''));
        $importTax = (float) ($row['import_tax'] ?? 0);

        $existing = $this->findMasterMaterial($partNo, $idCode);
        if ($existing) {
            $this->updateMaterialMasterFromCogm($existing, $row);

            return (int) $existing->id;
        }

        $code = $partNo !== '' && $partNo !== '-' ? $partNo : $idCode;
        if ($code === '' || $code === '-') {
            $code = '__COGM_' . Str::uuid()->toString();
        }

        $material = Material::query()
            ->whereRaw('LOWER(material_code) = ?', [Str::lower($code)])
            ->first();

        if ($material) {
            $this->updateMaterialMasterFromCogm($material, $row);

            return (int) $material->id;
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('materials');
        $columnMap = array_fill_keys($columns, true);
        $now = now();

        $payload = [
            'material_code' => $code,
            'material_description' => $partName !== '' ? $partName : $code,
            'base_uom' => $unit,
            'purchase_unit' => $unit,
            'currency' => in_array($currency, ['IDR', 'USD', 'JPY'], true) ? $currency : 'IDR',
            'price' => $amount1,
            'moq' => $qtyMoq,
            'cn' => in_array($cnType, ['C', 'N'], true) ? $cnType : 'N',
            'maker' => $supplier,
            'add_cost_import_tax' => $importTax,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $payload = array_intersect_key($payload, $columnMap);

        return (int) DB::table('materials')->insertGetId($payload);
    }

    private function parseCogmExcelToMaterialRows(string $filePath): array
    {
        if (!class_exists(IOFactory::class)) {
            throw new \RuntimeException('Parser PhpSpreadsheet tidak tersedia.');
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $bestRows = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (!$sheet instanceof Worksheet) {
                continue;
            }

            $fixedRows = $this->extractCogmRowsFromFixedColumnsSheet($sheet);
            $headerRows = $this->extractCogmRowsFromSheet($sheet);

            $rows = count($fixedRows) >= count($headerRows) ? $fixedRows : $headerRows;

            if (count($rows) > count($bestRows)) {
                $bestRows = $rows;
            }
        }

        return $bestRows;
    }

    private function extractCogmRowsFromSheet(Worksheet $sheet): array
    {
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headerRow = null;
        $headerMap = [];

        for ($row = 1; $row <= min($highestRow, 100); $row++) {
            $candidateMap = [];

            for ($col = 1; $col <= $highestColumn; $col++) {
                $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue());
                $field = $this->mapCogmHeader($value);

                if ($field !== null && !isset($candidateMap[$field])) {
                    $candidateMap[$field] = $col;
                }
            }

            if (
                (isset($candidateMap['part_no']) || isset($candidateMap['id_code']))
                && (isset($candidateMap['part_name']) || isset($candidateMap['qty_req']))
            ) {
                $headerRow = $row;
                $headerMap = $candidateMap;
                break;
            }
        }

        if ($headerRow === null) {
            return [];
        }

        $rows = [];
        $emptyStreak = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $partNo = $this->getCogmCell($sheet, $headerMap, 'part_no', $row);
            $idCode = $this->getCogmCell($sheet, $headerMap, 'id_code', $row);
            $partName = $this->getCogmCell($sheet, $headerMap, 'part_name', $row);
            $qtyReq = $this->toCogmFloatValue($this->getCogmCell($sheet, $headerMap, 'qty_req', $row));
            $unit = $this->normalizeUnitValue($this->getCogmCell($sheet, $headerMap, 'unit', $row));
            $proCode = $this->getCogmCell($sheet, $headerMap, 'pro_code', $row);
            $amount1 = $this->toCogmFloatValue($this->getCogmCell($sheet, $headerMap, 'amount1', $row));
            $unitPriceBasisText = $this->getCogmCell($sheet, $headerMap, 'unit_price_basis_text', $row);
            $unitPriceBasis = $this->getCogmNumericCell($sheet, 'L' . $row);
            $currency = strtoupper($this->getCogmCell($sheet, $headerMap, 'currency', $row));
            $qtyMoq = $this->toCogmFloatValue($this->getCogmCell($sheet, $headerMap, 'qty_moq', $row));
            $cnType = strtoupper($this->getCogmCell($sheet, $headerMap, 'cn_type', $row));
            $supplier = $this->getCogmCell($sheet, $headerMap, 'supplier', $row);
            $importTax = $this->toCogmFloatValue($this->getCogmCell($sheet, $headerMap, 'import_tax', $row));

            if ($partNo === '' && $idCode !== '') {
                $partNo = $idCode;
            }

            $hasData = $partNo !== ''
                || $idCode !== ''
                || $partName !== ''
                || $qtyReq > 0
                || $amount1 > 0
                || $unitPriceBasis > 0;

            if (!$hasData) {
                $emptyStreak++;

                if ($emptyStreak >= 50) {
                    break;
                }

                continue;
            }

            $emptyStreak = 0;

            if ($partNo === '' && $partName === '') {
                continue;
            }

            if (!in_array($currency, ['IDR', 'USD', 'JPY'], true)) {
                $currency = 'IDR';
            }

            if (!in_array($cnType, ['C', 'N'], true)) {
                $cnType = 'N';
            }

            $multiplyFactor = 1.0;
            $base = $amount1 + ($amount1 * ($importTax / 100));
            $amount2 = $base * $multiplyFactor;

            $rows[] = [
                'part_no' => $partNo,
                'id_code' => $idCode !== '' ? $idCode : null,
                'part_name' => $partName,
                'qty_req' => round($qtyReq, 6),
                'unit' => $unit,
                'pro_code' => $proCode,
                'amount1' => round($amount1, 6),
                'unit_price_basis' => round($unitPriceBasis, 6),
                'unit_price_basis_text' => $unitPriceBasisText !== '' ? $unitPriceBasisText : null,
                'currency' => $currency,
                'qty_moq' => round($qtyMoq, 6),
                'cn_type' => $cnType,
                'supplier' => $supplier,
                'import_tax' => round($importTax, 6),
                'amount2' => round($amount2, 6),
            ];
        }

        return $rows;
    }


    private function toCogmFloatValue($value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        $normalized = preg_replace('/[^0-9,\.\-]/', '', $raw);
        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return 0;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            $lastCommaPos = strrpos($normalized, ',');
            $lastDotPos = strrpos($normalized, '.');

            if ($lastCommaPos !== false && $lastDotPos !== false && $lastCommaPos > $lastDotPos) {
                // Format Indonesia: 1.305,46548132
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                // Format international: 1,305.46548132
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma && !$hasDot) {
            // Koma sebagai desimal, contoh: 1305,46548132
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasDot && !$hasComma) {
            // Titik bisa desimal atau ribuan.
            // Penting: 0.012 adalah desimal, jangan diubah menjadi 12.
            $dotCount = substr_count($normalized, '.');
            $lastDotPos = strrpos($normalized, '.');
            $digitsAfterLastDot = $lastDotPos === false ? 0 : strlen($normalized) - $lastDotPos - 1;
            $digitsBeforeLastDot = $lastDotPos === false ? 0 : strlen(ltrim(substr($normalized, 0, $lastDotPos), '-'));

            $looksLikeLeadingDecimal = preg_match('/^-?0\.\d+$/', $normalized) === 1;
            $looksLikeThousands = !$looksLikeLeadingDecimal
                && ($dotCount > 1 || ($digitsAfterLastDot === 3 && $digitsBeforeLastDot > 1));

            if ($looksLikeThousands) {
                $normalized = str_replace('.', '', $normalized);
            }
        }

        return is_numeric($normalized) ? (float) $normalized : 0;
    }


    private function getCogmNumericCell(Worksheet $sheet, string $cellAddress): float
    {
        $cell = $sheet->getCell($cellAddress);

        try {
            $rawValue = $cell->getCalculatedValue();
        } catch (\Throwable $e) {
            $rawValue = $cell->getValue();
        }

        if (is_int($rawValue) || is_float($rawValue)) {
            return (float) $rawValue;
        }

        if (is_string($rawValue) && is_numeric(trim($rawValue))) {
            return (float) trim($rawValue);
        }

        return $this->toCogmFloatValue($cell->getFormattedValue());
    }

    private function extractCogmRowsFromFixedColumnsSheet(Worksheet $sheet): array
    {
        $highestDataRow = (int) $sheet->getHighestDataRow();
        $highestRow = (int) $sheet->getHighestRow();

        $scanEnd = max($highestDataRow, 12);
        if ($scanEnd < 12 && $highestRow >= 12) {
            $scanEnd = min($highestRow, 5000);
        } else {
            $scanEnd = min(max($scanEnd + 200, 200), 5000);
        }

        $rows = [];
        $emptyStreak = 0;

        for ($row = 12; $row <= $scanEnd; $row++) {
            /*
             * Fixed COGM format:
             * E = Part No
             * F = ID Code
             * G = Part Name
             * H = Qty Req
             * I = Unit
             * J = Pro Code
             * K = Amount 1 / Price
             * L = Unit Price (Basis)
             * M = Currency
             * N = Qty MOQ
             * O = C/N
             * P = Supplier
             * Q = Import Tax (%)
             */
            $partNo = trim((string) $sheet->getCell('E' . $row)->getFormattedValue());
            $idCode = trim((string) $sheet->getCell('F' . $row)->getFormattedValue());
            $partName = trim((string) $sheet->getCell('G' . $row)->getFormattedValue());
            $qtyReq = $this->getCogmNumericCell($sheet, 'H' . $row);
            $unit = $this->normalizeUnitValue($sheet->getCell('I' . $row)->getFormattedValue());
            $proCode = trim((string) $sheet->getCell('J' . $row)->getFormattedValue());
            $amount1 = $this->getCogmNumericCell($sheet, 'K' . $row);
            $unitPriceBasisText = trim((string) $sheet->getCell('L' . $row)->getFormattedValue());
            $unitPriceBasis = $this->getCogmNumericCell($sheet, 'L' . $row);
            $currency = strtoupper(trim((string) $sheet->getCell('M' . $row)->getFormattedValue()));
            $qtyMoq = $this->getCogmNumericCell($sheet, 'N' . $row);
            $cnType = strtoupper(trim((string) $sheet->getCell('O' . $row)->getFormattedValue()));
            $supplier = trim((string) $sheet->getCell('P' . $row)->getFormattedValue());
            $importTax = $this->getCogmNumericCell($sheet, 'Q' . $row);

            if ($partNo === '' && $idCode !== '') {
                $partNo = $idCode;
            }

            $hasData = $partNo !== ''
                || $idCode !== ''
                || $partName !== ''
                || $qtyReq > 0
                || $amount1 > 0
                || $unitPriceBasis > 0
                || $qtyMoq > 0
                || $supplier !== '';

            if (!$hasData) {
                $emptyStreak++;
                if ($emptyStreak >= 80) {
                    break;
                }
                continue;
            }

            $emptyStreak = 0;

            $headerLikeValues = [
                'PART NO',
                'ID CODE',
                'PART NAME',
                'QTY',
                'QTY REQ',
                'UNIT',
                'PRO CODE',
                'AMOUNT 1',
                'UNIT PRICE',
                'UNIT PRICE BASIS',
                'CURRENCY',
                'QTY MOQ',
                'C/N',
                'SUPPLIER',
                'IMPORT TAX',
            ];

            if (in_array(strtoupper($partNo), $headerLikeValues, true)
                || in_array(strtoupper($idCode), $headerLikeValues, true)
                || in_array(strtoupper($partName), $headerLikeValues, true)) {
                continue;
            }

            if ($partNo === '' && $partName === '') {
                continue;
            }

            if (!in_array($currency, ['IDR', 'USD', 'JPY'], true)) {
                $currency = 'IDR';
            }

            if (!in_array($cnType, ['C', 'N'], true)) {
                $cnType = 'N';
            }

            $base = $amount1 + ($amount1 * ($importTax / 100));
            $amount2 = $base;

            $rows[] = [
                'part_no' => $partNo,
                'id_code' => $idCode !== '' ? $idCode : null,
                'part_name' => $partName,
                'qty_req' => round($qtyReq, 6),
                'unit' => $unit,
                'pro_code' => $proCode,
                'amount1' => round($amount1, 6),
                'unit_price_basis' => round($unitPriceBasis, 6),
                'unit_price_basis_text' => $unitPriceBasisText !== '' ? $unitPriceBasisText : null,
                'currency' => $currency,
                'qty_moq' => round($qtyMoq, 6),
                'cn_type' => $cnType,
                'supplier' => $supplier,
                'import_tax' => round($importTax, 6),
                'amount2' => round($amount2, 6),
            ];
        }

        return $rows;
    }


    private function mapCogmHeader(string $value): ?string
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($value)));

        if ($normalized === '') {
            return null;
        }

        $aliases = [
            'part_no' => [
                'partno',
                'partnumber',
                'supplierpartno',
                'materialcode',
                'itemcode',
                'kodebarang',
                'kodepart',
            ],
            'id_code' => [
                'idcode',
                'id',
                'kodeid',
                'code',
                'idmaterial',
            ],
            'part_name' => [
                'partname',
                'description',
                'materialdescription',
                'namapart',
                'itemname',
                'partdescription',
            ],
            'qty_req' => [
                'qty',
                'qtyreq',
                'quantity',
                'qtypcs',
                'qassy',
                'qtyassy',
                'usageqty',
            ],
            'unit' => [
                'unit',
                'uom',
                'satuan',
                'baseuom',
            ],
            'pro_code' => [
                'procode',
                'processcode',
                'process',
                'kodeproses',
            ],
            'amount1' => [
                'amount1',
                'price',
                'unitprice',
                'harga',
                'hargasatuan',
                'materialprice',
                'pricebasis',
            ],
            'unit_price_basis_text' => [
                'unitpricebasis',
                'basis',
                'purchaseunit',
                'unitbasis',
            ],
            'currency' => [
                'currency',
                'curr',
                'matauang',
            ],
            'qty_moq' => [
                'qtymoq',
                'moq',
                'minimumorderqty',
            ],
            'cn_type' => [
                'cn',
                'cntype',
                'ctype',
            ],
            'supplier' => [
                'supplier',
                'vendor',
                'maker',
            ],
            'import_tax' => [
                'importtax',
                'importtaxpercent',
                'tax',
                'addcost',
                'addcostimporttax',
            ],
        ];

        foreach ($aliases as $field => $fieldAliases) {
            if (in_array($normalized, $fieldAliases, true)) {
                return $field;
            }
        }

        return null;
    }

    private function getCogmCell(Worksheet $sheet, array $headerMap, string $field, int $row): string
    {
        if (!isset($headerMap[$field])) {
            return '';
        }

        $column = Coordinate::stringFromColumnIndex((int) $headerMap[$field]);

        return trim((string) $sheet->getCell($column . $row)->getFormattedValue());
    }

}
