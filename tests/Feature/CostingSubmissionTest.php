<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CostingData;
use App\Models\Material;
use App\Models\MaterialBreakdown;
use App\Models\User;
use App\Models\BusinessCategory;

class CostingSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_costing_data_with_materials()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // 1. Arrange: Create dependencies
        $product = Product::create(['name' => 'Test Product', 'code' => 'PROD-001']);
        $customer = Customer::create(['name' => 'Test Customer', 'code' => 'CUST-001']);
        $businessCategory = BusinessCategory::create(['code' => 'BC-001', 'name' => 'Test Business Category']);

        $formData = [
            // Costing Data Fields
            'project_period' => 5,
            'period' => '2026-01', // Added period
            'line' => 'Line Example',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'business_category_id' => $businessCategory->id,
            'exchange_rate_usd' => 15000, // Added
            'exchange_rate_jpy' => 100, // Added
            'exchange_rate_usd' => 15000,
            'exchange_rate_jpy' => 100,
            'forecast' => 1000,
            'model' => 'Model X',
            'assy_no' => 'ASSY-001',
            'assy_name' => 'Test Assembly',
            'material_cost' => 100000, // Added
            'labor_cost' => 20000, // Added
            'overhead_cost' => 10000, // Added
            'scrap_cost' => 5000, // Added
            'revenue' => 150000, // Added
            'qty_good' => 950, // Added

            // Material Breakdown Data
            'materials' => [
                [
                    'id_code' => 'MAT-001',
                    'part_name' => 'Steel Component',
                    'part_no' => 'P-123',
                    'unit' => 'PCS',
                    'qty_req' => '2',
                    'qty_moq' => '10',
                    'amount1' => '50000', // Price Basis
                    'unit_price_basis' => '50000',
                    'currency' => 'IDR',
                    'supplier' => 'Test Supplier',
                    'pro_code' => 'P',
                    'cn_type' => 'N',
                    'import_tax' => '0',
                ]
            ]
        ];

        // 2. Act: Post data
        $response = $this->post(route('costing.store'), $formData);

        // 3. Assert: Check redirect and database
        $response->assertRedirect(route('tracking-documents.index'));

        $this->assertDatabaseHas('costing_data', [
            'model' => 'Model X',
            'assy_no' => 'ASSY-001',
            'assy_name' => 'Test Assembly',
            'exchange_rate_usd' => 15000,
            'exchange_rate_jpy' => 100,
        ]);

        $costingData = CostingData::first();
        $this->assertNotNull($costingData);

        $this->assertDatabaseHas('material_breakdowns', [
            'costing_data_id' => $costingData->id,
            'part_no' => 'P-123',
            'id_code' => 'MAT-001',
            'part_name' => 'Steel Component',
        ]);

        $material = Material::where('material_code', '__PLACEHOLDER__')->first();
        $this->assertNotNull($material);

        // Check Breakdown
        // Calculation logic in Controller:
        // unit = PCS (Divisor 1)
        // denominator = forecast(1000) * period(5) * 12 * qtyReq(2) = 1000 * 60 * 2 = 120,000
        // ratio = moq(10) / 120,000 = 0.0000833
        // multiplyFactor = ratio (since ratio < 1, wait... controller logic: if ratio < 1 ? 1 : ratio)
        // Let's check controller logic: $multiplyFactor = ($cnType === 'C' || $ratio < 1) ? 1 : $ratio;
        // So multiplyFactor should be 1.

        // Amount2:
        // priceBase = 50000
        // importTax = 0 -> extra = 0
        // base = 50000
        // numerator = 1 * 50000 = 50000
        // unitDivisor2 = 1 (PCS)
        // amount2 = 50000 / 1 = 50000

        $this->assertDatabaseHas('material_breakdowns', [
            'costing_data_id' => $costingData->id,
            'material_id' => $material->id,
            'amount1' => '50000',
            'amount2' => 50000,
            'unit_price2' => 50000,
        ]);
    }
}
