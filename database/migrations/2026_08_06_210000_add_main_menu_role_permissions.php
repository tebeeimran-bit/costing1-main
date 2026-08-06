<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'admin_control_project' => ['project' => 'view'],
            'admin_costing' => ['project' => 'view', 'inbox_breakdown' => 'full', 'inbox_costing' => 'full', 'inbox_new_part_request' => 'full', 'inbox_marketing' => 'view'],
            'coordinator_costing' => ['project' => 'view', 'inbox_costing' => 'full', 'inbox_new_part_request' => 'full', 'inbox_marketing' => 'view'],
            'document_control' => ['project' => 'view'],
            'engineering' => ['project' => 'view'],
            'marketing' => ['project' => 'view', 'inbox_marketing' => 'full'],
            'editor' => ['project' => 'full', 'inbox_costing' => 'full', 'inbox_new_part_request' => 'full'],
            'viewer' => ['project' => 'view'],
        ];
        $modules = ['project', 'inbox_breakdown', 'inbox_costing', 'inbox_new_part_request', 'inbox_marketing'];

        foreach ($defaults as $role => $grants) {
            foreach ($modules as $module) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'module' => $module],
                    ['access' => $grants[$module] ?? 'none', 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereIn('module', [
            'project', 'inbox_breakdown', 'inbox_costing', 'inbox_new_part_request', 'inbox_marketing',
        ])->delete();
    }
};
