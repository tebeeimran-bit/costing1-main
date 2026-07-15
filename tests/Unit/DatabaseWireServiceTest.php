<?php

namespace Tests\Unit;

use App\Services\Database\DatabaseWireService;
use PHPUnit\Framework\TestCase;

class DatabaseWireServiceTest extends TestCase
{
    public function test_parse_localized_decimal_handles_comma_and_dot(): void
    {
        $service = new DatabaseWireService();

        $this->assertSame(1234.56, $service->parseLocalizedDecimal('1.234,56'));
        $this->assertSame(1234.56, $service->parseLocalizedDecimal('1,234.56'));
    }

    public function test_to_nullable_float_returns_null_for_blank(): void
    {
        $service = new DatabaseWireService();

        $this->assertNull($service->toNullableFloat(''));
        $this->assertSame(12.5, $service->toNullableFloat('12,5'));
    }
}
