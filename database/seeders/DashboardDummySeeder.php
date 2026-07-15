<?php

namespace Database\Seeders;

use App\Models\CogmSubmission;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DashboardDummySeeder extends Seeder
{
    public function run(): void
    {
        $products = collect([
            ['code' => 'WH-A', 'name' => 'Wiring Harness Alpha', 'line' => 'WIRING HARNESS'],
            ['code' => 'WH-B', 'name' => 'Wiring Harness Beta', 'line' => 'WIRING HARNESS'],
            ['code' => 'ECU-C', 'name' => 'ECU Controller', 'line' => 'AUTOMOTIVE ELECTRONICS PARTS'],
            ['code' => 'SNS-D', 'name' => 'Sensor Kit', 'line' => 'AUTOMOTIVE ELECTRONICS PARTS'],
            ['code' => 'PLS-E', 'name' => 'Plastic Casing', 'line' => 'POWER ENERGY & INOVATION SOLUTION'],
            ['code' => 'MTL-F', 'name' => 'Metal Bracket', 'line' => 'AMR SYSTEM'],
        ])->mapWithKeys(function (array $row) {
            $product = Product::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'line' => $row['line']]
            );

            return [$row['code'] => $product];
        });

        $customers = collect([
            ['code' => 'AHM', 'name' => 'Astra Honda Motor, PT'],
            ['code' => 'YIMM', 'name' => 'Yamaha Indonesia Motor, PT'],
            ['code' => 'SUZI', 'name' => 'Suzuki Indomobil Motor, PT'],
            ['code' => 'ADM', 'name' => 'Astra Daihatsu Motor, PT'],
            ['code' => 'HMMI', 'name' => 'Hyundai Motor Manufacturing, PT'],
            ['code' => 'MITS', 'name' => 'Mitsubishi Motors Krama Yudha, PT'],
        ])->mapWithKeys(function (array $row) {
            $customer = Customer::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name']]
            );

            return [$row['code'] => $customer];
        });

        $periodRows = [
            ['period' => '2025-11', 'forecastFactor' => 0.88, 'costFactor' => 0.93],
            ['period' => '2025-12', 'forecastFactor' => 0.92, 'costFactor' => 0.97],
            ['period' => '2026-01', 'forecastFactor' => 1.00, 'costFactor' => 1.00],
            ['period' => '2026-02', 'forecastFactor' => 1.04, 'costFactor' => 1.03],
            ['period' => '2026-03', 'forecastFactor' => 1.08, 'costFactor' => 1.06],
            ['period' => '2026-04', 'forecastFactor' => 1.12, 'costFactor' => 1.10],
        ];

        $projectBlueprints = [
            ['key' => 'A', 'model' => 'K4MA', 'assy_no' => '32100-K4MA-W203', 'assy_name' => 'HARNESS WIRE MAIN', 'product' => 'WH-A', 'customer' => 'AHM', 'baseForecast' => 18000, 'projectPeriod' => 24, 'material' => 42, 'labor' => 9, 'overhead' => 6, 'scrap' => 1.2, 'qtyGood' => 17000],
            ['key' => 'B', 'model' => 'K59J', 'assy_no' => '32110-K59J-F110', 'assy_name' => 'HARNESS WIRE SUB', 'product' => 'WH-B', 'customer' => 'AHM', 'baseForecast' => 15000, 'projectPeriod' => 20, 'material' => 36, 'labor' => 8, 'overhead' => 5.5, 'scrap' => 1.1, 'qtyGood' => 14500],
            ['key' => 'C', 'model' => 'NMAX2', 'assy_no' => '2DP-ECU-001', 'assy_name' => 'ECU UNIT ASSY', 'product' => 'ECU-C', 'customer' => 'YIMM', 'baseForecast' => 9000, 'projectPeriod' => 18, 'material' => 88, 'labor' => 14, 'overhead' => 10, 'scrap' => 2.3, 'qtyGood' => 8600],
            ['key' => 'D', 'model' => 'XL7', 'assy_no' => '36850-XL7-SNS', 'assy_name' => 'SENSOR KIT', 'product' => 'SNS-D', 'customer' => 'SUZI', 'baseForecast' => 7200, 'projectPeriod' => 22, 'material' => 51, 'labor' => 11, 'overhead' => 7.3, 'scrap' => 1.6, 'qtyGood' => 6800],
            ['key' => 'E', 'model' => 'AYLA', 'assy_no' => '76811-AYLA-PLS', 'assy_name' => 'COVER COWL', 'product' => 'PLS-E', 'customer' => 'ADM', 'baseForecast' => 11000, 'projectPeriod' => 16, 'material' => 25, 'labor' => 6.5, 'overhead' => 4.2, 'scrap' => 0.9, 'qtyGood' => 10400],
            ['key' => 'F', 'model' => 'CRETA', 'assy_no' => '86513-CRETA-MTL', 'assy_name' => 'BRACKET FRONT', 'product' => 'MTL-F', 'customer' => 'HMMI', 'baseForecast' => 8300, 'projectPeriod' => 19, 'material' => 33, 'labor' => 7.2, 'overhead' => 5.1, 'scrap' => 1.0, 'qtyGood' => 7900],
            ['key' => 'G', 'model' => 'XPANDER', 'assy_no' => '32100-XPDR-W001', 'assy_name' => 'HARNESS FLOOR', 'product' => 'WH-A', 'customer' => 'MITS', 'baseForecast' => 9800, 'projectPeriod' => 21, 'material' => 39, 'labor' => 8.3, 'overhead' => 5.8, 'scrap' => 1.3, 'qtyGood' => 9300],
            ['key' => 'H', 'model' => 'JUPITER', 'assy_no' => '2PH-SNS-778', 'assy_name' => 'SENSOR ADAPTER', 'product' => 'SNS-D', 'customer' => 'YIMM', 'baseForecast' => 7600, 'projectPeriod' => 15, 'material' => 46, 'labor' => 9.8, 'overhead' => 6.7, 'scrap' => 1.5, 'qtyGood' => 7100],
        ];

        $baseReceiveDates = [
            '2025-11-12',
            '2025-12-10',
            '2026-01-14',
            '2026-02-11',
            '2026-03-12',
            '2026-04-09',
        ];

        $engineeringPics = ['Rangga', 'Varani', 'Wisnu', 'Nadia'];
        $marketingPics = ['Dwi', 'Mira', 'Fajar', 'Lina'];

        foreach ($periodRows as $periodIndex => $periodMeta) {
            foreach ($projectBlueprints as $projectIndex => $bp) {
                $product = $products[$bp['product']];
                $customer = $customers[$bp['customer']];

                $projectKey = hash('sha256', implode('|', [
                    'dashboard-dummy',
                    strtolower($customer->name),
                    strtolower($bp['model']),
                    strtolower($bp['assy_no']),
                    strtolower($bp['assy_name']),
                ]));

                $project = DocumentProject::updateOrCreate(
                    ['project_key' => $projectKey],
                    [
                        'product_id' => $product->id,
                        'customer' => $customer->name,
                        'model' => $bp['model'],
                        'part_number' => $bp['assy_no'],
                        'part_name' => $bp['assy_name'],
                    ]
                );

                $receivedDate = $baseReceiveDates[$periodIndex];
                $revision = DocumentRevision::updateOrCreate(
                    [
                        'document_project_id' => $project->id,
                        'version_number' => $periodIndex + 1,
                    ],
                    [
                        'received_date' => $receivedDate,
                        'pic_engineering' => $engineeringPics[$projectIndex % count($engineeringPics)],
                        'status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
                        'cogm_generated_at' => $receivedDate . ' 10:00:00',
                        'pic_marketing' => $marketingPics[$projectIndex % count($marketingPics)],
                        'a00' => 'ada',
                        'a00_received_date' => $receivedDate,
                        'a00_document_original_name' => 'A00-' . $bp['assy_no'] . '.pdf',
                        'a00_document_file_path' => 'tracking-documents/a00/' . $bp['assy_no'] . '.pdf',
                        'a04' => (($projectIndex + $periodIndex) % 4 === 0) ? 'ada' : null,
                        'a04_received_date' => (($projectIndex + $periodIndex) % 4 === 0) ? $receivedDate : null,
                        'a04_document_original_name' => (($projectIndex + $periodIndex) % 4 === 0) ? ('A04-' . $bp['assy_no'] . '.pdf') : null,
                        'a04_document_file_path' => (($projectIndex + $periodIndex) % 4 === 0) ? ('tracking-documents/a04/' . $bp['assy_no'] . '.pdf') : null,
                        'a05' => (($projectIndex + $periodIndex) % 3 === 0) ? 'ada' : null,
                        'a05_received_date' => (($projectIndex + $periodIndex) % 3 === 0) ? $receivedDate : null,
                        'a05_document_original_name' => (($projectIndex + $periodIndex) % 3 === 0) ? ('A05-' . $bp['assy_no'] . '.pdf') : null,
                        'a05_document_file_path' => (($projectIndex + $periodIndex) % 3 === 0) ? ('tracking-documents/a05/' . $bp['assy_no'] . '.pdf') : null,
                        'partlist_original_name' => 'PARTLIST-' . $bp['assy_no'] . '.xlsx',
                        'partlist_file_path' => 'tracking-documents/partlist/' . $bp['assy_no'] . '.xlsx',
                        'umh_original_name' => 'UMH-' . $bp['assy_no'] . '.xlsx',
                        'umh_file_path' => 'tracking-documents/umh/' . $bp['assy_no'] . '.xlsx',
                        'notes' => 'Generated by DashboardDummySeeder',
                    ]
                );

                $costMultiplier = (float) $periodMeta['costFactor'];
                $forecastMultiplier = (float) $periodMeta['forecastFactor'];

                $forecast = (int) round($bp['baseForecast'] * $forecastMultiplier);
                $materialCost = (float) round(($bp['material'] * $forecast) * $costMultiplier, 2);
                $laborCost = (float) round(($bp['labor'] * $forecast) * $costMultiplier, 2);
                $overheadCost = (float) round(($bp['overhead'] * $forecast) * $costMultiplier, 2);
                $scrapCost = (float) round(($bp['scrap'] * $forecast) * $costMultiplier, 2);
                $revenue = (float) round(($materialCost + $laborCost + $overheadCost + $scrapCost) * 1.17, 2);
                $qtyGood = (int) round($bp['qtyGood'] * $forecastMultiplier);

                CostingData::updateOrCreate(
                    [
                        'wo_number' => 'DMY-' . $periodMeta['period'] . '-' . $bp['key'],
                    ],
                    [
                        'product_id' => $product->id,
                        'customer_id' => $customer->id,
                        'tracking_revision_id' => $revision->id,
                        'period' => $periodMeta['period'],
                        'line' => $product->line,
                        'wo_number' => 'DMY-' . $periodMeta['period'] . '-' . $bp['key'],
                        'model' => $bp['model'],
                        'assy_no' => $bp['assy_no'],
                        'assy_name' => $bp['assy_name'],
                        'exchange_rate_usd' => 15750,
                        'exchange_rate_jpy' => 107,
                        'lme_rate' => 9150,
                        'rate_periode' => $periodMeta['period'],
                        'forecast' => $forecast,
                        'project_period' => $bp['projectPeriod'],
                        'material_cost' => $materialCost,
                        'labor_cost' => $laborCost,
                        'overhead_cost' => $overheadCost,
                        'scrap_cost' => $scrapCost,
                        'revenue' => $revenue,
                        'qty_good' => $qtyGood,
                    ]
                );

                CogmSubmission::updateOrCreate(
                    [
                        'document_revision_id' => $revision->id,
                        'submitted_at' => $receivedDate . ' 14:30:00',
                    ],
                    [
                        'pic_marketing' => $marketingPics[$projectIndex % count($marketingPics)],
                        'cogm_value' => round(($materialCost + $laborCost + $overheadCost + $scrapCost) / max($qtyGood, 1), 2),
                        'submitted_by' => 'dashboard-seeder',
                        'notes' => 'Auto generated for dashboard chart',
                    ]
                );
            }
        }
    }
}
