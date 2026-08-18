<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_their_own_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password-lama')]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('password_success');
        $this->assertTrue(Hash::check('password-baru-123', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password-lama')]);

        $response = $this->actingAs($user)->from(route('profile.show'))->put(route('profile.password.update'), [
            'current_password' => 'password-salah',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }
}
