<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkProjectActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_deadlines_and_pics_in_bulk(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $revisions = collect([$this->revision($admin,'BULK-1'), $this->revision($admin,'BULK-2')]);

        $this->actingAs($admin)->post(route('project.bulk-action'), ['revision_ids'=>$revisions->pluck('id')->all(),'bulk_action'=>'deadline','bulk_value'=>'2026-08-10'])->assertRedirect();
        $this->assertDatabaseCount('project_task_settings', 2);

        $this->actingAs($admin)->post(route('project.bulk-action'), ['revision_ids'=>$revisions->pluck('id')->all(),'bulk_action'=>'pic_marketing','bulk_value'=>'Marketing Team'])->assertRedirect();
        $this->assertSame(2, DocumentRevision::whereIn('id',$revisions->pluck('id'))->where('pic_marketing','Marketing Team')->count());
        $this->assertDatabaseHas('project_activities',['event_type'=>'bulk_deadline_updated']);
    }

    public function test_export_returns_csv_and_viewer_cannot_run_bulk_update(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $viewer=User::factory()->create(['role'=>'viewer']); $revision=$this->revision($admin,'EXPORT-1');
        $this->actingAs($admin)->post(route('project.bulk-action'),['revision_ids'=>[$revision->id],'bulk_action'=>'export'])
            ->assertOk()->assertHeader('content-type','text/csv; charset=utf-8');
        $this->actingAs($viewer)->post(route('project.bulk-action'),['revision_ids'=>[$revision->id],'bulk_action'=>'deadline','bulk_value'=>'2026-08-10'])->assertForbidden();
    }

    private function revision(User $user,string $part): DocumentRevision
    {
        $project=DocumentProject::create(['customer'=>'Customer','model'=>'Model','part_number'=>$part,'part_name'=>'Harness','project_key'=>hash('sha256',uniqid($part,true))]); $this->actingAs($user);
        return DocumentRevision::create(['document_project_id'=>$project->id,'version_number'=>1,'received_date'=>'2026-07-16','pic_engineering'=>'PIC','partlist_original_name'=>'p.xlsx','partlist_file_path'=>'p.xlsx','umh_original_name'=>'u.xlsx','umh_file_path'=>'u.xlsx','status'=>DocumentRevision::STATUS_PENDING_FORM_INPUT]);
    }
}
