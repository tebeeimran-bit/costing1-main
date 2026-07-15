<?php

namespace Tests\Unit;

use App\Services\Database\DatabaseMaterialService;
use ReflectionClass;
use Tests\TestCase;

class DatabaseMaterialServiceTest extends TestCase
{
    public function test_normalize_trims_material_attributes(): void
    {
        $service = new DatabaseMaterialService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('normalize');
        $method->setAccessible(true);

        $normalized = $method->invoke($service, [
            'plant' => ' 1501 ',
            'material_code' => ' ABC-123 ',
            'material_description' => ' Connector ',
            'base_uom' => ' PCS ',
            'currency' => ' IDR ',
            'price' => 123.45,
        ]);

        $this->assertSame('1501', $normalized['plant']);
        $this->assertSame('ABC-123', $normalized['material_code']);
        $this->assertSame('Connector', $normalized['material_description']);
        $this->assertSame('PCS', $normalized['base_uom']);
        $this->assertSame('IDR', $normalized['currency']);
        $this->assertSame(123.45, $normalized['price']);
    }
}
