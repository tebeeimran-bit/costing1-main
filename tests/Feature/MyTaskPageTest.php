<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTaskPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_personal_task_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('my-tasks'))
            ->assertOk()
            ->assertSee('Yang perlu Anda kerjakan sekarang')
            ->assertSee('Tidak ada tugas pada kategori ini');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('my-tasks'))->assertRedirect(route('login'));
    }
}
