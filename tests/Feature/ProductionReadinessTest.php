<?php

namespace Tests\Feature;

use App\Models\ApprovalDelegation;
use App\Models\CostingApproval;
use App\Models\CostingData;
use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\ExportJob;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_and_idle_timeout_are_enforced(): void
    {
        $this->get('/login')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->withSession(['last_activity_at' => time() - (config('session.lifetime') * 60) - 5])->get('/project-selection')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_publish_role_targeted_announcement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('system-center.announcements.store'), ['title' => 'Maintenance', 'body' => 'Sistem maintenance pukul 18.00.', 'level' => 'warning', 'audiences' => ['admin']])->assertRedirect();
        $this->get(route('dashboard'))->assertOk()->assertSee('Maintenance');
    }

    public function test_export_center_creates_type_specific_downloadable_audit_records(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $headers = [
            'projects' => '"Part Number",Project,Customer',
            'costing' => 'Period,"Part Number",Project',
            'sla' => '"Snapshot Date","Project Revision",Stage',
        ];

        foreach ($headers as $type => $expectedHeader) {
            $this->actingAs($admin)->post(route('exports.store'), ['type' => $type])->assertRedirect();
            $job = ExportJob::where('type', $type)->firstOrFail();
            $this->assertSame('ready', $job->status);
            $this->assertNotNull($job->path);
            $response = $this->get(route('exports.download', $job))->assertOk();
            $this->assertStringStartsWith($expectedHeader, trim($response->streamedContent()));
        }
    }

    public function test_admin_can_open_system_center_and_assign_approval_delegation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coordinator = User::factory()->create(['role' => 'coordinator_costing']);
        $delegate = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($admin)->get(route('system-center.index'))->assertOk()->assertSee('System Center');
        $this->post(route('system-center.delegations.store'), [
            'delegator_id' => $coordinator->id,
            'delegate_id' => $delegate->id,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addDay(),
            'reason' => 'Cuti tahunan',
        ])->assertRedirect();

        $this->assertDatabaseHas('approval_delegations', ['delegator_id' => $coordinator->id, 'delegate_id' => $delegate->id]);
    }

    public function test_active_delegate_can_digitally_sign_approval(): void
    {
        $coordinator = User::factory()->create(['role' => 'coordinator_costing']);
        $delegate = User::factory()->create(['role' => 'viewer']);
        ApprovalDelegation::create(['delegator_id' => $coordinator->id, 'delegate_id' => $delegate->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'reason' => 'Cuti']);
        $project = DocumentProject::create(['customer' => 'Customer', 'model' => 'M1', 'part_number' => 'P1', 'part_name' => 'Harness', 'project_key' => 'prod-ready']);
        $revision = DocumentRevision::create(['document_project_id' => $project->id, 'version_number' => 1, 'received_date' => today(), 'pic_engineering' => 'PIC', 'status' => DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL, 'partlist_original_name' => 'p.xlsx', 'partlist_file_path' => 'p.xlsx', 'umh_original_name' => 'u.xlsx', 'umh_file_path' => 'u.xlsx']);
        $product = Product::create(['code' => 'PR', 'name' => 'Product']);
        $customer = Customer::create(['code' => 'CR', 'name' => 'Customer']);
        $costing = CostingData::create(['product_id' => $product->id, 'customer_id' => $customer->id, 'tracking_revision_id' => $revision->id, 'period' => '2026-07']);
        CostingApproval::create(['document_revision_id' => $revision->id, 'costing_data_id' => $costing->id, 'status' => CostingApproval::STATUS_WAITING, 'submitted_at' => now()]);
        $this->actingAs($delegate)->post(route('costing-approvals.approve', $revision), ['approval_confirmation' => 'APPROVE', 'approval_notes' => 'Reviewed'])->assertRedirect();
        $approval = CostingApproval::first();
        $this->assertNotNull($approval->signature_hash);
        $this->assertSame($coordinator->id, $approval->delegated_by_id);
        $this->assertSame(DocumentRevision::STATUS_APPROVED_BY_COORDINATOR, $revision->fresh()->status);
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.submit'), ['login' => 'nobody@example.com', 'password' => 'wrong-password']);
        }
        $this->post(route('login.submit'), ['login' => 'nobody@example.com', 'password' => 'wrong-password'])->assertSessionHasErrors('login');
        $this->assertDatabaseCount('login_activities', 5);
    }
}
