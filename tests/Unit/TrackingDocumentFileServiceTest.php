<?php

namespace Tests\Unit;

use App\Services\TrackingDocument\TrackingDocumentFileService;
use Tests\TestCase;

class TrackingDocumentFileServiceTest extends TestCase
{
    public function test_build_file_update_payload_uses_expected_revision_fields(): void
    {
        $service = new TrackingDocumentFileService();

        $payload = $service->buildFileUpdatePayload('a04', [
            'path' => 'tracking-documents/a04/example.pdf',
            'name' => 'example.pdf',
        ]);

        $this->assertSame([
            'a04_document_file_path' => 'tracking-documents/a04/example.pdf',
            'a04_document_original_name' => 'example.pdf',
        ], $payload);
    }

    public function test_clear_file_reference_sets_fields_to_null(): void
    {
        $service = new TrackingDocumentFileService();

        $this->assertSame([
            'partlist_file_path' => null,
            'partlist_original_name' => null,
        ], $service->clearFileReference('partlist'));
    }
}
