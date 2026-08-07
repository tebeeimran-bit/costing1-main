<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_project_id')->constrained('document_projects')->cascadeOnDelete();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->foreignId('workflow_task_id')->nullable()->constrained('project_workflow_tasks')->nullOnDelete();
            $table->enum('revision_type', ['design', 'partlist', 'drawing', 'umh']);
            $table->string('original_name');
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_revision_id', 'revision_type'], 'project_doc_revisions_revision_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_document_revisions');
    }
};
