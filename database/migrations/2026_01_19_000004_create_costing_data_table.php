<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('period'); // e.g., "2025-01"
            $table->string('wo_number')->nullable();
            
            // Exchange rates
            $table->decimal('exchange_rate_usd', 15, 2)->default(15500);
            $table->decimal('exchange_rate_jpy', 15, 2)->default(103);
            $table->decimal('lme_rate', 15, 2)->nullable();
            
            // Production parameters
            $table->integer('forecast')->default(0);
            $table->integer('project_period')->default(12);
            
            // Actual costs
            $table->decimal('material_cost', 20, 2)->default(0);
            $table->decimal('labor_cost', 20, 2)->default(0);
            $table->decimal('overhead_cost', 20, 2)->default(0);
            $table->decimal('scrap_cost', 20, 2)->default(0);
            $table->decimal('revenue', 20, 2)->default(0);
            $table->integer('qty_good')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_data');
    }
};
