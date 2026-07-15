<?php

namespace Tests\Unit;

use App\Services\Database\DatabaseProjectDocumentService;
use Illuminate\Support\Collection;
use ReflectionClass;
use stdClass;
use Tests\TestCase;

class DatabaseProjectDocumentServiceTest extends TestCase
{
    public function test_filter_rows_matches_search_and_status(): void
    {
        $service = new DatabaseProjectDocumentService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('filterRows');
        $method->setAccessible(true);

        $rowA = (object) [
            'status' => 'a00',
            'costingData' => (object) [
                'customer' => (object) ['name' => 'Alpha'],
                'model' => 'MDL-1',
                'assy_name' => 'Bracket',
            ],
            'project' => (object) [
                'customer' => 'Alpha',
                'model' => 'MDL-1',
                'part_name' => 'Bracket',
            ],
            'revision' => (object) [
                'version_label' => 'Rev 1',
            ],
        ];

        $rowB = (object) [
            'status' => 'a05',
            'costingData' => (object) [
                'customer' => (object) ['name' => 'Beta'],
                'model' => 'MDL-2',
                'assy_name' => 'Housing',
            ],
            'project' => (object) [
                'customer' => 'Beta',
                'model' => 'MDL-2',
                'part_name' => 'Housing',
            ],
            'revision' => (object) [
                'version_label' => 'Rev 2',
            ],
        ];

        $filtered = $method->invoke($service, new Collection([$rowA, $rowB]), 'beta', 'a05');

        $this->assertCount(1, $filtered);
        $this->assertSame('a05', $filtered->first()->status);
    }
}
