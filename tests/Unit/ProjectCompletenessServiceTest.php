<?php

namespace Tests\Unit;

use App\Models\CostingData;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Services\Project\ProjectCompletenessService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ProjectCompletenessServiceTest extends TestCase
{
    public function test_score_explains_missing_and_complete_project_data(): void
    {
        $project = new DocumentProject(['customer'=>'Customer','model'=>'Model','part_number'=>'PART-1','part_name'=>'Harness']);
        $revision = new DocumentRevision(['pic_engineering'=>'Engineering','pic_marketing'=>'Marketing','partlist_file_path'=>'p.xlsx','umh_file_path'=>'u.xlsx']);
        $revision->id = 10;
        $revision->setRelation('project', $project);
        $revision->setRelation('unpricedParts', new Collection());
        $service = new ProjectCompletenessService();

        $incomplete = $service->build($revision, null);
        $this->assertSame(45, $incomplete['score']);
        $this->assertContains('costing', collect($incomplete['missing'])->pluck('key')->all());

        $costing = new CostingData(['cycle_times' => [['process'=>'Cutting','qty'=>1]]]);
        $costing->setAttribute('material_breakdowns_count', 2);
        $complete = $service->build($revision, $costing);
        $this->assertSame(100, $complete['score']);
        $this->assertSame('complete', $complete['level']);
        $this->assertEmpty($complete['missing']);
    }
}
