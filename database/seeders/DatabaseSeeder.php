<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Material;
use App\Models\CostingData;
use App\Models\MaterialBreakdown;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(CycleTimeTemplateSeeder::class);

        // Seed Products
        $products = [
            ['code' => 'PCB-B', 'name' => 'PCB B', 'line' => 'Line A'],
            ['code' => 'MTR-X', 'name' => 'Motor X', 'line' => 'Line A'],
            ['code' => 'KBL-A', 'name' => 'Kabel A', 'line' => 'Line B'],
            ['code' => 'RLY-Y', 'name' => 'Relay Y', 'line' => 'Line B'],
            ['code' => 'SWT-Z', 'name' => 'Switch Z', 'line' => 'Line C'],
        ];
        
        foreach ($products as $product) {
            Product::firstOrCreate(['code' => $product['code']], $product);
        }

        // Seed Customers
        $customers = [
            ['code' => 'PLN', 'name' => 'PLN'],
            ['code' => 'TLKM', 'name' => 'Telkom'],
            ['code' => 'ASTR', 'name' => 'Astra'],
            ['code' => 'SMSG', 'name' => 'Samsung'],
            ['code' => 'YMH', 'name' => 'Yamaha'],
        ];
        
        foreach ($customers as $customer) {
            Customer::firstOrCreate(['code' => $customer['code']], $customer);
        }

        // Seed Materials
        $materials = [
            ['material_code' => 'MAT-001', 'material_description' => 'Copper Wire', 'base_uom' => 'KG', 'price' => 25000, 'purchase_unit' => 'KG', 'currency' => 'IDR', 'maker' => 'PT Tembaga Jaya'],
            ['material_code' => 'MAT-002', 'material_description' => 'Steel Plate', 'base_uom' => 'KG', 'price' => 15000, 'purchase_unit' => 'KG', 'currency' => 'IDR', 'maker' => 'PT Baja Utama'],
            ['material_code' => 'MAT-003', 'material_description' => 'IC Chips', 'base_uom' => 'PCS', 'price' => 5000, 'purchase_unit' => 'PCS', 'currency' => 'IDR', 'maker' => 'Samsung Elec'],
            ['material_code' => 'MAT-004', 'material_description' => 'EVC Cable', 'base_uom' => 'MM', 'price' => 1000, 'purchase_unit' => 'MM', 'currency' => 'IDR', 'maker' => 'PT Kabel Indo'],
            ['material_code' => 'MAT-005', 'material_description' => 'Plastic Case', 'base_uom' => 'PCS', 'price' => 2000, 'purchase_unit' => 'PCS', 'currency' => 'IDR', 'maker' => 'PT Plastik Maju'],
            ['material_code' => 'MAT-006', 'material_description' => 'Resistor 10K', 'base_uom' => 'PCS', 'price' => 500, 'purchase_unit' => 'PCS', 'currency' => 'IDR', 'maker' => 'Yageo Corp'],
            ['material_code' => 'MAT-007', 'material_description' => 'Capacitor 100uF', 'base_uom' => 'PCS', 'price' => 1500, 'purchase_unit' => 'PCS', 'currency' => 'IDR', 'maker' => 'Murata Mfg'],
            ['material_code' => 'MAT-008', 'material_description' => 'Spring Steel', 'base_uom' => 'KG', 'price' => 18000, 'purchase_unit' => 'KG', 'currency' => 'IDR', 'maker' => 'PT Spring Indo'],
        ];
        
        foreach ($materials as $material) {
            Material::firstOrCreate(['material_code' => $material['material_code']], $material);
        }

        // Seed Costing Data
        $costingDataList = [
            [
                'product_id' => 1, // PCB B
                'customer_id' => 1, // PLN
                'period' => '2025-01',
                'wo_number' => 'WO-2025-001',
                'exchange_rate_usd' => 15500,
                'exchange_rate_jpy' => 103,
                'lme_rate' => 8500,
                'forecast' => 5000,
                'project_period' => 12,
                'material_cost' => 430000,
                'labor_cost' => 125000,
                'overhead_cost' => 98000,
                'scrap_cost' => 12000,
                'revenue' => 830000,
                'qty_good' => 55000,
            ],
            [
                'product_id' => 2, // Motor X
                'customer_id' => 2, // Telkom
                'period' => '2025-01',
                'wo_number' => 'WO-2025-002',
                'exchange_rate_usd' => 15500,
                'exchange_rate_jpy' => 103,
                'lme_rate' => 8500,
                'forecast' => 8000,
                'project_period' => 12,
                'material_cost' => 520000,
                'labor_cost' => 148000,
                'overhead_cost' => 110000,
                'scrap_cost' => 15000,
                'revenue' => 995000,
                'qty_good' => 80000,
            ],
            [
                'product_id' => 3, // Kabel A
                'customer_id' => 3, // Astra
                'period' => '2025-01',
                'wo_number' => 'WO-2025-003',
                'exchange_rate_usd' => 15500,
                'exchange_rate_jpy' => 103,
                'lme_rate' => 8500,
                'forecast' => 10000,
                'project_period' => 12,
                'material_cost' => 610000,
                'labor_cost' => 175000,
                'overhead_cost' => 132000,
                'scrap_cost' => 18000,
                'revenue' => 1150000,
                'qty_good' => 100000,
            ],
            [
                'product_id' => 4, // Relay Y
                'customer_id' => 4, // Samsung
                'period' => '2025-01',
                'wo_number' => 'WO-2025-004',
                'exchange_rate_usd' => 15500,
                'exchange_rate_jpy' => 103,
                'lme_rate' => 8500,
                'forecast' => 6000,
                'project_period' => 12,
                'material_cost' => 360000,
                'labor_cost' => 102000,
                'overhead_cost' => 86000,
                'scrap_cost' => 9000,
                'revenue' => 680000,
                'qty_good' => 50000,
            ],
            [
                'product_id' => 5, // Switch Z
                'customer_id' => 5, // Yamaha
                'period' => '2025-01',
                'wo_number' => 'WO-2025-005',
                'exchange_rate_usd' => 15500,
                'exchange_rate_jpy' => 103,
                'lme_rate' => 8500,
                'forecast' => 3000,
                'project_period' => 12,
                'material_cost' => 240000,
                'labor_cost' => 65000,
                'overhead_cost' => 54000,
                'scrap_cost' => 5000,
                'revenue' => 430000,
                'qty_good' => 35000,
            ],
        ];
        
        foreach ($costingDataList as $costingData) {
            $cd = CostingData::create($costingData);
            
            // Add material breakdowns for each costing data
            $breakdowns = [
                [
                    'costing_data_id' => $cd->id,
                    'material_id' => 1,
                    'qty_req' => 20,
                    'amount1' => 8500,
                    'unit_price_basis' => 8500,
                    'currency' => 'IDR',
                    'qty_moq' => 25,
                    'cn_type' => 'N',
                    'import_tax_percent' => 5,
                    'amount2' => 8925,
                    'currency2' => 'IDR',
                    'unit_price2' => 8925,
                ],
                [
                    'costing_data_id' => $cd->id,
                    'material_id' => 2,
                    'qty_req' => 15,
                    'amount1' => 4500,
                    'unit_price_basis' => 4500,
                    'currency' => 'IDR',
                    'qty_moq' => 20,
                    'cn_type' => 'N',
                    'import_tax_percent' => 2.5,
                    'amount2' => 4612.5,
                    'currency2' => 'IDR',
                    'unit_price2' => 4612.5,
                ],
                [
                    'costing_data_id' => $cd->id,
                    'material_id' => 3,
                    'qty_req' => 5,
                    'amount1' => 2.5,
                    'unit_price_basis' => 2.5,
                    'currency' => 'USD',
                    'qty_moq' => 8,
                    'cn_type' => 'C',
                    'import_tax_percent' => 7.5,
                    'amount2' => 2.6875,
                    'currency2' => 'USD',
                    'unit_price2' => 2.6875,
                ],
            ];
            
            foreach ($breakdowns as $breakdown) {
                MaterialBreakdown::create($breakdown);
            }
        }
        
        // Add more periods for trend data
        $additionalPeriods = ['2024-07', '2024-08', '2024-09', '2024-10', '2024-11', '2024-12'];
        $baseCosts = [620000, 610000, 600000, 590000, 580000, 570000];
        
        foreach ($additionalPeriods as $index => $period) {
            CostingData::create([
                'product_id' => 1,
                'customer_id' => 1,
                'period' => $period,
                'wo_number' => 'WO-' . str_replace('-', '', $period) . '-001',
                'exchange_rate_usd' => 15500,
                'exchange_rate_jpy' => 103,
                'lme_rate' => 8500,
                'forecast' => 5000,
                'project_period' => 12,
                'material_cost' => $baseCosts[$index] * 0.6,
                'labor_cost' => $baseCosts[$index] * 0.2,
                'overhead_cost' => $baseCosts[$index] * 0.18,
                'scrap_cost' => $baseCosts[$index] * 0.02,
                'revenue' => $baseCosts[$index] * 1.15,
                'qty_good' => 50000 + ($index * 2000),
            ]);
        }
    }
}
