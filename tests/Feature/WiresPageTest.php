<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WiresPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_wires_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/database/wires');

        $response
            ->assertOk()
            ->assertSee('Rates')
            ->assertSee('Daftar Wire')
            ->assertSee('Tambah Wire');
    }
}
