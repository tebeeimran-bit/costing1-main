<?php

namespace App\Services\Costing;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ZipArchive;

class ManualCogmWorkbookService
{
    public function extractSummary(string $path): array
    {
        $fastResult = $this->extractXlsxSummary($path);
        if ($fastResult !== null) return $fastResult;

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }
        $sheetNames = $reader->listWorksheetNames($path);
        $summarySheets = array_values(array_filter($sheetNames, static function (string $name): bool {
            return preg_match('/resume|cogm/i', $name) === 1;
        }));
        if ($summarySheets !== []) {
            $reader->setLoadSheetsOnly($summarySheets);
        }
        $workbook = $reader->load($path);
        $result = [];

        foreach ($workbook->getWorksheetIterator() as $sheet) {
            $maxRow = min((int) $sheet->getHighestDataRow(), 300);
            $maxColumn = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), 40);

            for ($row = 1; $row <= $maxRow; $row++) {
                for ($column = 1; $column <= $maxColumn; $column++) {
                    $label = $this->normalizeLabel((string) $sheet->getCell([$column, $row])->getFormattedValue());
                    $field = $this->fieldForLabel($label);
                    if (!$field || array_key_exists($field, $result)) continue;

                    $value = $this->findValueToRight($sheet, $row, $column, $maxColumn);
                    if ($value !== null) $result[$field] = $value;
                }
            }
        }

        $workbook->disconnectWorksheets();
        return $result;
    }

    private function extractXlsxSummary(string $path): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return null;
        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $relationsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $relationsXml === false) return null;

            preg_match_all('/<sheet\b[^>]*name="([^"]+)"[^>]*r:id="([^"]+)"[^>]*\/?\s*>/i', $workbookXml, $sheetMatches, PREG_SET_ORDER);
            preg_match_all('/<Relationship\b[^>]*Id="([^"]+)"[^>]*Target="([^"]+)"[^>]*\/?\s*>/i', $relationsXml, $relationMatches, PREG_SET_ORDER);
            $targets = [];
            foreach ($relationMatches as $match) $targets[$match[1]] = $match[2];

            $sheets = [];
            foreach ($sheetMatches as $match) {
                $name = html_entity_decode($match[1], ENT_QUOTES | ENT_XML1);
                $target = $targets[$match[2]] ?? null;
                if (!$target) continue;
                $target = ltrim(str_replace('\\', '/', $target), '/');
                if (!str_starts_with($target, 'xl/')) $target = 'xl/'.$target;
                $sheets[] = ['name' => $name, 'target' => $target];
            }
            $summarySheets = array_values(array_filter($sheets, fn ($sheet) => preg_match('/resume|cogm/i', $sheet['name']) === 1));
            if ($summarySheets !== []) $sheets = $summarySheets;

            $sharedStrings = $this->xlsxSharedStrings($zip);
            $result = [];
            foreach ($sheets as $sheet) {
                $xml = $zip->getFromName($sheet['target']);
                if ($xml === false) continue;
                $this->scanXlsxSheetXml($xml, $sharedStrings, $result);
                if (count($result) >= 5) break;
            }
            return $result;
        } finally {
            $zip->close();
        }
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $reader = new \XMLReader();
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        $strings = [];
        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'si') continue;
            $outer = $reader->readOuterXml();
            preg_match_all('/<t(?:\s[^>]*)?>(.*?)<\/t>/s', $outer, $matches);
            $strings[] = html_entity_decode(implode('', $matches[1] ?? []), ENT_QUOTES | ENT_XML1);
        }
        $reader->close();
        return $strings;
    }

    private function scanXlsxSheetXml(string $xml, array $sharedStrings, array &$result): void
    {
        preg_match_all('/<row\b([^>]*)>(.*?)<\/row>/s', $xml, $rows, PREG_SET_ORDER);
        foreach ($rows as $row) {
            preg_match('/\br="(\d+)"/', $row[1], $rowNumberMatch);
            if ((int) ($rowNumberMatch[1] ?? 0) > 300) break;
            $rowXml = $row[2];
            preg_match_all('/<c\b([^>]*?)(?<!\/)>(.*?)<\/c>/s', $rowXml, $cells, PREG_SET_ORDER);
            $values = [];
            foreach ($cells as $cell) {
                if (!preg_match('/\br="([A-Z]+)\d+"/', $cell[1], $coordinate)) continue;
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($coordinate[1]);
                if ($column > 40) continue;
                preg_match('/\bt="([^"]+)"/', $cell[1], $type);
                preg_match('/<v>(.*?)<\/v>/s', $cell[2], $rawValue);
                $raw = html_entity_decode($rawValue[1] ?? '', ENT_QUOTES | ENT_XML1);
                if (($type[1] ?? '') === 's') $raw = $sharedStrings[(int) $raw] ?? '';
                if (($type[1] ?? '') === 'inlineStr' && preg_match('/<t(?:\s[^>]*)?>(.*?)<\/t>/s', $cell[2], $inline)) {
                    $raw = html_entity_decode($inline[1], ENT_QUOTES | ENT_XML1);
                }
                $values[$column] = $raw;
            }
            foreach ($values as $column => $value) {
                $field = $this->fieldForLabel($this->normalizeLabel((string) $value));
                if (!$field || array_key_exists($field, $result)) continue;
                $candidates = [];
                for ($right = $column + 1; $right <= $column + 8; $right++) {
                    if (!array_key_exists($right, $values)) continue;
                    $number = $this->number($values[$right], (string) $values[$right]);
                    if ($number !== null) $candidates[] = $number;
                }
                if ($candidates !== []) $result[$field] = $this->amountCandidate($candidates);
            }
        }
    }

    private function fieldForLabel(string $label): ?string
    {
        return match (true) {
            $label === 'TOTAL MATERIAL COST' => 'material_cost',
            $label === 'PROCESS COST', $label === 'TOTAL PROCESS COST' => 'labor_cost',
            str_contains($label, 'TOOLING') && (str_contains($label, 'DEPRESIASI') || str_contains($label, 'DEPRECIATION')) => 'overhead_cost',
            $label === 'ADMINISTRATION COST', $label === 'ADMINISTRASI COST', $label === 'ADMIN COST' => 'scrap_cost',
            $label === 'COGM', $label === 'TOTAL COGM' => 'cogm_total',
            default => null,
        };
    }

    private function findValueToRight(Worksheet $sheet, int $row, int $labelColumn, int $maxColumn): ?float
    {
        $candidates = [];
        for ($column = $labelColumn + 1; $column <= min($labelColumn + 8, $maxColumn); $column++) {
            $cell = $sheet->getCell([$column, $row]);
            $value = $cell->getValue();
            if (is_string($value) && str_starts_with(trim($value), '=')) {
                $value = $cell->getOldCalculatedValue();
            }
            $number = $this->number($value, $cell->getFormattedValue());
            if ($number !== null) $candidates[] = $number;
        }
        return $candidates === [] ? null : $this->amountCandidate($candidates);
    }

    /**
     * Resume costing dapat menaruh Qty/jam, Amount, lalu persentase pada baris
     * yang sama. Nilai Amount adalah kandidat nominal terbesar pada baris itu.
     */
    private function amountCandidate(array $candidates): float
    {
        return (float) collect($candidates)->sortByDesc(fn (float $value) => abs($value))->first();
    }

    private function number(mixed $value, string $formatted): ?float
    {
        if (is_int($value) || is_float($value)) return (float) $value;
        $raw = trim(is_string($value) ? $value : '');
        if ($raw === '' || str_starts_with($raw, '=')) $raw = trim($formatted);
        $raw = preg_replace('/[^0-9,.-]/', '', $raw) ?? '';
        if ($raw === '' || !preg_match('/\d/', $raw)) return null;
        $comma = strrpos($raw, ','); $dot = strrpos($raw, '.');
        if ($comma !== false && $dot !== false) {
            $raw = $comma > $dot ? str_replace(',', '.', str_replace('.', '', $raw)) : str_replace(',', '', $raw);
        } elseif ($comma !== false) {
            $raw = str_replace(',', '.', $raw);
        }
        return is_numeric($raw) ? (float) $raw : null;
    }

    private function normalizeLabel(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtoupper($value)) ?? '');
    }
}
