<?php

namespace Tests\Unit;

use App\Services\Costing\CostingPersistenceService;
use PHPUnit\Framework\TestCase;

class CostingPersistenceServiceTest extends TestCase
{
    public function test_build_section_payload_only_keeps_keys_for_requested_section(): void
    {
        $service = new CostingPersistenceService();

        $payload = [
            'customer_id' => 10,
            'tracking_revision_id' => 7,
            'period' => '2026-04',
            'forecast' => 300,
            'project_period' => 3,
            'material_cost' => 1000,
            'cycle_times' => [['process' => 'Cutting']],
        ];

        $filtered = $service->buildSectionPayload($payload, 'material');

        $this->assertSame([
            'tracking_revision_id' => 7,
            'forecast' => 300,
            'project_period' => 3,
            'material_cost' => 1000,
        ], $filtered);
    }

    public function test_build_section_payload_returns_base_payload_for_full_submit(): void
    {
        $service = new CostingPersistenceService();
        $payload = [
            'customer_id' => 10,
            'period' => '2026-04',
            'material_cost' => 1000,
        ];

        $this->assertSame($payload, $service->buildSectionPayload($payload, ''));
    }
}
