<?php

namespace Tests\Feature;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_page_marks_correct_navigation_and_matches_password_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('permissions'))->assertOk();
        $this->assertSame(1, preg_match('/href="\/permissions"\s+class="sidebar-nav-item active"/', $response->getContent()));
        $response->assertSee('minlength="10"', false)->assertDontSee('minlength="6"', false);
    }

    public function test_short_password_is_rejected_when_creating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('permissions.store'), [
            'name' => 'New User', 'email' => 'new@example.test', 'password' => 'short123', 'role' => 'viewer',
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_active_admin_cannot_demote_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('permissions.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'viewer', 'password' => '',
        ])->assertRedirect(route('permissions'))->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_role_permission_can_be_updated_without_affecting_locked_modules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('permissions.update-access'), [
            'role' => 'viewer', 'module' => 'dashboard', 'access' => 'view',
        ])->assertRedirect(route('permissions'));
        $this->assertSame('view', RolePermission::where('role', 'viewer')->where('module', 'dashboard')->value('access'));

        $this->post(route('permissions.update-access'), [
            'role' => 'viewer', 'module' => 'user_management', 'access' => 'full',
        ])->assertSessionHas('error');
        $this->assertNotSame('full', RolePermission::where('role', 'viewer')->where('module', 'user_management')->value('access'));
    }
}
