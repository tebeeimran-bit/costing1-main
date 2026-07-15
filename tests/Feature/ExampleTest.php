<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_user_can_login_using_name(): void
    {
        User::factory()->create([
            'name' => 'Admin Costing',
            'email' => 'admin-costing@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'Admin Costing',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/project-selection');
        $this->assertAuthenticated();
    }
}