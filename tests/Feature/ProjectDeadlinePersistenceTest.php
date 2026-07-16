<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Services\Project\ProjectDeadlineService;
use App\Services\Project\ProjectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectDeadlinePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unrelated_revision_update_does_not_reset_stage_aging_or_deadline(): void
    {
        $project = DocumentProject::create([
            'customer' => 'Customer', 'model' => 'Model', 'part_number' => 'PART-AGE',
            'part_name' => 'Harness', 'project_key' => hash('sha256', 'deadline-persistence'),
        ]);
        $revision = DocumentRevision::create([
            'document_project_id' => $project->id,
            'version_number' => 1,
            'received_date' => now()->subDays(7),
            'pic_engineering' => 'PIC A',
            'partlist_original_name' => '', 'partlist_file_path' => '',
            'umh_original_name' => '', 'umh_file_path' => '',
            'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
        ]);
        DB::table('document_revisions')->where('id', $revision->id)->update(['updated_at' => now()->subDays(7)]);
        $revision = $revision->fresh(['project', 'unpricedParts', 'taskSetting']);

        $workflow = app(ProjectWorkflowService::class)->build($revision, null, 'admin');
        $first = app(ProjectDeadlineService::class)->resolve($revision, $workflow);

        $revision->update(['pic_engineering' => 'PIC B']);
        $revision = $revision->fresh(['project', 'unpricedParts', 'taskSetting']);
        $workflow = app(ProjectWorkflowService::class)->build($revision, null, 'admin');
        $second = app(ProjectDeadlineService::class)->resolve($revision, $workflow);

        $this->assertSame($first['due_at']->timestamp, $second['due_at']->timestamp);
        $this->assertSame(7, $second['aging_days']);
        $this->assertSame('documents', $revision->taskSetting->workflow_stage);
    }

    public function test_latest_per_project_scope_returns_only_highest_version(): void
    {
        $project = DocumentProject::create([
            'customer' => 'Customer', 'model' => 'Model', 'part_number' => 'PART-LATEST',
            'part_name' => 'Harness', 'project_key' => hash('sha256', 'latest-revision'),
        ]);
        $older = $this->revision($project, 1);
        $latest = $this->revision($project, 2);

        $ids = DocumentRevision::latestPerProject()->pluck('id');

        $this->assertFalse($ids->contains($older->id));
        $this->assertTrue($ids->contains($latest->id));
    }

    private function revision(DocumentProject $project, int $version): DocumentRevision
    {
        return DocumentRevision::create([
            'document_project_id' => $project->id,
            'version_number' => $version,
            'received_date' => now(),
            'pic_engineering' => 'PIC Engineering',
            'partlist_original_name' => '', 'partlist_file_path' => '',
            'umh_original_name' => '', 'umh_file_path' => '',
            'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
        ]);
    }
}
