<?php

namespace Tests\Unit;

use App\Models\CostingData;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\UnpricedPart;
use App\Services\Project\ProjectWorkflowService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ProjectWorkflowServiceTest extends TestCase
{
    private ProjectWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectWorkflowService();
    }

    public function test_it_directs_incomplete_documents_to_project_documents(): void
    {
        $revision = $this->revision(DocumentRevision::STATUS_PENDING_FORM_INPUT, documentsComplete: false);

        $workflow = $this->service->build($revision, null, 'admin');

        $this->assertSame(17, $workflow['progress']);
        $this->assertSame('current', $workflow['steps'][1]['state']);
        $this->assertSame('Lengkapi Dokumen', $workflow['next_action']['label']);
        $this->assertStringContainsString('/database/project-documents', $workflow['next_action']['url']);
    }

    public function test_it_marks_unpriced_parts_as_an_issue(): void
    {
        $revision = $this->revision(DocumentRevision::STATUS_PENDING_PRICING, unpricedCount: 2);

        $workflow = $this->service->build($revision, new CostingData(), 'admin_costing');

        $this->assertSame('issue', $workflow['steps'][2]['state']);
        $this->assertSame(2, $workflow['open_unpriced_count']);
        $this->assertSame('Lengkapi 2 Harga Part', $workflow['next_action']['label']);
        $this->assertSame('issue', $workflow['next_action']['type']);
    }

    public function test_it_recommends_approval_review_to_coordinator(): void
    {
        $revision = $this->revision(DocumentRevision::STATUS_WAITING_COORDINATOR_APPROVAL);

        $workflow = $this->service->build($revision, new CostingData(), 'coordinator_costing');

        $this->assertSame(67, $workflow['progress']);
        $this->assertSame('current', $workflow['steps'][4]['state']);
        $this->assertSame('Review Approval', $workflow['next_action']['label']);
        $this->assertSame('action', $workflow['next_action']['type']);
    }

    public function test_it_marks_marketing_submission_as_complete(): void
    {
        $revision = $this->revision(DocumentRevision::STATUS_SUBMITTED_TO_MARKETING);

        $workflow = $this->service->build($revision, new CostingData(), 'admin');

        $this->assertSame(100, $workflow['progress']);
        $this->assertTrue($workflow['is_complete']);
        $this->assertSame('complete', $workflow['next_action']['type']);
    }

    private function revision(string $status, bool $documentsComplete = true, int $unpricedCount = 0): DocumentRevision
    {
        $revision = new DocumentRevision([
            'status' => $status,
            'partlist_file_path' => $documentsComplete ? 'partlist.xlsx' : null,
            'umh_file_path' => $documentsComplete ? 'umh.xlsx' : null,
        ]);
        $revision->id = 10;
        $revision->setRelation('project', new DocumentProject(['part_number' => 'PART-001']));
        $revision->setRelation('unpricedParts', new Collection(
            collect(array_fill(0, $unpricedCount, null))->map(fn () => new UnpricedPart(['resolved_at' => null]))->all()
        ));

        return $revision;
    }
}
