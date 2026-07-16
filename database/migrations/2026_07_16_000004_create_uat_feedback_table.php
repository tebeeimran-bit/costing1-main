<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uat_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40);
            $table->string('severity', 20)->default('medium');
            $table->string('title');
            $table->text('description');
            $table->text('page_url')->nullable();
            $table->string('route_name')->nullable();
            $table->text('browser')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('status', 30)->default('open');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uat_feedback');
    }
};
