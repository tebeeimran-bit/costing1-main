<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('period_date');
            $table->decimal('usd_to_idr', 15, 2)->nullable();
            $table->decimal('jpy_to_idr', 15, 5)->nullable();
            $table->decimal('lme_copper', 15, 2)->nullable(); // USD/ton
            $table->string('source')->default('Manual');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
