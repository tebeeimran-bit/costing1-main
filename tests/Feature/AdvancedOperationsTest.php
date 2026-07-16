<?php

namespace Tests\Feature;

use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\ImportRun;
use App\Models\Material;
use App\Models\Product;
use App\Models\SlaSnapshot;
use App\Models\SystemBackup;
use App\Models\User;
use App\Services\Operations\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvancedOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_partlist_and_umh_can_be_previewed_with_row_level_issues(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $path = public_path('templates/templatepartlist.xlsx');

        $this->actingAs($admin)->post(route('costing.import-partlist.preview'), [
            'import_partlist_file' => new UploadedFile($path, 'partlist.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertOk()->assertJsonStructure(['summary' => ['rows', 'issues'], 'issues', 'preview']);
    }

    public function test_partlist_and_umh_import_snapshots_can_be_rolled_back(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create(['code' => 'ROLL', 'name' => 'Rollback']);
        $customer = Customer::create(['code' => 'ROLL', 'name' => 'Rollback']);
        $costing = CostingData::create(['product_id' => $product->id, 'customer_id' => $customer->id, 'period' => '2026-07', 'material_cost' => 999, 'cycle_times' => [['process' => 'New']]]);
        $material = Material::create(['material_code' => 'ROLL-MAT', 'material_description' => 'Rollback Material', 'base_uom' => 'PCS', 'price' => 999]);
        DB::table('material_breakdowns')->insert(['costing_data_id' => $costing->id, 'material_id' => $material->id, 'part_no' => 'NEW', 'part_name' => 'New Part', 'qty_req' => 1, 'amount1' => 999, 'created_at' => now(), 'updated_at' => now()]);
        $oldRow = DB::table('material_breakdowns')->where('costing_data_id', $costing->id)->first();
        $oldRow = (array) $oldRow;
        $oldRow['part_no'] = 'OLD';
        $oldRow['part_name'] = 'Old Part';
        $partlist = ImportRun::create(['user_id' => $admin->id, 'costing_data_id' => $costing->id, 'type' => 'partlist', 'status' => 'applied', 'before_snapshot' => ['material_cost' => 100, 'materials' => [$oldRow]]]);

        $this->actingAs($admin)->post(route('costing.imports.rollback', $partlist))->assertRedirect();
        $this->assertDatabaseHas('material_breakdowns', ['costing_data_id' => $costing->id, 'part_no' => 'OLD']);
        $this->assertSame('100.00', $costing->fresh()->material_cost);

        $umh = ImportRun::create(['user_id' => $admin->id, 'costing_data_id' => $costing->id, 'type' => 'umh', 'status' => 'applied', 'before_snapshot' => ['cycle_times' => [['process' => 'Old Process', 'qty' => 1]]]]);
        $this->post(route('costing.imports.rollback', $umh))->assertRedirect();
        $this->assertSame('Old Process', $costing->fresh()->cycle_times[0]['process']);
    }

    public function test_backup_checksum_detects_tampering(): void
    {
        $path = storage_path('framework/testing-backup.sql');
        file_put_contents($path, 'valid backup');
        $backup = SystemBackup::create(['database_driver' => 'mysql', 'filename' => 'backup.sql', 'path' => $path, 'size_bytes' => filesize($path), 'checksum' => hash_file('sha256', $path)]);
        $service = app(DatabaseBackupService::class);
        $this->assertTrue($service->verify($backup));
        file_put_contents($path, 'tampered');
        $this->assertFalse($service->verify($backup));
        unlink($path);
    }

    public function test_sla_dashboard_supports_yearly_history_breakdown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SlaSnapshot::create(['snapshot_date' => today()->subMonths(4), 'document_revision_id' => $this->revisionId(), 'stage' => 'approval', 'pic' => 'PIC A', 'is_overdue' => true, 'aging_days' => 5, 'compliance' => 0]);

        $this->actingAs($admin)->get(route('sla-performance', ['history_range' => 365]))
            ->assertOk()->assertSee('12 bulan')->assertSee('Historis per Tahap')->assertSee('Historis per PIC');
    }

    private function revisionId(): int
    {
        $project = DocumentProject::create(['customer' => 'C', 'model' => 'M', 'part_number' => 'P', 'part_name' => 'N', 'project_key' => 'advanced-ops']);

        return DocumentRevision::create(['document_project_id' => $project->id, 'version_number' => 1, 'received_date' => today(), 'pic_engineering' => 'PIC A', 'status' => 'pending_form_input', 'partlist_original_name' => '', 'partlist_file_path' => '', 'umh_original_name' => '', 'umh_file_path' => ''])->id;
    }
}
