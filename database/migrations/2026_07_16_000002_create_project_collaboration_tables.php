<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->unique()->constrained('document_revisions')->cascadeOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('set_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['document_revision_id', 'occurred_at']);
        });

        Schema::create('project_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('mentioned_user_ids')->nullable();
            $table->timestamps();
            $table->index(['document_revision_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_comments');
        Schema::dropIfExists('project_activities');
        Schema::dropIfExists('project_task_settings');
    }
};
