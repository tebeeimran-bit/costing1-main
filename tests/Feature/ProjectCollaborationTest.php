<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\ProjectComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCollaborationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_deadline_and_post_comment_with_mention(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mentioned = User::factory()->create(['name' => 'Marketing User', 'email' => 'marketing.user@example.com', 'role' => 'marketing']);
        $revision = $this->revision($admin);

        $this->actingAs($admin)->patch(route('project-collaboration.deadline', $revision), [
            'due_at' => '2026-08-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('project_task_settings', [
            'document_revision_id' => $revision->id,
            'set_by_id' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('project-collaboration.comments.store', $revision), [
            'body' => 'Please review this costing @marketing.user',
        ])->assertRedirect();

        $comment = ProjectComment::firstOrFail();
        $this->assertSame([$mentioned->id], $comment->mentioned_user_ids);
        $this->assertDatabaseHas('project_activities', ['document_revision_id' => $revision->id, 'event_type' => 'deadline_updated']);
        $this->assertDatabaseHas('project_activities', ['document_revision_id' => $revision->id, 'event_type' => 'comment_added']);

        $this->actingAs($admin)->get(route('project-collaboration.show', $revision))
            ->assertOk()
            ->assertSee('Activity Timeline')
            ->assertSee('Please review this costing @marketing.user')
            ->assertSee('01 Aug 2026');

        $this->actingAs($mentioned)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Anda disebut dalam komentar')
            ->assertSee('Buka Diskusi');
    }

    public function test_viewer_cannot_change_deadline_or_delete_another_users_comment(): void
    {
        $owner = User::factory()->create(['role' => 'admin_costing']);
        $viewer = User::factory()->create(['role' => 'viewer']);
        $revision = $this->revision($owner);
        $comment = ProjectComment::create([
            'document_revision_id' => $revision->id,
            'user_id' => $owner->id,
            'body' => 'Owner comment',
        ]);

        $this->actingAs($viewer)->patch(route('project-collaboration.deadline', $revision), ['due_at' => '2026-08-01'])
            ->assertForbidden();
        $this->actingAs($viewer)->delete(route('project-collaboration.comments.destroy', [$revision, $comment]))
            ->assertForbidden();
    }

    public function test_revision_status_change_is_written_to_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $revision = $this->revision($admin);

        $this->actingAs($admin);
        $revision->update(['status' => DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL]);

        $this->assertDatabaseHas('project_activities', [
            'document_revision_id' => $revision->id,
            'event_type' => 'status_changed',
            'user_id' => $admin->id,
        ]);
    }

    private function revision(User $actor): DocumentRevision
    {
        $project = DocumentProject::create([
            'customer' => 'Test Customer',
            'model' => 'TEST-01',
            'part_number' => 'PART-001',
            'part_name' => 'Test Harness',
            'project_key' => hash('sha256', uniqid('collaboration', true)),
        ]);

        $this->actingAs($actor);

        return DocumentRevision::create([
            'document_project_id' => $project->id,
            'version_number' => 1,
            'received_date' => '2026-07-16',
            'pic_engineering' => 'Engineering PIC',
            'partlist_original_name' => 'partlist.xlsx',
            'partlist_file_path' => 'testing/partlist.xlsx',
            'umh_original_name' => 'umh.xlsx',
            'umh_file_path' => 'testing/umh.xlsx',
            'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
        ]);
    }
}
