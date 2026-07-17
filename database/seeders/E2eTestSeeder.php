<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Material;
use App\Models\MaterialBreakdown;
use App\Models\Pic;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2eTestSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'e2e-admin@example.test'], ['name' => 'E2E Admin', 'password' => 'E2E-password-2026', 'role' => 'admin']);
        $category = BusinessCategory::firstOrCreate(['code' => 'E2E'], ['name' => 'E2E Harness']);
        $customer = Customer::firstOrCreate(['code' => 'E2EC'], ['name' => 'E2E Customer']);
        Pic::firstOrCreate(['name' => 'E2E Engineer', 'type' => 'engineering']);
        Pic::firstOrCreate(['name' => 'E2E Marketing', 'type' => 'marketing']);
        $product = Product::firstOrCreate(['code' => 'E2E'], ['name' => 'E2E Harness', 'line' => $category->name]);
        $project = DocumentProject::firstOrCreate(['project_key' => 'e2e-approval-project'], [
            'product_id' => $product->id, 'customer' => $customer->name, 'model' => 'E2E-MODEL',
            'part_number' => 'E2E-APPROVAL-001', 'part_name' => 'E2E Approval Harness',
        ]);
        $revision = DocumentRevision::firstOrCreate(['document_project_id' => $project->id, 'version_number' => 1], [
            'received_date' => today(), 'pic_engineering' => 'E2E Engineer', 'pic_marketing' => 'E2E Marketing',
            'status' => DocumentRevision::STATUS_COGM_GENERATED,
            'partlist_original_name' => 'partlist.xlsx', 'partlist_file_path' => 'e2e/partlist.xlsx',
            'umh_original_name' => 'umh.xlsx', 'umh_file_path' => 'e2e/umh.xlsx',
        ]);
        $costing = CostingData::firstOrCreate(['tracking_revision_id' => $revision->id], [
            'product_id' => $product->id, 'customer_id' => $customer->id, 'period' => now()->format('Y-m'),
            'model' => 'E2E-MODEL', 'assy_no' => 'E2E-APPROVAL-001', 'assy_name' => 'E2E Approval Harness',
            'material_cost' => 100000, 'labor_cost' => 20000, 'overhead_cost' => 10000, 'scrap_cost' => 1000,
            'cycle_times' => [['process' => 'Assembly', 'qty' => 1, 'time_sec' => 30]],
        ]);
        $material = Material::firstOrCreate(['material_code' => 'E2E-MAT'], ['material_description' => 'E2E Material', 'base_uom' => 'PCS', 'price' => 100000, 'currency' => 'IDR']);
        MaterialBreakdown::firstOrCreate(['costing_data_id' => $costing->id, 'material_id' => $material->id], [
            'part_no' => 'E2E-MAT', 'part_name' => 'E2E Material', 'qty_req' => 1, 'amount1' => 100000,
            'unit_price_basis' => 100000, 'currency' => 'IDR', 'amount2' => 100000,
        ]);
    }
}
