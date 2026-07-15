<?php

namespace Tests\Feature;

use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrackingDocumentUpdateFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_files_updates_existing_revision_and_increments_file_counter(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        Storage::fake();

        $oldPartlistPath = 'tracking-documents/partlist/old-partlist.xlsx';
        $oldUmhPath = 'tracking-documents/umh/old-umh.xlsx';

        Storage::put($oldPartlistPath, 'old partlist content');
        Storage::put($oldUmhPath, 'old umh content');

        $project = DocumentProject::create([
            'customer' => 'AHM',
            'model' => 'K4MA',
            'part_number' => '32100-K4MA-W203',
            'part_name' => 'WIRE HARNESS',
            'project_key' => hash('sha256', 'ahm|k4ma|32100-k4ma-w203|wire harness'),
        ]);

        $revision = DocumentRevision::create([
            'document_project_id' => $project->id,
            'version_number' => 1,
            'received_date' => now()->toDateString(),
            'pic_engineering' => 'Imran',
            'status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
            'partlist_original_name' => 'old-partlist.xlsx',
            'partlist_file_path' => $oldPartlistPath,
            'umh_original_name' => 'old-umh.xlsx',
            'umh_file_path' => $oldUmhPath,
            'notes' => 'Initial revision',
            'change_remark' => 'Dokumen awal diterima (baseline V0).',
        ]);

        $response = $this->post(route('tracking-documents.update-files', ['revision' => $revision->id]), [
            'partlist_file' => UploadedFile::fake()->create('new-partlist.xlsx', 120),
            'change_remark' => 'Update partlist dari engineering',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('document_revisions', 1);

        $updatedRevision = $revision->fresh();

        $this->assertNotNull($updatedRevision);
        $this->assertSame(1, $updatedRevision->version_number);
        $this->assertSame(DocumentRevision::STATUS_SUBMITTED_TO_MARKETING, $updatedRevision->status);
        $this->assertSame('new-partlist.xlsx', $updatedRevision->partlist_original_name);
        $this->assertNotSame($oldPartlistPath, $updatedRevision->partlist_file_path);
        $this->assertSame(1, (int) $updatedRevision->partlist_update_count);
        $this->assertNotNull($updatedRevision->partlist_updated_at);
        $this->assertSame($oldUmhPath, $updatedRevision->umh_file_path);
        $this->assertSame('old-umh.xlsx', $updatedRevision->umh_original_name);
        $this->assertSame(0, (int) $updatedRevision->umh_update_count);
        $this->assertSame('Update partlist dari engineering', $updatedRevision->change_remark);

        $this->assertTrue(Storage::exists($oldUmhPath));
        $this->assertTrue(Storage::exists($updatedRevision->partlist_file_path));
    }
}
