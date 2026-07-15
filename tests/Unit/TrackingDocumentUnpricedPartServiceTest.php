<?php

namespace Tests\Unit;

use App\Services\TrackingDocument\TrackingDocumentUnpricedPartService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TrackingDocumentUnpricedPartServiceTest extends TestCase
{
    public function test_normalize_lookup_key_removes_symbols_and_lowercases(): void
    {
        $service = new TrackingDocumentUnpricedPartService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('normalizeLookupKey');
        $method->setAccessible(true);

        $normalized = $method->invoke($service, ' AB-12 / CD ');

        $this->assertSame('ab12cd', $normalized);
    }
}
