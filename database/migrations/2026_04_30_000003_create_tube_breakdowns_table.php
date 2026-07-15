<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_data_id')->constrained('costing_data')->cascadeOnDelete();
            $table->foreignId('tube_id')->nullable()->constrained('tubes')->nullOnDelete();
            $table->string('tube_code')->nullable();
            $table->string('tube_name')->nullable();
            $table->string('spec')->nullable();
            $table->decimal('usage_qty', 20, 4)->default(0);
            $table->enum('usage_unit', ['pcs', 'meter', 'mm', 'set', 'unit'])->default('pcs');
            $table->decimal('price', 20, 4)->default(0);
            $table->enum('price_unit', ['pcs', 'meter', 'mm', 'set', 'unit'])->default('pcs');
            $table->decimal('amount', 20, 4)->default(0);
            $table->boolean('is_estimate')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['costing_data_id', 'tube_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tube_breakdowns');
    }
};
