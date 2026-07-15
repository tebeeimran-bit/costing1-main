<?php

namespace Tests\Unit;

use App\Models\DocumentRevision;
use App\Services\TrackingDocument\TrackingDocumentFileService;
use App\Services\TrackingDocument\TrackingDocumentProjectService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TrackingDocumentProjectServiceTest extends TestCase
{
    public function test_resolve_document_update_returns_cleared_state_when_status_is_not_ada(): void
    {
        $service = new TrackingDocumentProjectService(new TrackingDocumentFileService());
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('resolveDocumentUpdate');
        $method->setAccessible(true);

        $revision = new DocumentRevision([
            'a00' => 'ada',
            'a00_document_file_path' => 'tracking-documents/a00/file.pdf',
            'a00_document_original_name' => 'file.pdf',
        ]);

        $request = new \Illuminate\Http\Request();
        $result = $method->invoke($service, $revision, $request, ['a00' => 'belum_ada'], 'a00');

        $this->assertSame([
            'status' => 'belum_ada',
            'path' => null,
            'name' => null,
        ], $result);
    }
}
