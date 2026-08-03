<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_project_id')->constrained('document_projects')->cascadeOnDelete();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->string('stage', 30)->index();
            $table->string('assigned_role', 50)->index();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['document_revision_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_workflow_tasks');
    }
};
