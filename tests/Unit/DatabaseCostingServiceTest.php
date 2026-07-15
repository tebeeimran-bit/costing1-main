<?php

namespace Tests\Unit;

use App\Services\Database\DatabaseCostingService;
use PHPUnit\Framework\TestCase;

class DatabaseCostingServiceTest extends TestCase
{
    public function test_service_can_be_instantiated(): void
    {
        $service = new DatabaseCostingService();

        $this->assertInstanceOf(DatabaseCostingService::class, $service);
    }
}
