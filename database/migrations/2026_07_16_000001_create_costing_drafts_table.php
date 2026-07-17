<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tracking_revision_id')->nullable()->constrained('document_revisions')->cascadeOnDelete();
            $table->foreignId('costing_data_id')->nullable()->constrained('costing_data')->cascadeOnDelete();
            $table->string('draft_key', 120);
            $table->longText('payload');
            $table->timestamp('saved_at');
            $table->timestamps();

            $table->unique(['user_id', 'draft_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_drafts');
    }
};
