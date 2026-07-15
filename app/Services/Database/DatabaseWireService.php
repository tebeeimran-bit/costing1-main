<?php

namespace App\Services\Database;

use App\Models\Wire;
use App\Models\WireRate;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatabaseWireService
{
    public function getPageData(): array
    {
        $wires = Wire::orderBy('idcode')->get();
        $wireRates = WireRate::orderBy('period_month', 'asc')->orderBy('id', 'asc')->get();
        $periodRates = $wireRates->filter(fn ($rate) => !is_null($rate->period_month))->values();

        $selectedRateId = (int) session('wire_selected_rate_id', 0);
        if ($selectedRateId <= 0 && $wireRates->isNotEmpty()) {
            $selectedRateId = (int) $wireRates->last()->id;
        }

        $activeRate = $wireRates->firstWhere('id', $selectedRateId);
        if (!$activeRate) {
            $activeRate = $wireRates->last();
            $selectedRateId = (int) ($activeRate?->id ?? 0);
        }

        $wirePriceNotes = [];
        foreach ($wires as $wire) {
            $wirePriceNotes[$wire->id] = $this->buildWirePriceNote($wire, $activeRate);
        }

        return compact('wires', 'wireRates', 'periodRates', 'selectedRateId', 'activeRate', 'wirePriceNotes');
    }

    public function switchActiveRate(int $rateId): bool
    {
        $activeRate = WireRate::find($rateId);
        if (!$activeRate) {
            return false;
        }

        session(['wire_selected_rate_id' => $rateId]);
        $this->recalculateAllWirePrices($rateId);

        return true;
    }

    public function createRate(array $attributes): WireRate
    {
        $data = $this->normalizeRateAttributes($attributes);
        $data['lme_reference'] = $this->resolveLmeReference((float) $data['lme_active']);

        $wireRate = WireRate::create($data);
        $this->recalculateAllWirePrices();

        return $wireRate;
    }

    public function updateRate(WireRate $wireRate, array $attributes): WireRate
    {
        $data = $this->normalizeRateAttributes($attributes);
        $data['lme_reference'] = $this->resolveLmeReference((float) $data['lme_active']);

        $wireRate->update($data);
        $this->recalculateAllWirePrices();

        return $wireRate->refresh();
    }

    public function deleteRate(WireRate $wireRate): void
    {
        $wireRate->delete();
        $this->recalculateAllWirePrices();
    }

    public function createWire(array $attributes): Wire
    {
        $wire = Wire::create($this->normalizeWireAttributes($attributes));
        $this->recalculateAllWirePrices();

        return $wire;
    }

    public function updateWire(Wire $wire, array $attributes): Wire
    {
        $wire->update($this->normalizeWireAttributes($attributes));
        $this->recalculateAllWirePrices();

        return $wire->refresh();
    }

    public function deleteWire(Wire $wire): void
    {
        $wire->delete();
    }

    public function importWires(string $filePath): array
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

        $headerMap = [];
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $headerRaw = (string) $sheet->getCell($column . '1')->getFormattedValue();
            $normalizedHeader = $this->normalizeWireImportHeader($headerRaw);
            if ($normalizedHeader !== null && !isset($headerMap[$normalizedHeader])) {
                $headerMap[$normalizedHeader] = $col;
            }
        }

        $requiredFields = ['item', 'machine_maintenance', 'fix_cost'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($headerMap[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return [
                'status' => 'validation_error',
                'errors' => ['import_file' => 'Header wajib tidak ditemukan: ' . implode(', ', $missingFields) . '.'],
            ];
        }

        $wires = Wire::query()->select('id', 'item')->get();
        $wireBuckets = [];
        foreach ($wires as $wire) {
            $key = $this->normalizeWireItemKey((string) $wire->item);
            if ($key === '') {
                continue;
            }
            $wireBuckets[$key] ??= [];
            $wireBuckets[$key][] = $wire;
        }

        $processed = 0;
        $skipped = 0;
        $updated = 0;
        $failed = 0;
        $issues = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $item = trim((string) $this->readImportCell($sheet, $headerMap, 'item', $row));
            $machineMaintenanceRaw = (string) $this->readImportCell($sheet, $headerMap, 'machine_maintenance', $row);
            $fixCostRaw = (string) $this->readImportCell($sheet, $headerMap, 'fix_cost', $row);

            if ($item === '' && trim($machineMaintenanceRaw) === '' && trim($fixCostRaw) === '') {
                $skipped++;
                continue;
            }

            $processed++;

            if ($item === '') {
                $failed++;
                $issues[] = 'Baris ' . $row . ': item kosong.';
                continue;
            }

            $itemKey = $this->normalizeWireItemKey($item);
            $matchedWires = $wireBuckets[$itemKey] ?? [];

            if (count($matchedWires) === 0) {
                $failed++;
                $issues[] = 'Baris ' . $row . ': item "' . $item . '" tidak ditemukan.';
                continue;
            }

            if (count($matchedWires) > 1) {
                $failed++;
                $issues[] = 'Baris ' . $row . ': item "' . $item . '" duplikat di database.';
                continue;
            }

            $machineMaintenance = $this->toNullableFloat($machineMaintenanceRaw);
            if ($machineMaintenance === null) {
                $failed++;
                $issues[] = 'Baris ' . $row . ': machine_maintenance harus angka valid.';
                continue;
            }

            $fixCost = $this->toNullableFloat($fixCostRaw);
            if ($fixCost === null) {
                $failed++;
                $issues[] = 'Baris ' . $row . ': fix_cost harus angka valid.';
                continue;
            }

            $wireId = (int) $matchedWires[0]->id;
            Wire::query()->where('id', $wireId)->update([
                'machine_maintenance' => $this->formatWireNumericString($machineMaintenance),
                'fix_cost' => round($fixCost, 5),
                'price' => 0,
                'updated_at' => now(),
            ]);
            $updated++;
        }

        if ($updated > 0) {
            $this->recalculateAllWirePrices();
        }

        return [
            'status' => 'success',
            'message' => "Import wire selesai. Diproses: {$processed}, berhasil: {$updated}, gagal: {$failed}, kosong dilewati: {$skipped}.",
            'warning' => $failed > 0 ? 'Sebagian baris gagal diproses. Periksa detail error di bawah.' : null,
            'issues' => array_slice($issues, 0, 30),
        ];
    }

    public function parseLocalizedDecimal($value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^0-9,\.\-]/', '', $raw);
        if ($normalized === '' || $normalized === '-' || $normalized === ',' || $normalized === '.') {
            return 0.0;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    public function toNullableFloat($value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9,\.\-]/', '', $raw);
        if ($normalized === '' || $normalized === '-' || $normalized === ',' || $normalized === '.') {
            return null;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalizeRateAttributes(array $attributes): array
    {
        $period = trim((string) ($attributes['period_month'] ?? ''));
        $attributes['period_month'] = $this->normalizePeriodMonth($period);
        $attributes['request_name'] = isset($attributes['request_name']) ? $this->nullableTrim($attributes['request_name']) : null;

        return $attributes;
    }

    private function normalizeWireAttributes(array $attributes): array
    {
        return [
            'idcode' => trim((string) $attributes['idcode']),
            'item' => trim((string) $attributes['item']),
            'machine_maintenance' => trim((string) $attributes['machine_maintenance']),
            'fix_cost' => $attributes['fix_cost'] ?? 0,
            'price' => 0,
        ];
    }

    private function normalizePeriodMonth(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth()->format('Y-m-d');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function resolveLmeReference(float $lmeActive): float
    {
        return floor($lmeActive / 100) * 100;
    }

    private function nullableTrim($value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function readImportCell($sheet, array $headerMap, string $field, int $row)
    {
        if (!isset($headerMap[$field])) {
            return '';
        }

        $cellRef = Coordinate::stringFromColumnIndex((int) $headerMap[$field]) . $row;
        return $sheet->getCell($cellRef)->getFormattedValue();
    }

    private function normalizeWireImportHeader(string $value): ?string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(['-', ' ', '_'], '', $normalized);
        $aliases = [
            'item' => ['item', 'wireitem', 'namaitem'],
            'machine_maintenance' => ['machinemaintenance', 'maintenance', 'machinemaint', 'machinecost'],
            'fix_cost' => ['fixcost', 'fixedcost', 'fix'],
        ];

        foreach ($aliases as $target => $candidateHeaders) {
            if (in_array($normalized, $candidateHeaders, true)) {
                return $target;
            }
        }

        return null;
    }

    private function normalizeWireItemKey(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value));
        return Str::lower($normalized ?? '');
    }

    private function formatWireNumericString(float $value): string
    {
        $formatted = number_format($value, 5, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function resolveWireLookupValue(string $idCode, string $item, float $lmeReference): float
    {
        $lookupData = $this->loadWireLookupData();
        if ($lookupData === null) {
            return 0.0;
        }

        $idCode = trim($idCode);
        $item = trim($item);
        $lmeKey = (int) round($lmeReference);
        $targetColumn = $lookupData['lmeColumnByValue'][$lmeKey] ?? null;
        if (!$targetColumn) {
            return 0.0;
        }

        $row = null;
        if ($idCode !== '' && isset($lookupData['rowByKey'][$idCode])) {
            $row = $lookupData['rowByKey'][$idCode];
        }
        if ($row === null && $item !== '') {
            $normalizedItem = strtolower(preg_replace('/\s+/', '', $item));
            $row = $lookupData['rowByItem'][$normalizedItem] ?? null;
        }
        if ($row === null) {
            return 0.0;
        }

        $valueRaw = $lookupData['valueByRowCol'][$row][$targetColumn] ?? null;
        if ($valueRaw === null) {
            return 0.0;
        }

        return $this->parseLocalizedDecimal($valueRaw);
    }

    private function loadWireLookupData(): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $filePath = public_path('templates/lookup wire.xlsx');
        if (!is_file($filePath)) {
            $cache = null;
            return null;
        }

        try {
            $sheet = IOFactory::load($filePath)->getActiveSheet();
        } catch (\Throwable $e) {
            $cache = null;
            return null;
        }

        $lmeColumnByValue = [];
        for ($i = Coordinate::columnIndexFromString('E'); $i <= Coordinate::columnIndexFromString('CX'); $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $headerRaw = trim((string) $sheet->getCell($col . '8')->getFormattedValue());
            if ($headerRaw === '') {
                continue;
            }

            $headerValue = (int) round($this->parseLocalizedDecimal($headerRaw));
            if ($headerValue > 0) {
                $lmeColumnByValue[$headerValue] = $col;
            }
        }

        $rowByKey = [];
        $rowByItem = [];
        $valueByRowCol = [];
        for ($row = 10; $row <= 73; $row++) {
            $key = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());
            $item = trim((string) $sheet->getCell('D' . $row)->getFormattedValue());
            if ($key !== '') {
                $rowByKey[$key] = $row;
            }
            if ($item !== '') {
                $normalizedItem = strtolower(preg_replace('/\s+/', '', $item));
                $rowByItem[$normalizedItem] = $row;
            }
            foreach ($lmeColumnByValue as $col) {
                $rawValue = $sheet->getCell($col . $row)->getValue();
                $valueByRowCol[$row][$col] = is_numeric($rawValue)
                    ? (float) $rawValue
                    : trim((string) $sheet->getCell($col . $row)->getFormattedValue());
            }
        }

        $cache = [
            'lmeColumnByValue' => $lmeColumnByValue,
            'rowByKey' => $rowByKey,
            'rowByItem' => $rowByItem,
            'valueByRowCol' => $valueByRowCol,
        ];

        return $cache;
    }

    private function calculateWirePriceValue(Wire $wire, ?WireRate $rate): float
    {
        if (!$rate) {
            return 0.0;
        }

        $usdRate = (float) ($rate->usd_rate ?? 0);
        $lmeActive = (float) ($rate->lme_active ?? 0);
        $lmeReference = (float) ($rate->lme_reference ?? 0);
        $item = trim((string) ($wire->item ?? ''));
        if ($usdRate <= 0 || $lmeActive <= 0 || $item === '') {
            return 0.0;
        }

        $lookupValue = $this->resolveWireLookupValue((string) ($wire->idcode ?? ''), $item, $lmeReference);
        if ($lookupValue <= 0) {
            return 0.0;
        }

        $machineMaintenance = $this->parseLocalizedDecimal($wire->machine_maintenance ?? 0);
        $fixCost = $this->parseLocalizedDecimal($wire->fix_cost ?? 0);
        $markupFactor = $this->wireRateMarkupFactor($rate);
        $baseValue = (($lookupValue + $machineMaintenance) * $usdRate) + $fixCost;
        $roundedValue = $this->applyWireRateRounding($baseValue, $rate);

        return round($roundedValue * $markupFactor, 2);
    }

    private function wireRateMarkupFactor(WireRate $rate): float
    {
        return 1.03;
    }

    private function applyWireRateRounding(float $baseValue, WireRate $rate): float
    {
        return $rate->period_month ? (float) ceil($baseValue) : (float) floor($baseValue);
    }

    private function buildWirePriceNote(Wire $wire, ?WireRate $rate): array
    {
        if (!$rate) {
            return ['status' => 'error', 'reason' => 'Rate aktif belum tersedia.'];
        }

        $usdRate = (float) ($rate->usd_rate ?? 0);
        $lmeActive = (float) ($rate->lme_active ?? 0);
        $lmeReference = (float) ($rate->lme_reference ?? 0);
        $item = trim((string) ($wire->item ?? ''));
        $machineMaintenance = $this->parseLocalizedDecimal($wire->machine_maintenance ?? 0);
        $fixCost = $this->parseLocalizedDecimal($wire->fix_cost ?? 0);
        $markupFactor = $this->wireRateMarkupFactor($rate);
        $roundingLabel = $rate->period_month ? 'ROUNDUP (ceil)' : 'ROUNDDOWN (floor)';

        $rateLabel = $rate->period_month
            ? $rate->period_month->format('M-Y')
            : (trim((string) ($rate->request_name ?? '')) !== '' ? trim((string) $rate->request_name) : 'Request Khusus');

        if ($usdRate <= 0 || $lmeActive <= 0 || $item === '') {
            return [
                'status' => 'error',
                'reason' => 'Syarat perhitungan belum terpenuhi (USD, LME aktif, atau item kosong).',
                'rate_label' => $rateLabel,
                'usd_rate' => $usdRate,
                'lme_active' => $lmeActive,
                'lme_reference' => $lmeReference,
            ];
        }

        $lookupValue = $this->resolveWireLookupValue((string) ($wire->idcode ?? ''), $item, $lmeReference);
        if ($lookupValue <= 0) {
            return [
                'status' => 'error',
                'reason' => 'Lookup value tidak ditemukan dari tabel referensi.',
                'rate_label' => $rateLabel,
                'usd_rate' => $usdRate,
                'lme_active' => $lmeActive,
                'lme_reference' => $lmeReference,
                'machine_maintenance' => $machineMaintenance,
                'fix_cost' => $fixCost,
            ];
        }

        $baseValue = (($lookupValue + $machineMaintenance) * $usdRate) + $fixCost;
        $roundedValue = $this->applyWireRateRounding($baseValue, $rate);
        $finalPrice = round($roundedValue * $markupFactor, 2);

        return [
            'status' => 'ok',
            'rate_label' => $rateLabel,
            'usd_rate' => $usdRate,
            'lme_active' => $lmeActive,
            'lme_reference' => $lmeReference,
            'lookup_value' => $lookupValue,
            'machine_maintenance' => $machineMaintenance,
            'fix_cost' => $fixCost,
            'base_value' => $baseValue,
            'rounded_value' => $roundedValue,
            'rounding_label' => $roundingLabel,
            'markup_factor' => $markupFactor,
            'final_price' => $finalPrice,
        ];
    }

    private function recalculateAllWirePrices(?int $rateId = null): void
    {
        $activeRate = null;
        if ($rateId && $rateId > 0) {
            $activeRate = WireRate::find($rateId);
        }
        if (!$activeRate) {
            $sessionRateId = (int) session('wire_selected_rate_id', 0);
            if ($sessionRateId > 0) {
                $activeRate = WireRate::find($sessionRateId);
            }
        }
        if (!$activeRate) {
            $activeRate = WireRate::query()->orderByDesc('period_month')->orderByDesc('id')->first();
        }

        Wire::query()->orderBy('id')->chunkById(100, function ($wires) use ($activeRate) {
            foreach ($wires as $wire) {
                $wire->update(['price' => $this->calculateWirePriceValue($wire, $activeRate)]);
            }
        });
    }
}
