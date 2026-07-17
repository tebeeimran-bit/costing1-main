<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('release_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('target_release_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('release_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_cycle_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('functional');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('tester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sla_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->foreignId('document_revision_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->string('pic')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->unsignedInteger('aging_days')->default(0);
            $table->unsignedTinyInteger('compliance')->default(100);
            $table->timestamps();
            $table->unique(['snapshot_date', 'document_revision_id']);
            $table->index(['snapshot_date', 'stage', 'is_overdue']);
        });

        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('costing_data_id')->nullable()->constrained('costing_data')->nullOnDelete();
            $table->foreignId('document_revision_id')->nullable()->constrained('document_revisions')->nullOnDelete();
            $table->string('type');
            $table->string('original_name')->nullable();
            $table->string('status')->default('previewed');
            $table->json('before_snapshot')->nullable();
            $table->json('after_summary')->nullable();
            $table->dateTime('rolled_back_at')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('ready');
            $table->string('checksum', 64)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('document_revisions', function (Blueprint $table) {
            $table->index(['document_project_id', 'version_number', 'id'], 'revision_latest_lookup_idx');
            $table->index(['status', 'updated_at'], 'revision_status_time_idx');
        });

        Schema::table('costing_data', function (Blueprint $table) {
            $table->index(['tracking_revision_id', 'id'], 'costing_revision_latest_idx');
        });
    }

    public function down(): void
    {
        Schema::table('costing_data', fn (Blueprint $table) => $table->dropIndex('costing_revision_latest_idx'));
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropIndex('revision_latest_lookup_idx');
            $table->dropIndex('revision_status_time_idx');
        });
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('import_runs');
        Schema::dropIfExists('sla_snapshots');
        Schema::dropIfExists('release_checks');
        Schema::dropIfExists('release_cycles');
        Schema::dropIfExists('company_holidays');
    }
};
