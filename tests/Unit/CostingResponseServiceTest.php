<?php

namespace Tests\Unit;

use App\Services\Costing\CostingResponseService;
use PHPUnit\Framework\TestCase;

class CostingResponseServiceTest extends TestCase
{
    public function test_build_success_message_for_section_update(): void
    {
        $service = new CostingResponseService();

        $message = $service->buildSuccessMessage('rates', false, 0, false, 0);

        $this->assertSame('Rates berhasil diupdate!', $message);
    }

    public function test_build_success_message_for_imports(): void
    {
        $service = new CostingResponseService();

        $partlistMessage = $service->buildSuccessMessage('material', true, 12, false, 0);
        $cycleTimeMessage = $service->buildSuccessMessage('cycle_time', false, 0, true, 4);

        $this->assertSame('Partlist berhasil diimport ke Material (12 baris).', $partlistMessage);
        $this->assertSame('Cycle Time berhasil diimport (4 baris).', $cycleTimeMessage);
    }
}
