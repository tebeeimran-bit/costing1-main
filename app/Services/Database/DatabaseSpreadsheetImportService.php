<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatabaseSpreadsheetImportService
{
    public function importParts(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        if ($highestRow < 2) {
            return [
                'status' => 'warning',
                'message' => 'File template kosong. Isi minimal satu baris data.',
            ];
        }

        $expectedHeaders = [
            'plant', 'material_code', 'material_description', 'material_type', 'material_group',
            'base_uom', 'price', 'purchase_unit', 'currency', 'moq', 'cn', 'maker',
            'add_cost_import_tax', 'price_update', 'price_before',
        ];

        $created = 0;
        $totalRows = $highestRow - 1;

        for ($row = 2; $row <= $highestRow; $row++) {
            $payload = [
                'created_at' => now(),
                'updated_at' => now(),
                'price' => 0,
                'currency' => 'IDR',
            ];

            foreach ($expectedHeaders as $colIndex => $field) {
                $col = $colIndex + 1;
                if ($col > $highestColIndex) {
                    $payload[$field] = $field === 'base_uom' ? '' : null;
                    continue;
                }

                $cellRef = Coordinate::stringFromColumnIndex($col) . $row;
                $cell = $sheet->getCell($cellRef);
                $rawValue = trim((string) $cell->getFormattedValue());

                if (in_array($field, ['price', 'moq', 'add_cost_import_tax', 'price_before'], true)) {
                    $cellRawValue = $cell->getValue();
                    $payload[$field] = ($cellRawValue !== null && $cellRawValue !== '')
                        ? floatval($cellRawValue)
                        : (($field === 'price') ? 0 : null);
                    continue;
                }

                if ($field === 'price_update') {
                    $payload[$field] = $this->parseDateValue($rawValue);
                    continue;
                }

                if ($rawValue === '') {
                    if ($field === 'base_uom') {
                        $payload[$field] = '';
                    } elseif ($field !== 'currency') {
                        $payload[$field] = null;
                    }
                    continue;
                }

                $payload[$field] = $rawValue;
            }

            try {
                DB::table('materials')->insert($payload);
                $created++;
            } catch (\Throwable $e) {
                \Log::warning('Row ' . $row . ' insert failed: ' . $e->getMessage());
            }
        }

        return [
            'status' => 'success',
            'message' => "Import selesai. Total baris Excel: {$totalRows}, berhasil ditambahkan: {$created}.",
        ];
    }

    private function parseDateValue(string $rawValue): ?string
    {
        if ($rawValue === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($rawValue)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
