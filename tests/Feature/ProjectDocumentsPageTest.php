<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_project_documents_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/database/project-documents');

        $response
            ->assertOk()
            ->assertSee('Pengumpulan Dokumen Engineering')
            ->assertSee('Pengumpulan Dokumen A00, A04 &amp; A05', false)
            ->assertSee('Edit Project Document');
    }
}
