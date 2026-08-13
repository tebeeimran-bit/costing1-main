<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_parts_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/database/parts');

        $response
            ->assertOk()
            ->assertSee('Update Database Part via Excel')
            ->assertSee('Tambah Material Baru')
            ->assertSee('Hapus Semua Data');
    }
}
