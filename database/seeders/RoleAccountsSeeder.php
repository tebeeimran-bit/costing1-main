<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('INITIAL_ROLE_PASSWORD');
        if (blank($password)) {
            $this->command?->warn('Role accounts skipped. Set INITIAL_ROLE_PASSWORD in .env before running this seeder.');
            return;
        }
        $accounts = [
            ['name' => 'Admin Control Project', 'email' => 'control.project@costing.local', 'role' => 'admin_control_project'],
            ['name' => 'Admin Costing', 'email' => 'admin.costing@costing.local', 'role' => 'admin_costing'],
            ['name' => 'Coordinator Costing', 'email' => 'coordinator.costing@costing.local', 'role' => 'coordinator_costing'],
            ['name' => 'Document Control', 'email' => 'document.control@costing.local', 'role' => 'document_control'],
            ['name' => 'Editor', 'email' => 'editor@costing.local', 'role' => 'editor'],
            ['name' => 'Viewer', 'email' => 'viewer@costing.local', 'role' => 'viewer'],
            ['name' => 'DAFFA', 'email' => 'daffa@costing.local', 'role' => 'engineering'],
            ['name' => 'DWI D', 'email' => 'dwi.d@costing.local', 'role' => 'marketing'],
        ];

        foreach ($accounts as $account) {
            User::firstOrCreate(
                ['email' => $account['email']],
                $account + ['password' => $password]
            );
        }

        $engineeringPermissions = [
            'dashboard' => 'view',
            'control_project' => 'view',
            'document_control' => 'view',
            'input_data' => 'view',
            'database' => 'view',
            'laporan' => 'view',
            'user_management' => 'none',
        ];

        foreach ($engineeringPermissions as $module => $access) {
            RolePermission::updateOrCreate(
                ['role' => 'engineering', 'module' => $module],
                ['access' => $access]
            );
        }
    }
}
