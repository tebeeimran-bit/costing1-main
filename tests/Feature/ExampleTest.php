<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DocumentProject;
use App\Models\DocumentRevision;
use App\Models\RolePermission;
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

    public function test_admin_can_render_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertSee('Total Project Tracking (Semua Periode)');
    }

    public function test_marketing_dashboard_only_shows_projects_assigned_to_the_logged_in_pic(): void
    {
        $user = User::factory()->create(['name' => 'DWI D', 'role' => 'marketing']);
        RolePermission::updateOrCreate(
            ['role' => 'marketing', 'module' => 'dashboard'],
            ['access' => 'view']
        );

        $ownProject = DocumentProject::create([
            'customer' => 'Samsung', 'model' => 'K5MA', 'part_number' => 'W40295',
            'part_name' => 'PROJECT MILIK DWI', 'project_key' => 'dashboard-dwi',
        ]);
        $otherProject = DocumentProject::create([
            'customer' => 'Astra', 'model' => 'K4MA', 'part_number' => 'W40294',
            'part_name' => 'PROJECT MILIK PIC LAIN', 'project_key' => 'dashboard-other',
        ]);

        foreach ([[$ownProject, 'DWI D'], [$otherProject, 'MIRA']] as [$project, $picMarketing]) {
            DocumentRevision::create([
                'document_project_id' => $project->id,
                'version_number' => 1,
                'received_date' => '2026-08-06',
                'pic_engineering' => 'DAFFA',
                'pic_marketing' => $picMarketing,
                'status' => DocumentRevision::STATUS_SUBMITTED_TO_MARKETING,
                'partlist_original_name' => 'partlist.xlsx',
                'partlist_file_path' => 'tests/partlist.xlsx',
                'umh_original_name' => 'umh.xlsx',
                'umh_file_path' => 'tests/umh.xlsx',
            ]);
        }

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Dashboard Marketing')
            ->assertSee('PROJECT MILIK DWI')
            ->assertDontSee('PROJECT MILIK PIC LAIN')
            ->assertDontSee('Total Project Tracking (Semua Periode)');
    }

    public function test_engineering_dashboard_only_shows_projects_assigned_to_the_logged_in_pic(): void
    {
        $user = User::factory()->create(['name' => 'DAFFA', 'role' => 'engineering']);
        RolePermission::updateOrCreate(
            ['role' => 'engineering', 'module' => 'dashboard'],
            ['access' => 'view']
        );

        foreach ([['PROJECT ENGINEERING SAYA', 'DAFFA', 'eng-own'], ['PROJECT ENGINEERING LAIN', 'RANGGA', 'eng-other']] as [$name, $pic, $key]) {
            $project = DocumentProject::create([
                'customer' => 'Samsung', 'model' => 'K5MA', 'part_number' => strtoupper($key),
                'part_name' => $name, 'project_key' => $key,
            ]);
            DocumentRevision::create([
                'document_project_id' => $project->id,
                'version_number' => 1,
                'received_date' => '2026-08-06',
                'pic_engineering' => $pic,
                'pic_marketing' => 'DWI D',
                'status' => DocumentRevision::STATUS_PENDING_FORM_INPUT,
                'partlist_original_name' => 'partlist.xlsx',
                'partlist_file_path' => 'tests/partlist.xlsx',
                'umh_original_name' => 'umh.xlsx',
                'umh_file_path' => 'tests/umh.xlsx',
            ]);
        }

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Dashboard Engineering')
            ->assertSee('PROJECT ENGINEERING SAYA')
            ->assertDontSee('PROJECT ENGINEERING LAIN');
    }

    public function test_authenticated_role_can_open_dashboard_without_a_permission_record(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Costing Tanpa Permission',
            'role' => 'admin_costing',
        ]);
        RolePermission::where('role', 'admin_costing')->where('module', 'dashboard')->delete();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Dashboard Admin Costing');
    }

    public function test_admin_can_save_multiple_role_permissions_at_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/permissions/update-access', [
            'permissions' => [
                'marketing' => ['dashboard' => 'view', 'laporan' => 'full'],
                'engineering' => ['dashboard' => 'view', 'input_data' => 'full'],
            ],
        ])->assertRedirect(route('permissions'));

        $this->assertDatabaseHas('role_permissions', ['role' => 'marketing', 'module' => 'laporan', 'access' => 'full']);
        $this->assertDatabaseHas('role_permissions', ['role' => 'engineering', 'module' => 'input_data', 'access' => 'full']);
    }

    public function test_sidebar_menu_follows_saved_role_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admin_control_project']);
        foreach (['input_data', 'database', 'laporan', 'document_control'] as $module) {
            RolePermission::updateOrCreate(
                ['role' => 'admin_control_project', 'module' => $module],
                ['access' => 'none']
            );
        }
        RolePermission::updateOrCreate(
            ['role' => 'admin_control_project', 'module' => 'control_project'],
            ['access' => 'full']
        );

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Control Project')
            ->assertDontSee('Inbox Marketing')
            ->assertDontSee('Inbox Breakdown')
            ->assertDontSee('Inbox Costing')
            ->assertDontSee('Laporan &amp; Export', false)
            ->assertDontSee('<span>Database</span>', false);
    }

    public function test_new_main_menu_permissions_also_protect_direct_urls(): void
    {
        $user = User::factory()->create(['role' => 'admin_control_project']);
        RolePermission::updateOrCreate(
            ['role' => 'admin_control_project', 'module' => 'project'],
            ['access' => 'view']
        );
        RolePermission::updateOrCreate(
            ['role' => 'admin_control_project', 'module' => 'inbox_marketing'],
            ['access' => 'none']
        );

        $this->actingAs($user)->get('/project')->assertOk();
        $this->actingAs($user)->get('/marketing/cogm-inbox')->assertForbidden();
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
