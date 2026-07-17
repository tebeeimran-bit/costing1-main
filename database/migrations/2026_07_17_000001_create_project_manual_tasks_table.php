<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_manual_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_project_id')->constrained('document_projects')->cascadeOnDelete();
            $table->foreignId('assignee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 30)->default('general');
            $table->string('priority', 20)->default('normal');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status', 20)->default('open');
            $table->date('due_at')->nullable();
            $table->timestamps();

            $table->index(['assignee_id', 'status', 'due_at']);
            $table->index(['document_project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_manual_tasks');
    }
};
