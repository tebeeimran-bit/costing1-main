<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unpriced_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->foreignId('costing_data_id')->nullable()->constrained('costing_data')->nullOnDelete();
            $table->string('part_number');
            $table->string('part_name')->nullable();
            $table->decimal('qty', 15, 4)->default(0);
            $table->decimal('detected_price', 20, 4)->nullable();
            $table->decimal('manual_price', 20, 4)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['document_revision_id', 'resolved_at']);
            $table->index(['document_revision_id', 'part_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unpriced_parts');
    }
};
