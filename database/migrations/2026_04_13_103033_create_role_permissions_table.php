<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('module');
            $table->string('access')->default('none'); // full, view, none
            $table->timestamps();
            $table->unique(['role', 'module']);
        });

        // Seed default permissions
        $defaults = [
            ['admin', 'dashboard', 'full'],
            ['admin', 'input_data', 'full'],
            ['admin', 'database', 'full'],
            ['admin', 'laporan', 'full'],
            ['admin', 'user_management', 'full'],
            ['editor', 'dashboard', 'full'],
            ['editor', 'input_data', 'full'],
            ['editor', 'database', 'full'],
            ['editor', 'laporan', 'full'],
            ['editor', 'user_management', 'none'],
            ['viewer', 'dashboard', 'view'],
            ['viewer', 'input_data', 'none'],
            ['viewer', 'database', 'view'],
            ['viewer', 'laporan', 'view'],
            ['viewer', 'user_management', 'none'],
        ];

        foreach ($defaults as [$role, $module, $access]) {
            \DB::table('role_permissions')->insert([
                'role' => $role,
                'module' => $module,
                'access' => $access,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
