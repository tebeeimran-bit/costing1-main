<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_control_registrations', function (Blueprint $table) {
            $table->foreignId('document_project_id')->nullable()->after('id')->constrained('document_projects')->nullOnDelete();
            $table->foreignId('document_revision_id')->nullable()->after('document_project_id')->constrained('document_revisions')->nullOnDelete();
            $table->foreignId('workflow_task_id')->nullable()->unique()->after('document_revision_id')->constrained('project_workflow_tasks')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('document_control_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workflow_task_id');
            $table->dropConstrainedForeignId('document_revision_id');
            $table->dropConstrainedForeignId('document_project_id');
        });
    }
};
