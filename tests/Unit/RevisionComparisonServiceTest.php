<?php

namespace Tests\Unit;

use App\Models\CostingData;
use App\Models\MaterialBreakdown;
use App\Services\Project\RevisionComparisonService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RevisionComparisonServiceTest extends TestCase
{
    public function test_it_compares_cost_components_and_changed_materials(): void
    {
        $previous = new CostingData([
            'material_cost' => 100,
            'labor_cost' => 20,
            'overhead_cost' => 10,
            'scrap_cost' => 5,
        ]);
        $previous->setRelation('materialBreakdowns', new Collection([
            new MaterialBreakdown(['part_no' => 'A-01', 'qty_req' => 1, 'amount2' => 100]),
        ]));

        $current = new CostingData([
            'material_cost' => 120,
            'labor_cost' => 20,
            'overhead_cost' => 15,
            'scrap_cost' => 5,
        ]);
        $current->setRelation('materialBreakdowns', new Collection([
            new MaterialBreakdown(['part_no' => 'A-01', 'qty_req' => 2, 'amount2' => 120]),
            new MaterialBreakdown(['part_no' => 'B-02', 'qty_req' => 1, 'amount2' => 10]),
        ]));

        $result = (new RevisionComparisonService)->build($current, $previous);

        $this->assertTrue($result['available']);
        $this->assertSame(25.0, $result['total_delta']);
        $this->assertSame(2, $result['material_changes']);
        $this->assertCount(4, $result['components']);
    }

    public function test_it_returns_unavailable_without_previous_revision(): void
    {
        $current = new CostingData;

        $result = (new RevisionComparisonService)->build($current, null);

        $this->assertFalse($result['available']);
    }
}
