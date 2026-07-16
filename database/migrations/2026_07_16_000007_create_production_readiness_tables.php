<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('level')->default('info');
            $table->json('audiences')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('severity')->default('info');
            $table->string('route')->nullable();
            $table->string('method', 12)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('memory_kb')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['type', 'severity', 'occurred_at']);
            $table->index(['route', 'duration_ms']);
        });

        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->dateTime('occurred_at');
            $table->index(['identifier', 'occurred_at']);
            $table->index(['successful', 'occurred_at']);
        });

        Schema::create('approval_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegate_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['delegator_id', 'is_active', 'starts_at', 'ends_at'], 'approval_delegate_window_idx');
        });

        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->json('filters')->nullable();
            $table->string('filename')->nullable();
            $table->string('path')->nullable();
            $table->string('status')->default('ready');
            $table->unsignedInteger('row_count')->default(0);
            $table->string('frequency')->nullable();
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'scheduled_for']);
        });

        Schema::table('costing_approvals', function (Blueprint $table) {
            $table->string('signature_hash', 64)->nullable()->after('approval_notes');
            $table->foreignId('delegated_by_id')->nullable()->after('approved_by_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('costing_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delegated_by_id');
            $table->dropColumn('signature_hash');
        });
        Schema::dropIfExists('export_jobs');
        Schema::dropIfExists('approval_delegations');
        Schema::dropIfExists('login_activities');
        Schema::dropIfExists('system_events');
        Schema::dropIfExists('announcements');
    }
};
