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
HandlesPartlistParsing
{
    private function buildUnpricedAggregationFromBreakdowns(int $costingDataId, $manualUnpricedPrices): array
    {
        $rows = MaterialBreakdown::with('material')
            ->where('costing_data_id', $costingDataId)
            ->get();

        $aggregation = [];

        foreach ($rows as $row) {
            $partNumber = trim((string) ($row->part_no ?? ''));
            if ($partNumber === '' || $partNumber === '-') {
                continue;
            }

            $partName = trim((string) ($row->part_name ?? ''));
            // Wire/tube parts: kosongkan part_name
            $upperPN = strtoupper($partName);
            if (in_array($upperPN, ['WIRE', 'TUBE']) || str_contains($upperPN, 'PENGIKAT WIRE')) {
                $partName = '';
            }
            if ($partName === '' || $partName === '-') {
                $partName = '';
            }

            $partKey = strtolower($partNumber);
            $manualPrice = floatval($manualUnpricedPrices->get($partKey, 0));
            $detectedPrice = floatval($row->material->price ?? 0);
            $rowAmount1 = floatval($row->amount1 ?? 0);
            $rowBasisPrice = floatval($row->unit_price_basis ?? 0);
            $isUnpriced = ($rowAmount1 <= 0)
                && ($rowBasisPrice <= 0)
                && ($manualPrice <= 0);

            if (!isset($aggregation[$partKey])) {
                $aggregation[$partKey] = [
                    'part_number' => $partNumber,
                    'part_name' => $partName,
                    'detected_price' => $detectedPrice,
                    'manual_price' => $manualPrice > 0 ? $manualPrice : null,
                    'is_unpriced' => false,
                ];
            } elseif (empty($aggregation[$partKey]['part_name']) && !empty($partName)) {
                // Update part_name if previously empty but current row has it
                $aggregation[$partKey]['part_name'] = $partName;
            }

            $aggregation[$partKey]['is_unpriced'] = $aggregation[$partKey]['is_unpriced'] || $isUnpriced;

            if ($manualPrice > 0) {
                $aggregation[$partKey]['manual_price'] = $manualPrice;
            }
        }

        return $aggregation;
    }

    private function buildUnpricedAggregationFromMaterialsInput(array $materialsInput, $manualUnpricedPrices): array
    {
        $aggregation = [];

        foreach ($materialsInput as $matData) {
            $partNo = trim((string) ($matData['part_no'] ?? ''));
            if ($partNo === '' || $partNo === '-') {
                continue;
            }

            $partKey = strtolower($partNo);
            $partName = trim((string) ($matData['part_name'] ?? ''));
            // Wire/tube parts: kosongkan part_name
            $upperPN = strtoupper($partName);
            if (in_array($upperPN, ['WIRE', 'TUBE']) || str_contains($upperPN, 'PENGIKAT WIRE')) {
                $partName = '';
            }
            if ($partName === '' || $partName === '-') {
                $partName = '';
            }

            $qtyReq = intval(round($this->toFloatValue($matData['qty_req'] ?? 0)));
            $amount1 = $this->toFloatValue($matData['amount1'] ?? 0);
            $unitPriceBasisRaw = trim((string) ($matData['unit_price_basis_text'] ?? $matData['unit_price_basis'] ?? ''));
            $unitPriceBasis = $this->toFloatValue($unitPriceBasisRaw);
            $manualPrice = floatval($manualUnpricedPrices->get($partKey, 0));

            $rowPartNo = trim((string) ($matData['part_no'] ?? ''));
            $rowIdCode = trim((string) ($matData['id_code'] ?? ''));
            $masterMaterial = $this->findMasterMaterial($rowPartNo, $rowIdCode);
            $detectedPrice = floatval($masterMaterial?->price ?? 0);

            $isUnpriced = ($amount1 <= 0)
                && ($unitPriceBasis <= 0)
                && ($manualPrice <= 0);

            if (!isset($aggregation[$partKey])) {
                $aggregation[$partKey] = [
                    'part_number' => $partNo,
                    'part_name' => $partName,
                    'detected_price' => $detectedPrice,
                    'manual_price' => $manualPrice > 0 ? $manualPrice : null,
                    'is_unpriced' => false,
                ];
            }

            $aggregation[$partKey]['is_unpriced'] = $aggregation[$partKey]['is_unpriced'] || $isUnpriced;

            if ($manualPrice > 0) {
                $aggregation[$partKey]['manual_price'] = $manualPrice;
            }
        }

        return $aggregation;
    }

    private function parsePartlistXlsx(string $filePath): array
    {
        if (class_exists(IOFactory::class)) {
            $rows = $this->parsePartlistWithPhpSpreadsheet($filePath);
            if (count($rows) > 0) {
                return $rows;
            }
        }

        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP zip belum aktif. Aktifkan ext-zip untuk import partlist XLSX.');
        }

        $zip = new ZipArchive();
        $tempCopyPath = null;
        $zipOpenResult = $zip->open($filePath);
        if ($zipOpenResult !== true) {
            $tempCopyPath = tempnam(sys_get_temp_dir(), 'partlist_');
            if ($tempCopyPath && @copy($filePath, $tempCopyPath)) {
                $retryZip = new ZipArchive();
                $retryResult = $retryZip->open($tempCopyPath);
                if ($retryResult === true) {
                    $zip = $retryZip;
                } else {
                    @unlink($tempCopyPath);
                    throw new \RuntimeException('File Excel tidak dapat dibuka (' . $this->zipOpenErrorToMessage((int) $retryResult) . ').');
                }
            } else {
                throw new \RuntimeException('File Excel tidak dapat dibuka (' . $this->zipOpenErrorToMessage((int) $zipOpenResult) . ').');
            }
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $workbookRelsXml === false) {
            $zip->close();
            throw new \RuntimeException('Struktur workbook tidak valid.');
        }

        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($workbookRelsXml);
        if (!$workbook || !$rels) {
            $zip->close();
            throw new \RuntimeException('Workbook tidak dapat diparse.');
        }

        $relNs = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $relationshipTargets = [];
        foreach ($rels->Relationship as $relationship) {
            $relationshipTargets[(string) ($relationship['Id'] ?? '')] = (string) ($relationship['Target'] ?? '');
        }

        $sheetTargets = [];
        if (isset($workbook->sheets->sheet)) {
            foreach ($workbook->sheets->sheet as $sheetNode) {
                $sheetAttrs = $sheetNode->attributes($relNs);
                $sheetRid = (string) ($sheetAttrs['id'] ?? '');
                if ($sheetRid === '' || empty($relationshipTargets[$sheetRid])) {
                    continue;
                }

                $sheetTargets[] = (string) $relationshipTargets[$sheetRid];
            }
        }

        if (count($sheetTargets) === 0) {
            $zip->close();
            throw new \RuntimeException('Sheet partlist tidak ditemukan.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $sharedStringsDoc = @simplexml_load_string($sharedStringsXml);
            if ($sharedStringsDoc && isset($sharedStringsDoc->si)) {
                foreach ($sharedStringsDoc->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = trim((string) $si->t);
                        continue;
                    }

                    $text = '';
                    foreach ($si->r as $run) {
                        $text .= (string) ($run->t ?? '');
                    }
                    $sharedStrings[] = trim($text);
                }
            }
        }

        $bestRows = [];
        $bestCount = 0;

        foreach ($sheetTargets as $sheetTarget) {
            $sheetPath = 'xl/' . ltrim((string) $sheetTarget, '/');
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                continue;
            }

            $sheet = @simplexml_load_string($sheetXml);
            if (!$sheet || !isset($sheet->sheetData->row)) {
                continue;
            }

            $rawRows = [];
            foreach ($sheet->sheetData->row as $row) {
                $rowNumber = (int) ($row['r'] ?? 0);
                $rowValues = [];
                foreach ($row->c as $cell) {
                    $cellRef = (string) ($cell['r'] ?? '');
                    $columnRef = preg_replace('/\d+/', '', $cellRef);
                    if ($columnRef === '') {
                        continue;
                    }

                    $columnIndex = $this->excelColumnToIndex($columnRef);
                    $value = $this->extractXlsxCellValue($cell, $sharedStrings);

                    $rowValues[$columnIndex] = $value;
                }

                if (!empty($rowValues)) {
                    $rowValues['__row'] = $rowNumber;
                    $rawRows[] = $rowValues;
                }
            }

            if (count($rawRows) === 0) {
                continue;
            }

            $mappedRows = $this->mapPartlistRowsToMaterials($rawRows);
            if (count($mappedRows) > $bestCount) {
                $bestRows = $mappedRows;
                $bestCount = count($mappedRows);
            }
        }

        $zip->close();
        if ($tempCopyPath) {
            @unlink($tempCopyPath);
        }

        if ($bestCount === 0) {
            return [];
        }

        return $bestRows;
    }

    private function parsePartlistWithPhpSpreadsheet(string $filePath): array
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (\Throwable $e) {
            return [];
        }

        $bestMaterials = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (!$sheet instanceof Worksheet) {
                continue;
            }

            $materials = $this->extractMaterialsFromFixedTemplateSheet($sheet);
            if (count($materials) > count($bestMaterials)) {
                $bestMaterials = $materials;
            }
        }

        // Fallback: read fixed template columns directly (D-J from row 12)
        // for files where header labels are missing/shifted but data rows exist.
        if (count($bestMaterials) === 0) {
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                if (!$sheet instanceof Worksheet) {
                    continue;
                }

                $materials = $this->extractMaterialsFromLooseFixedColumnsSheet($sheet);
                if (count($materials) > count($bestMaterials)) {
                    $bestMaterials = $materials;
                }
            }
        }

        return $bestMaterials;
    }

    private function extractMaterialsFromLooseFixedColumnsSheet(Worksheet $sheet): array
    {
        $highestDataRow = (int) $sheet->getHighestDataRow();
        $highestRow = (int) $sheet->getHighestRow();

        $scanEnd = max($highestDataRow, 12);
        if ($scanEnd < 12 && $highestRow >= 12) {
            $scanEnd = min($highestRow, 5000);
        } else {
            $scanEnd = min(max($scanEnd + 200, 200), 5000);
        }

        $skipPartNos = [
            'NO ASSY',
            'ASSY NAME',
            'CUSTOMER',
            'MODEL',
            'TANGGAL',
            'PIC ENGINEERING',
            'PIC MARKETING',
            'PART NO',
            'SUPPLIER PART NO',
            'ID CODE',
            'PART NAME',
            'QTY',
            'UNIT',
            'PRO CODE',
            'NO',
            'NO.',
            'NOMOR',
        ];

        $materials = [];
        $emptyStreak = 0;

        for ($row = 12; $row <= $scanEnd; $row++) {
            $rowNo = trim((string) $sheet->getCell('D' . $row)->getFormattedValue());
            $partNo = trim((string) $sheet->getCell('E' . $row)->getFormattedValue());
            $idCode = trim((string) $sheet->getCell('F' . $row)->getFormattedValue());
            $partName = trim((string) $sheet->getCell('G' . $row)->getFormattedValue());
            $qtyRaw = trim((string) $sheet->getCell('H' . $row)->getFormattedValue());
            $unit = trim((string) $sheet->getCell('I' . $row)->getFormattedValue());
            $proCode = trim((string) $sheet->getCell('J' . $row)->getFormattedValue());

            $qtyReq = $this->toFloatValue($qtyRaw);
            $hasRowNumber = $this->hasPartlistRowNumber($rowNo);

            if ($partNo === '' || $partNo === '-') {
                $partNo = $idCode;
            }

            $hasSignalData = ($partNo !== '' && $partNo !== '-')
                || ($idCode !== '' && $idCode !== '-')
                || ($partName !== '' && $partName !== '-')
                || $qtyReq > 0
                || $proCode !== '';

            if (!$hasRowNumber && !$hasSignalData) {
                $emptyStreak++;
                if ($emptyStreak >= 80) {
                    break;
                }
                continue;
            }

            $emptyStreak = 0;

            // Optional requirement: allow rows with no NO if it has signal data
            // Removed strict !$hasRowNumber requirement to permit blanks in NO column.
            
            $partNoUpper = strtoupper($partNo);
            $idCodeUpper = strtoupper($idCode);
            $partNameUpper = strtoupper($partName);
            if (in_array($partNoUpper, $skipPartNos, true)
                || in_array($idCodeUpper, $skipPartNos, true)
                || in_array($partNameUpper, $skipPartNos, true)) {
                continue;
            }

            $materials[] = [
                'row_no' => $rowNo,
                'part_no' => $partNo,
                'id_code' => $idCode !== '' && $idCode !== '-' ? $idCode : null,
                'part_name' => $partName,
                'qty_req' => round($qtyReq, 6),
                'unit' => $this->normalizeUnitValue($unit),
                'pro_code' => $proCode,
                'amount1' => 0,
                'unit_price_basis' => 0,
                'unit_price_basis_text' => null,
                'currency' => 'IDR',
                'qty_moq' => 0,
                'cn_type' => 'N',
                'supplier' => '',
                'import_tax' => 0,
            ];
        }

        return $materials;
    }

    private function extractMaterialsFromFixedTemplateSheet(Worksheet $sheet): array
    {
        $highestRow = (int) $sheet->getHighestDataRow();
        if ($highestRow < 12) {
            return [];
        }

        // Find header row (expect row 11) and map column indices dynamically
        $headerRowIndex = 11;
        $headerMap = [];
        
        $headerLabels = [
            'row_no' => ['NO', 'NO.', 'NOMOR'],
            'supplier_part_no' => ['SUPPLIER PART NO', 'PART NO', 'PARTLIST NO'],
            'id_code' => ['ID CODE', 'ID', 'KODE ID'],
            'part_name' => ['PART NAME', 'NAMA PART', 'DESKRIPSI'],
            'qty_req' => ['Q', 'QTY', 'QUANTITY', 'Q/ASSY'],
            'unit' => ['UNIT', 'UOM', 'SATUAN'],
            'pro_code' => ['PRO CODE', 'PROSES', 'PROCESS CODE'],
        ];

        // Scan row 11 to find headers
        for ($col = 1; $col <= 20; $col++) {
            $cellValue = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $headerRowIndex)->getFormattedValue());
            $cellValueUpper = strtoupper($cellValue);
            
            foreach ($headerLabels as $key => $aliases) {
                if (in_array($cellValueUpper, $aliases, true)) {
                    $headerMap[$key] = $col;
                    break;
                }
            }
        }

        // If we couldn't find headers, fallback to default columns (E-J)
        if (empty($headerMap)) {
            $headerMap = [
            'row_no' => 4,            // D
                'supplier_part_no' => 5,  // E
                'id_code' => 6,            // F
                'part_name' => 7,          // G
                'qty_req' => 8,            // H
                'unit' => 9,               // I
                'pro_code' => 10,          // J
            ];
        }

        $skipPartNos = [
            'NO ASSY',
            'ASSY NAME',
            'CUSTOMER',
            'MODEL',
            'TANGGAL',
            'PIC ENGINEERING',
            'PIC MARKETING',
            'PART NO',
            'SUPPLIER PART NO',
            'ID CODE',
            'PART NAME',
            'QTY',
            'UNIT',
            'PRO CODE',
        ];

        $materials = [];
        for ($row = 12; $row <= $highestRow; $row++) {
            $rowNo = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['row_no'] ?? 4) . $row)->getFormattedValue());
            $partNo = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['supplier_part_no'] ?? 5) . $row)->getFormattedValue());
            
            // Only read ID CODE if header was explicitly found, otherwise keep empty
            $idCode = '';
            if (isset($headerMap['id_code'])) {
                $idCode = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['id_code']) . $row)->getFormattedValue());
            }
            
            $partName = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['part_name'] ?? 7) . $row)->getFormattedValue());
            $qtyRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['qty_req'] ?? 8) . $row)->getFormattedValue());
            $unit = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['unit'] ?? 9) . $row)->getFormattedValue());
            $proCode = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap['pro_code'] ?? 10) . $row)->getFormattedValue());

            if ($partNo === '' || $partNo === '-') {
                $partNo = $idCode;
            }

            $qtyReq = intval(round($this->toFloatValue($qtyRaw)));
            $hasRowNumber = $this->hasPartlistRowNumber($rowNo);

            $isRowEmpty = ($partNo === '' || $partNo === '-')
                && ($idCode === '' || $idCode === '-')
                && $partName === ''
                && $qtyReq <= 0
                && $proCode === '';

            if ($isRowEmpty && !$hasRowNumber) {
                continue;
            }

            $partNoUpper = strtoupper($partNo);
            $idCodeUpper = strtoupper($idCode);
            $partNameUpper = strtoupper($partName);
            if (in_array($partNoUpper, $skipPartNos, true)
                || in_array($idCodeUpper, $skipPartNos, true)
                || in_array($partNameUpper, $skipPartNos, true)) {
                continue;
            }

            $materials[] = [
                'row_no' => $rowNo,
                'part_no' => $partNo,
                'id_code' => $idCode !== '' && $idCode !== '-' ? $idCode : null,
                'part_name' => $partName,
                'qty_req' => round($qtyReq, 6),
                'unit' => $this->normalizeUnitValue($unit),
                'pro_code' => $proCode,
                'amount1' => 0,
                'unit_price_basis' => 0,
                'unit_price_basis_text' => null,
                'currency' => 'IDR',
                'qty_moq' => 0,
                'cn_type' => 'N',
                'supplier' => '',
                'import_tax' => 0,
            ];
        }

        return $materials;
    }

    private function mapPartlistRowsToMaterials(array $rawRows): array
    {
        // Primary rule requested: fixed template columns D:J starting row 12.
        $fixedTemplateRows = $this->mapPartlistRowsByFixedTemplate($rawRows);
        if (count($fixedTemplateRows) > 0) {
            return $fixedTemplateRows;
        }

        $headerRowIndex = null;
        $headerMap = [];

        foreach ($rawRows as $rowIndex => $rowValues) {
            $candidate = [];
            foreach ($rowValues as $columnIndex => $rawValue) {
                if (!is_int($columnIndex)) {
                    continue;
                }

                $headerKey = $this->mapPartlistHeader((string) $rawValue);
                if ($headerKey !== null && !isset($candidate[$headerKey])) {
                    $candidate[$headerKey] = $columnIndex;
                }
            }

            if ((isset($candidate['part_no']) || isset($candidate['id_code']))
                && (isset($candidate['part_name']) || isset($candidate['qty_req']) || isset($candidate['unit']))) {
                $headerRowIndex = $rowIndex;
                $headerMap = $candidate;
                break;
            }
        }

        if ($headerRowIndex === null) {
            return $this->mapPartlistRowsByFixedTemplate($rawRows);
        }

        $materials = [];
        foreach (array_slice($rawRows, $headerRowIndex + 1) as $rowValues) {
            $rowNo = trim((string) $this->rowCellValue($rowValues, $headerMap, 'row_no'));
            $partNo = trim((string) $this->rowCellValue($rowValues, $headerMap, 'part_no'));
            $idCode = trim((string) $this->rowCellValue($rowValues, $headerMap, 'id_code'));
            if ($partNo === '' || $partNo === '-') {
                $partNo = $idCode;
            }

            $partName = trim((string) $this->rowCellValue($rowValues, $headerMap, 'part_name'));
            $qtyReq = intval(round($this->toFloatValue($this->rowCellValue($rowValues, $headerMap, 'qty_req'))));

            if (($partNo === '' || $partNo === '-') && ($idCode === '' || $idCode === '-') && $partName === '' && $qtyReq <= 0) {
                continue;
            }

            $unit = trim((string) $this->rowCellValue($rowValues, $headerMap, 'unit'));
            $currency = strtoupper(trim((string) $this->rowCellValue($rowValues, $headerMap, 'currency')));
            $cnType = strtoupper(trim((string) $this->rowCellValue($rowValues, $headerMap, 'cn_type')));

            $materials[] = [
                'row_no' => $rowNo,
                'part_no' => $partNo,
                'id_code' => ($idCode !== '' && $idCode !== '-') ? $idCode : null,
                'part_name' => $partName,
                'qty_req' => round($qtyReq, 6),
                'unit' => $this->normalizeUnitValue($unit),
                'pro_code' => trim((string) $this->rowCellValue($rowValues, $headerMap, 'pro_code')),
                // Keep price fields empty for partlist import; users fill via manual input or unpriced recap action.
                'amount1' => 0,
                'unit_price_basis' => 0,
                'unit_price_basis_text' => null,
                'currency' => in_array($currency, ['IDR', 'USD', 'JPY'], true) ? $currency : 'IDR',
                'qty_moq' => $this->toFloatValue($this->rowCellValue($rowValues, $headerMap, 'qty_moq')),
                'cn_type' => in_array($cnType, ['C', 'N', 'E'], true) ? $cnType : 'N',
                'supplier' => trim((string) $this->rowCellValue($rowValues, $headerMap, 'supplier')),
                'import_tax' => $this->toFloatValue($this->rowCellValue($rowValues, $headerMap, 'import_tax')),
            ];
        }

        return $materials;
    }

    private function mapPartlistRowsByFixedTemplate(array $rawRows): array
    {
        $skipPartNos = [
            'NO ASSY',
            'ASSY NAME',
            'CUSTOMER',
            'MODEL',
            'TANGGAL',
            'PIC ENGINEERING',
            'PIC MARKETING',
            'PART NO',
        ];

        // Find header row (row 11) and detect column mapping dynamically
        $headerRow = null;
        foreach ($rawRows as $rowValues) {
            $rowNumber = (int) ($rowValues['__row'] ?? 0);
            if ($rowNumber === 11) {
                $headerRow = $rowValues;
                break;
            }
        }

        $headerMap = [
            'row_no' => 3,              // Default D
            'supplier_part_no' => 4,  // Default E
            'id_code' => 5,            // Default F
            'part_name' => 6,          // Default G
            'qty_req' => 7,            // Default H
            'unit' => 8,               // Default I
            'pro_code' => 9,           // Default J
        ];

        // If we found header row, try to dynamically map columns
        if ($headerRow) {
            $headerLabels = [
                'row_no' => ['NO', 'NO.', 'NOMOR'],
                'supplier_part_no' => ['SUPPLIER PART NO', 'PART NO', 'PARTLIST NO'],
                'id_code' => ['ID CODE', 'ID', 'KODE ID'],
                'part_name' => ['PART NAME', 'NAMA PART', 'DESKRIPSI'],
                'qty_req' => ['Q', 'QTY', 'QUANTITY', 'Q/ASSY'],
                'unit' => ['UNIT', 'UOM', 'SATUAN'],
                'pro_code' => ['PRO CODE', 'PROSES', 'PROCESS CODE'],
            ];

            foreach ($headerRow as $colIndex => $cellValue) {
                if (!is_int($colIndex)) continue;
                
                $headerValueUpper = strtoupper(trim((string) $cellValue));
                foreach ($headerLabels as $field => $aliases) {
                    if (in_array($headerValueUpper, $aliases, true)) {
                        $headerMap[$field] = $colIndex;
                        break;
                    }
                }
            }
        }

        // Filter to data rows (12+)
        $rows = array_values(array_filter($rawRows, function ($rowValues) {
            $rowNumber = (int) ($rowValues['__row'] ?? 0);
            return $rowNumber >= 12;
        }));

        $materials = [];
        foreach ($rows as $rowValues) {
            $rowNo = trim((string) ($rowValues[$headerMap['row_no']] ?? ''));
            $partNo = trim((string) ($rowValues[$headerMap['supplier_part_no']] ?? ''));
            
            // Only read ID CODE if header was explicitly found
            $idCode = '';
            if (isset($headerMap['id_code'])) {
                $idCode = trim((string) ($rowValues[$headerMap['id_code']] ?? ''));
            }
            
            if ($partNo === '' || $partNo === '-') {
                $partNo = $idCode;
            }

            $partName = trim((string) ($rowValues[$headerMap['part_name']] ?? ''));
            $partNoUpper = strtoupper($partNo);
            $idCodeUpper = strtoupper($idCode);
            $qtyReq = intval(round($this->toFloatValue($rowValues[$headerMap['qty_req']] ?? '')));
            $hasRowNumber = $this->hasPartlistRowNumber($rowNo);

            $isRowEmpty = ($partNo === '' || $partNo === '-')
                && ($idCode === '' || $idCode === '-')
                && $partName === ''
                && $qtyReq <= 0;

            $isHeaderLike = in_array($partNoUpper, $skipPartNos, true)
                || in_array($idCodeUpper, $skipPartNos, true)
                || in_array(strtoupper($partName), $skipPartNos, true);

            if (($isRowEmpty && !$hasRowNumber) || $isHeaderLike) {
                continue;
            }

            $materials[] = [
                'row_no' => $rowNo,
                'part_no' => $partNo,
                'id_code' => ($idCode !== '' && $idCode !== '-') ? $idCode : null,
                'part_name' => $partName,
                'qty_req' => round($qtyReq, 6),
                'unit' => $this->normalizeUnitValue($rowValues[$headerMap['unit']] ?? ''),
                'pro_code' => trim((string) ($rowValues[$headerMap['pro_code']] ?? '')),
                'amount1' => 0,
                'unit_price_basis' => 0,
                'unit_price_basis_text' => null,
                'currency' => 'IDR',
                'qty_moq' => 0,
                'cn_type' => 'N',
                'supplier' => '',
                'import_tax' => 0,
            ];
        }

        return $materials;
    }

    private function hasPartlistRowNumber(string $value): bool
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return false;
        }

        return !in_array($normalized, ['NO', 'NO.', 'NOMOR'], true);
    }

    private function mapPartlistHeader(string $value): ?string
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($value)));
        if ($normalized === '') {
            return null;
        }

        $headerAliases = [
            'row_no' => ['no', 'nomor', 'rownumber', 'rowno', 'itemno', 'nomorurut', 'urut'],
            'part_no' => ['partno', 'partnumber', 'materialcode', 'partnumbermaterial', 'pn', 'partnumberno'],
            'id_code' => ['idcode', 'idmaterial', 'materialid', 'itemcode', 'id', 'kodepart', 'code'],
            'part_name' => ['partname', 'materialdescription', 'description', 'namapart'],
            'qty_req' => ['qtyreq', 'qtyrequired', 'qty', 'usageqty', 'qtyperassy', 'quantity', 'qtyneed', 'qtypcs'],
            'unit' => ['unit', 'uom', 'baseuom'],
            'pro_code' => ['procode', 'processcode', 'kodeproses', 'proc', 'process'],
            'amount1' => ['amount1', 'price', 'hargasatuan', 'materialprice'],
            'unit_price_basis' => ['unitpricebasis', 'basisprice', 'unitprice', 'pricebasis'],
            'currency' => ['currency', 'curr', 'matauang'],
            'qty_moq' => ['qtymoq', 'moq', 'minimumorderqty'],
            'cn_type' => ['cn', 'ctype', 'cntype', 'cndesc'],
            'supplier' => ['supplier', 'vendor', 'maker'],
            'import_tax' => ['importtax', 'importtaxpercent', 'taximport'],
        ];

        foreach ($headerAliases as $field => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $field;
            }
        }

        return null;
    }

    private function rowCellValue(array $rowValues, array $headerMap, string $field): string
    {
        if (!isset($headerMap[$field])) {
            return '';
        }

        $columnIndex = $headerMap[$field];
        return isset($rowValues[$columnIndex]) ? (string) $rowValues[$columnIndex] : '';
    }

    private function extractXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 's') {
            $sharedIndex = (int) ($cell->v ?? 0);
            return trim((string) ($sharedStrings[$sharedIndex] ?? ''));
        }

        if ($type === 'inlineStr') {
            if (isset($cell->is->t)) {
                return trim((string) $cell->is->t);
            }

            $richText = '';
            foreach ($cell->is->r as $run) {
                $richText .= (string) ($run->t ?? '');
            }

            return trim($richText);
        }

        if (isset($cell->v)) {
            return trim((string) $cell->v);
        }

        if (isset($cell->is->t)) {
            return trim((string) $cell->is->t);
        }

        if (isset($cell->f)) {
            // Fallback: if formula has no cached value, keep formula text instead of empty.
            return trim((string) $cell->f);
        }

        return '';
    }

    private function excelColumnToIndex(string $columnRef): int
    {
        $columnRef = strtoupper($columnRef);
        $index = 0;
        $length = strlen($columnRef);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($columnRef[$i]) - 64);
        }

        return $index - 1;
    }

    private function toFloatValue($value): float
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
                // Format Indonesia: 244.289,30 => 244289.30
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                // Format international: 244,289.30 => 244289.30
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma && !$hasDot) {
            // Format Indonesia tanpa ribuan: 244289,30 => 244289.30
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasDot && !$hasComma) {
            /*
             * Bisa berarti:
             * - raw decimal dari frontend: 244289.3
             * - ribuan Indonesia: 244.289
             *
             * Kalau angka setelah titik bukan tepat 3 digit, anggap titik sebagai desimal.
             * Kalau titik lebih dari satu, anggap sebagai ribuan.
             */
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

    private function parseQuantityValue($value): int|float
    {
        if (is_int($value)) {
            return max(0, $value);
        }
        if (is_float($value)) {
            return max(0, round($value, 6));
        }

        $text = trim((string) $value);
        // Input UI lama memakai satu titik/koma dengan tepat tiga digit sebagai
        // pemisah ribuan (3.500 / 15,000). Nilai desimal dari Excel tetap diproses
        // oleh parser numerik umum (misalnya 15403.5 atau 15.403,5).
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $text) === 1) {
            return max(0, (int) preg_replace('/[.,]/', '', $text));
        }

        $parsed = max(0, round($this->toFloatValue($value), 6));
        return floor($parsed) === $parsed ? (int) $parsed : $parsed;
    }

    private function parseNumericInput($value): float
    {
        return $this->toFloatValue($value);
    }

    private function decodeJsonArrayInput($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function calculateCogmMaterialAmount2(array $row, CostingData $costingData): array
    {
        $qtyReq = max(0.0, (float) ($row['qty_req'] ?? 0));
        $qtyMoq = max(0.0, (float) ($row['qty_moq'] ?? 0));
        $amount1 = max(0.0, (float) ($row['amount1'] ?? 0));
        $importTax = max(0.0, (float) ($row['import_tax'] ?? 0));
        $cnType = strtoupper(trim((string) ($row['cn_type'] ?? 'N')));
        $unit = strtoupper(trim((string) ($row['unit'] ?? '')));
        $priceBasis = strtoupper(trim((string) ($row['unit_price_basis'] ?? '')));

        $forecast = max(0.0, (float) ($costingData->forecast ?? 0));
        $projectPeriod = max(0.0, (float) ($costingData->project_period ?? 0));

        /*
         * Samakan dengan rumus JavaScript di form:
         * - Multiply Factor memakai divisor 1000 khusus UNIT = MM
         * - Amount 2 memakai Unit Price (Basis), sama seperti JavaScript.
         */
        $multiplyUnitDivisor = ($unit === 'MM') ? 1000.0 : 1.0;

        if ($qtyReq <= 0) {
            $multiplyFactor = 0.0;
        } else {
            $scheduledLifetimeQuantity = $costingData->tracking_revision_id
                ? (float) \App\Models\ProjectQuantityForecast::where('document_revision_id', $costingData->tracking_revision_id)->sum('quantity')
                : 0;
            $basisMultiplier = ($costingData->forecast_basis ?? 'per_month') === 'per_year' ? 1 : 12;
            $lifetimeQuantity = $scheduledLifetimeQuantity > 0
                ? $scheduledLifetimeQuantity
                : $forecast * $projectPeriod * $basisMultiplier;
            $denominator = $lifetimeQuantity * $qtyReq;
            $denominator = $denominator != 0.0 ? ($denominator / $multiplyUnitDivisor) : 0.0;
            $ratio = $denominator != 0.0 ? ($qtyMoq / $denominator) : 0.0;
            $multiplyFactor = ($cnType === 'C' || $ratio < 1) ? 1.0 : $ratio;
        }

        $base = $amount1 + ($amount1 * ($importTax / 100));
        $amountUnitDivisor = in_array($priceBasis, ['METER', 'M', 'MTR'], true) ? 1000.0 : 1.0;
        $amount2 = $amountUnitDivisor != 0.0 ? (($multiplyFactor * $base) / $amountUnitDivisor) : 0.0;

        return [
            'multiply_factor' => round($multiplyFactor, 8),
            'amount2' => round($amount2, 8),
        ];
    }

    private function calculateMaterialCostFromBreakdowns(int $costingDataId, float $usdRate, float $jpyRate): float
    {
        $usdRate = $usdRate > 0 ? $usdRate : 15500;
        $jpyRate = $jpyRate > 0 ? $jpyRate : 103;

        $rows = MaterialBreakdown::where('costing_data_id', $costingDataId)
            ->get(['qty_req', 'amount2', 'currency']);

        $total = 0.0;
        foreach ($rows as $row) {
            $qtyReq = max(0.0, (float) ($row->qty_req ?? 0));
            $amount2 = max(0.0, (float) ($row->amount2 ?? 0));
            $currency = strtoupper(trim((string) ($row->currency ?? 'IDR')));

            $rate = match ($currency) {
                'USD' => $usdRate,
                'JPY' => $jpyRate,
                default => 1.0,
            };

            $total += $qtyReq * $amount2 * $rate;
        }

        return round($total, 4);
    }

    private function uploadErrorCodeToMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'ukuran file melebihi batas upload_max_filesize server',
            UPLOAD_ERR_FORM_SIZE => 'ukuran file melebihi batas form',
            UPLOAD_ERR_PARTIAL => 'file hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'tidak ada file yang dipilih',
            UPLOAD_ERR_NO_TMP_DIR => 'folder temporary upload tidak tersedia',
            UPLOAD_ERR_CANT_WRITE => 'server gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'upload dibatalkan oleh ekstensi PHP',
            default => 'error upload tidak diketahui',
        };
    }

    private function zipOpenErrorToMessage(int $zipErrorCode): string
    {
        return match ($zipErrorCode) {
            ZipArchive::ER_NOZIP => 'format file bukan ZIP/XLSX yang valid',
            ZipArchive::ER_INCONS => 'struktur ZIP/XLSX tidak konsisten',
            ZipArchive::ER_READ => 'gagal membaca file',
            ZipArchive::ER_OPEN => 'file tidak bisa dibuka',
            ZipArchive::ER_NOENT => 'file tidak ditemukan',
            default => 'kode error ZIP: ' . $zipErrorCode,
        };
    }

    private function diagnosePartlistFile(string $filePath): string
    {
        if (!class_exists(IOFactory::class)) {
            return 'Parser PhpSpreadsheet tidak tersedia.';
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            return 'File terbaca tetapi gagal didiagnosa: ' . $e->getMessage();
        }

        $summary = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (!$sheet instanceof Worksheet) {
                continue;
            }

            $highestRow = (int) $sheet->getHighestDataRow();
            $candidates = 0;
            for ($r = 12; $r <= $highestRow; $r++) {
                $partNo = trim((string) $sheet->getCell('E' . $r)->getFormattedValue());
                $idCode = trim((string) $sheet->getCell('F' . $r)->getFormattedValue());
                $partName = trim((string) $sheet->getCell('G' . $r)->getFormattedValue());
                $qtyRaw = trim((string) $sheet->getCell('H' . $r)->getFormattedValue());
                $proCode = trim((string) $sheet->getCell('J' . $r)->getFormattedValue());
                $qtyReq = $this->toFloatValue($qtyRaw);

                $hasData = ($partNo !== '' && $partNo !== '-')
                    || ($idCode !== '' && $idCode !== '-')
                    || $partName !== ''
                    || $qtyReq > 0
                    || ($proCode !== '' && $proCode !== '-');

                if ($hasData) {
                    $candidates++;
                }
            }

            $summary[] = $sheet->getTitle() . ': rowData=' . $candidates . ', highestRow=' . $highestRow;
        }

        if (count($summary) === 0) {
            return 'Workbook tidak memiliki sheet yang dapat dibaca.';
        }

        return 'Diagnosa sheet -> ' . implode(' | ', $summary);
    }

    private function normalizeUnitValue($value): string
    {
        $unit = strtoupper(trim((string) $value));

        if ($unit === '') {
            return 'PCS';
        }

        return $unit;
    }

    private function validMasterMaterialsQuery()
    {
        $skipCodes = $this->materialMetaSkipCodes();

        return Material::query()
            ->whereNotNull('material_code')
            ->where('material_code', '!=', '')
            ->where('material_code', '!=', '-')
            ->where('material_code', 'not like', '__ROW_%')
            ->whereRaw('UPPER(material_code) NOT IN (' . implode(',', array_fill(0, count($skipCodes), '?')) . ')', $skipCodes);
    }

    private function findMasterMaterialFromCache($cache, ?string $partNo, ?string $idCode): ?Material
    {
        if ($cache === null) {
            return $this->findMasterMaterial($partNo, $idCode);
        }

        $partNo = trim((string) $partNo);
        $idCode = trim((string) $idCode);

        $candidates = array_values(array_unique(array_filter([$partNo, $idCode], function ($value) {
            $normalized = trim((string) $value);
            return $normalized !== '' && $normalized !== '-' && !$this->isMaterialMetaCode($normalized);
        })));

        foreach ($candidates as $code) {
            $match = $cache->get(Str::lower($code));
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function findMasterMaterial(?string $partNo, ?string $idCode): ?Material
    {
        $partNo = trim((string) $partNo);
        $idCode = trim((string) $idCode);

        $candidates = array_values(array_unique(array_filter([$partNo, $idCode], function ($value) {
            $normalized = trim((string) $value);
            return $normalized !== '' && $normalized !== '-' && !$this->isMaterialMetaCode($normalized);
        })));

        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $code) {
            $match = $this->validMasterMaterialsQuery()
                ->whereRaw('LOWER(material_code) = ?', [Str::lower($code)])
                ->first();

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function materialMetaSkipCodes(): array
    {
        return [
            'NO ASSY',
            'ASSY NAME',
            'CUSTOMER',
            'MODEL',
            'TANGGAL',
            'PIC ENGINEERING',
            'PIC MARKETING',
            'SUPPLIER PART NO',
            'PART NO',
            'ID CODE',
            'PART NAME',
            'QTY',
            'UNIT',
            'PRO CODE',
        ];
    }

    private function isMaterialMetaCode(string $code): bool
    {
        return in_array(strtoupper(trim($code)), $this->materialMetaSkipCodes(), true);
    }

}
