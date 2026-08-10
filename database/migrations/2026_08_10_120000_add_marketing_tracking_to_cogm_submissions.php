<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cogm_submissions', function (Blueprint $table) {
            $table->string('marketing_status')->nullable()->index();
            $table->text('marketing_status_reason')->nullable();
            $table->timestamp('marketing_status_at')->nullable();
            $table->timestamp('waiting_since')->nullable();
        });
        Schema::create('cogm_submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cogm_submission_id')->constrained('cogm_submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->string('source')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('cogm_value', 20, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cogm_submission_events');
        Schema::table('cogm_submissions', fn (Blueprint $table) => $table->dropColumn(['marketing_status','marketing_status_reason','marketing_status_at','waiting_since']));
    }
};
