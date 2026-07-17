<?php

namespace Tests\Feature;

use App\Models\CompanyHoliday;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\Product;
use App\Models\ReleaseCycle;
use App\Models\User;
use App\Services\Costing\CostingLockService;
use App\Services\Project\BusinessCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_calendar_skips_weekends_and_company_holidays(): void
    {
        CompanyHoliday::create(['holiday_date' => '2026-07-20', 'name' => 'Company Holiday']);
        $due = app(BusinessCalendarService::class)->addBusinessDays(Carbon::parse('2026-07-17 09:00'), 1);
        $this->assertSame('2026-07-21', $due->toDateString());
    }

    public function test_admin_can_create_release_with_standard_checks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('operations.releases.store'), ['name' => 'Production', 'version' => 'v2'])->assertRedirect();
        $release = ReleaseCycle::firstOrFail();
        $this->assertCount(6, $release->checks);
        $this->get(route('operations.index'))->assertOk()->assertSee('Operations Center')->assertSee('Production');
    }

    public function test_release_cannot_be_ready_until_every_check_passes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $release = ReleaseCycle::create(['name' => 'Production', 'created_by' => $admin->id]);
        $release->checks()->create(['title' => 'Critical workflow']);
        $this->actingAs($admin)->patch(route('operations.releases.update', $release), ['status' => 'ready'])->assertSessionHas('error');
        $this->assertSame('draft', $release->refresh()->status);
    }

    public function test_approved_costing_is_frozen(): void
    {
        $project = DocumentProject::create(['customer' => 'Customer', 'model' => 'M1', 'part_number' => 'P1', 'part_name' => 'Part', 'project_key' => 'test-lock']);
        $revision = DocumentRevision::create(['document_project_id' => $project->id, 'version_number' => 1, 'received_date' => '2026-07-16', 'pic_engineering' => 'Engineer', 'status' => DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL, 'partlist_original_name' => 'part.xlsx', 'partlist_file_path' => 'part.xlsx', 'umh_original_name' => 'umh.xlsx', 'umh_file_path' => 'umh.xlsx']);
        $product = Product::create(['code' => 'P1', 'name' => 'Product']);
        $customer = Customer::create(['code' => 'C1', 'name' => 'Customer']);
        $costing = CostingData::create(['product_id' => $product->id, 'customer_id' => $customer->id, 'tracking_revision_id' => $revision->id, 'period' => '2026-07']);
        $this->expectException(ValidationException::class);
        app(CostingLockService::class)->assertEditable($costing);
    }
}
