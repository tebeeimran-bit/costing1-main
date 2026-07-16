<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\ProjectTaskSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sla_snapshot_and_overdue_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = DocumentProject::create([
            'customer' => 'Test Customer',
            'model' => 'MODEL-01',
            'part_number' => 'PART-SLA-01',
            'part_name' => 'SLA Harness',
            'project_key' => hash('sha256', 'sla-performance-test'),
        ]);
        $revision = DocumentRevision::create([
            'document_project_id' => $project->id,
            'version_number' => 1,
            'received_date' => now()->toDateString(),
            'pic_engineering' => 'HENDRI',
            'partlist_original_name' => '',
            'partlist_file_path' => '',
            'umh_original_name' => '',
            'umh_file_path' => '',
            'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
        ]);
        ProjectTaskSetting::create([
            'document_revision_id' => $revision->id,
            'due_at' => now()->subDays(2),
            'set_by_id' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('sla-performance'))
            ->assertOk()
            ->assertSee('SLA Performance Dashboard')
            ->assertSee('PART-SLA-01')
            ->assertSee('2 hari terlambat')
            ->assertSee('HENDRI');
    }
}
