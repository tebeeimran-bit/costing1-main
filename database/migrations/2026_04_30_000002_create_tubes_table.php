<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tubes', function (Blueprint $table) {
            $table->id();
            $table->string('tube_code')->unique();
            $table->string('tube_name')->nullable();
            $table->string('spec')->nullable();
            $table->string('material_type')->nullable();
            $table->decimal('diameter', 18, 4)->nullable();
            $table->decimal('thickness', 18, 4)->nullable();
            $table->decimal('length', 18, 4)->nullable();
            $table->enum('unit', ['pcs', 'meter', 'mm', 'set', 'unit'])->default('pcs');
            $table->decimal('price', 20, 4)->default(0);
            $table->enum('price_unit', ['pcs', 'meter', 'mm', 'set', 'unit'])->default('pcs');
            $table->string('currency', 10)->default('IDR');
            $table->string('supplier')->nullable();
            $table->date('effective_date')->nullable();
            $table->boolean('is_estimate')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tube_code', 'unit', 'price_unit']);
            $table->index(['supplier', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tubes');
    }
};
