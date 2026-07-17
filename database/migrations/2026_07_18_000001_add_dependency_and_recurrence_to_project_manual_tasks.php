<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_manual_tasks', function (Blueprint $table) {
            $table->foreignId('depends_on_task_id')->nullable()->after('created_by_id')->constrained('project_manual_tasks')->nullOnDelete();
            $table->string('recurrence', 20)->nullable()->after('due_at');
        });
    }

    public function down(): void
    {
        Schema::table('project_manual_tasks', function (Blueprint $table) {
            $table->dropForeign(['depends_on_task_id']);
            $table->dropColumn(['depends_on_task_id', 'recurrence']);
        });
    }
};
