<?php

namespace Tests\Unit;

use App\Services\Database\DatabaseSpreadsheetImportService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseSpreadsheetImportServiceTest extends TestCase
{
    public function test_parse_date_value_handles_invalid_input(): void
    {
        $service = new DatabaseSpreadsheetImportService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('parseDateValue');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($service, 'not-a-date'));
        $this->assertSame('2026-04-23', $method->invoke($service, '2026-04-23'));
    }
}
