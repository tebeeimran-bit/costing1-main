<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->foreignId('costing_data_id')->nullable()->constrained('costing_data')->nullOnDelete();
            $table->string('status')->default('waiting_coordinator_approval');
            $table->decimal('cogm_value', 20, 2)->nullable();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('submit_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_notes')->nullable();
            $table->timestamps();

            $table->index(['document_revision_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        $roles = [
            'admin_costing' => [
                'dashboard' => 'view',
                'input_data' => 'full',
                'database' => 'view',
                'laporan' => 'view',
                'user_management' => 'none',
            ],
            'coordinator_costing' => [
                'dashboard' => 'view',
                'input_data' => 'view',
                'database' => 'view',
                'laporan' => 'full',
                'user_management' => 'none',
            ],
            'marketing' => [
                'dashboard' => 'view',
                'input_data' => 'none',
                'database' => 'none',
                'laporan' => 'view',
                'user_management' => 'none',
            ],
        ];

        foreach ($roles as $role => $modules) {
            foreach ($modules as $module => $access) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'module' => $module],
                    ['access' => $access, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_approvals');

        DB::table('role_permissions')
            ->whereIn('role', ['admin_costing', 'coordinator_costing', 'marketing'])
            ->delete();
    }
};