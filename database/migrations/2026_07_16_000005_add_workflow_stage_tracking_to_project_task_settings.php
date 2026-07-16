<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_task_settings', function (Blueprint $table) {
            $table->string('workflow_stage', 40)->nullable()->after('due_at');
            $table->timestamp('stage_entered_at')->nullable()->after('workflow_stage');
            $table->index(['workflow_stage', 'stage_entered_at']);
        });
    }

    public function down(): void
    {
        Schema::table('project_task_settings', function (Blueprint $table) {
            $table->dropIndex(['workflow_stage', 'stage_entered_at']);
            $table->dropColumn(['workflow_stage', 'stage_entered_at']);
        });
    }
};
