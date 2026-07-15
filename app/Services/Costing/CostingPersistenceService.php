<?php

namespace App\Services\Costing;

use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\Product;
use App\Models\WireRate;
use Illuminate\Http\Request;

class CostingPersistenceService
{
    private const FILLABLE_REQUEST_FIELDS = [
        'customer_id',
        'tracking_revision_id',
        'period',
        'line',
        'model',
        'assy_no',
        'assy_name',
        'exchange_rate_usd',
        'exchange_rate_jpy',
        'lme_rate',
        'forecast',
        'project_period',
        'material_cost',
        'labor_cost',
        'overhead_cost',
        'scrap_cost',
        'revenue',
        'qty_good',
        'cycle_times',
        'costing_resume_overrides',
    ];

    private const SECTION_PAYLOAD_MAP = [
        'informasi_project' => ['customer_id', 'tracking_revision_id', 'period', 'line', 'model', 'assy_no', 'assy_name', 'forecast', 'project_period', 'costing_resume_overrides'],
        'rates' => ['exchange_rate_usd', 'exchange_rate_jpy', 'lme_rate', 'tracking_revision_id', 'costing_resume_overrides'],
        'material' => ['forecast', 'project_period', 'material_cost', 'labor_cost', 'overhead_cost', 'scrap_cost', 'revenue', 'qty_good', 'tracking_revision_id', 'costing_resume_overrides'],
        'unpriced_parts' => ['tracking_revision_id', 'costing_resume_overrides'],
        'cycle_time' => ['cycle_times', 'tracking_revision_id', 'costing_resume_overrides'],
        'resume_cogm' => ['material_cost', 'labor_cost', 'overhead_cost', 'scrap_cost', 'revenue', 'qty_good', 'tracking_revision_id', 'costing_resume_overrides'],
    ];

    private const CREATE_DEFAULTS = [
        'exchange_rate_usd' => 15500,
        'exchange_rate_jpy' => 103,
        'forecast' => 0,
        'project_period' => 0,
        'material_cost' => 0,
        'labor_cost' => 0,
        'overhead_cost' => 0,
        'scrap_cost' => 0,
        'revenue' => 0,
        'qty_good' => 0,
        'cycle_times' => [],
        'costing_resume_overrides' => [],
    ];

    public function applySelectedWireRate(Request $request, array $validated, string $updateSection): void
    {
        if ($updateSection !== 'rates' || empty($validated['wire_rate_id'])) {
            return;
        }

        $selectedWireRate = WireRate::find((int) $validated['wire_rate_id']);
        if (!$selectedWireRate) {
            return;
        }

        session(['wire_selected_rate_id' => (int) $selectedWireRate->id]);

        $request->merge([
            'exchange_rate_usd' => (float) ($selectedWireRate->usd_rate ?? 0),
            'exchange_rate_jpy' => (float) ($selectedWireRate->jpy_rate ?? 0),
            'lme_rate' => (float) ($selectedWireRate->lme_active ?? 0),
        ]);
    }

    public function resolveExistingCostingData(array $validated): ?CostingData
    {
        $costingDataId = $validated['costing_data_id'] ?? null;
        $trackingRevisionId = $validated['tracking_revision_id'] ?? null;

        if ($costingDataId) {
            $existing = CostingData::whereKey($costingDataId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if ($trackingRevisionId) {
            return CostingData::where('tracking_revision_id', $trackingRevisionId)
                ->latest('id')
                ->lockForUpdate()
                ->first();
        }

        return null;
    }

    public function resolveProductId(Request $request, ?CostingData $costingData): ?int
    {
        $productId = $costingData?->product_id;
        if (!$request->filled('business_category_id')) {
            return $productId;
        }

        $businessCategory = BusinessCategory::findOrFail((int) $request->input('business_category_id'));
        $product = Product::firstOrCreate(
            ['code' => trim((string) $businessCategory->code)],
            ['name' => trim((string) $businessCategory->name)]
        );

        if (trim((string) $product->name) !== trim((string) $businessCategory->name)) {
            $product->update(['name' => trim((string) $businessCategory->name)]);
        }

        return (int) $product->id;
    }

    public function saveCostingData(Request $request, array $validated, string $updateSection, ?CostingData $costingData, ?int $productId): CostingData
    {
        $trackingRevisionId = $validated['tracking_revision_id'] ?? null;
        $basePayload = $this->buildBasePayload($request, $validated);
        $payload = $this->buildSectionPayload($basePayload, $updateSection);

        if ($trackingRevisionId) {
            $payload['tracking_revision_id'] = $trackingRevisionId;
        }
        if ($productId) {
            $payload['product_id'] = $productId;
        }

        if ($costingData) {
            if (!empty($payload)) {
                $costingData->update($payload);
                $costingData->refresh();
            }

            return $costingData;
        }

        $createPayload = array_merge($basePayload, $payload);
        if ($trackingRevisionId) {
            $createPayload['tracking_revision_id'] = $trackingRevisionId;
        }
        if ($productId) {
            $createPayload['product_id'] = $productId;
        }

        foreach (['product_id', 'customer_id', 'period'] as $requiredField) {
            if (!array_key_exists($requiredField, $createPayload) || $createPayload[$requiredField] === null || $createPayload[$requiredField] === '') {
                throw new MissingProjectInformationException('Simpan Informasi Project terlebih dahulu sebelum update section lain.');
            }
        }

        return CostingData::create(array_merge(self::CREATE_DEFAULTS, $createPayload));
    }

    public function buildBasePayload(Request $request, array $validated): array
    {
        $basePayload = $request->only(self::FILLABLE_REQUEST_FIELDS);

        foreach (['material_cost', 'labor_cost', 'overhead_cost', 'scrap_cost'] as $numericField) {
            if (array_key_exists($numericField, $basePayload)) {
                $basePayload[$numericField] = $this->parseNumericInput($validated[$numericField] ?? 0);
            }
        }

        if (array_key_exists('costing_resume_overrides', $basePayload)) {
            $basePayload['costing_resume_overrides'] = $this->normalizeCostingResumeOverrides($basePayload['costing_resume_overrides']);
        }

        return $basePayload;
    }

    public function buildSectionPayload(array $basePayload, string $updateSection): array
    {
        if ($updateSection === '') {
            return $basePayload;
        }

        $allowedKeys = self::SECTION_PAYLOAD_MAP[$updateSection] ?? [];

        return array_intersect_key($basePayload, array_flip($allowedKeys));
    }

    private function normalizeCostingResumeOverrides(mixed $value): array
    {
        if (is_array($value)) {
            $decoded = $value;
        } else {
            $raw = trim((string) $value);
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }
        }

        $normalized = [];
        foreach ($decoded as $key => $overrideValue) {
            $normalizedKey = trim((string) $key);
            if ($normalizedKey === '' || strlen($normalizedKey) > 120) {
                continue;
            }

            if (is_scalar($overrideValue) || $overrideValue === null) {
                $text = trim((string) $overrideValue);
                $normalized[$normalizedKey] = substr($text, 0, 500);
            }
        }

        return $normalized;
    }
    private function parseNumericInput($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return 0.0;
        }

        $negative = false;
        if (str_starts_with($normalized, '(') && str_ends_with($normalized, ')')) {
            $negative = true;
            $normalized = substr($normalized, 1, -1);
        }

        $normalized = preg_replace('/[^0-9,.-]/', '', $normalized) ?? '';
        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return 0.0;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        $number = is_numeric($normalized) ? (float) $normalized : 0.0;

        return $negative ? -1 * $number : $number;
    }
}
