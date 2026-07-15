<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_data_id')->constrained('costing_data')->onDelete('cascade');
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            
            $table->decimal('qty_req', 15, 4)->default(0);
            $table->decimal('amount1', 15, 4)->default(0);
            $table->decimal('unit_price_basis', 15, 4)->default(0);
            $table->string('currency')->default('IDR'); // IDR, USD, JPY
            $table->decimal('qty_moq', 15, 4)->default(0);
            $table->string('cn_type')->default('N'); // C or N
            $table->decimal('import_tax_percent', 5, 2)->default(0);
            $table->decimal('amount2', 15, 4)->default(0);
            $table->string('currency2')->default('IDR');
            $table->decimal('unit_price2', 15, 4)->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_breakdowns');
    }
};
