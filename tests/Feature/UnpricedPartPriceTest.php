<?php

namespace Tests\Feature;

use App\Http\Controllers\CostingController;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Material;
use App\Models\MaterialBreakdown;
use App\Models\UnpricedPart;
use App\Services\TrackingDocument\TrackingDocumentUnpricedPartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class UnpricedPartPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_is_applied_when_costing_edit_workbook_is_missing(): void
    {
        $project = DocumentProject::create([
            'customer' => 'Test Customer',
            'model' => 'TEST-MODEL',
            'part_number' => 'ASSY-001',
            'part_name' => 'Test Assembly',
            'project_key' => 'test-customer|test-model|assy-001',
        ]);

        $revision = DocumentRevision::create([
            'document_project_id' => $project->id,
            'version_number' => 1,
            'received_date' => now()->toDateString(),
            'pic_engineering' => 'Tester',
            'status' => DocumentRevision::STATUS_PENDING_PRICING,
            'partlist_original_name' => 'partlist.xlsx',
            'partlist_file_path' => 'missing/partlist.xlsx',
            'umh_original_name' => 'umh.xlsx',
            'umh_file_path' => 'missing/umh.xlsx',
            'costing_edit_file_path' => null,
        ]);

        $unpricedPart = UnpricedPart::create([
            'document_revision_id' => $revision->id,
            'part_number' => 'PART-001',
            'part_name' => 'Test Part',
        ]);

        $result = app(TrackingDocumentUnpricedPartService::class)->updatePrice($revision, [
            'part_number' => 'PART-001',
            'manual_price' => 12.5,
            'currency' => 'USD',
            'purchase_unit' => 'PCE',
            'update_costing_edit' => true,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['costing_edit_updated']);
        $this->assertSame(0, $result['open_unpriced_count']);
        $this->assertNotNull($unpricedPart->fresh()->resolved_at);
        $this->assertDatabaseHas('materials', [
            'material_code' => 'PART-001',
            'currency' => 'USD',
            'price' => 12.5,
        ]);

        $partlistRow = new MaterialBreakdown([
            'part_no' => 'PART-001',
            'amount1' => 0,
        ]);
        $controller = app(CostingController::class);
        $method = (new ReflectionClass($controller))->getMethod('applyResolvedUnpricedPricesToRows');
        $method->setAccessible(true);
        $rows = $method->invoke($controller, collect([$partlistRow]), $revision->id);

        $this->assertSame(12.5, (float) $rows->first()->amount1);
        $this->assertSame('PCE', $rows->first()->unit_price_basis_text);
        $this->assertSame('USD', $rows->first()->currency);
    }
}
