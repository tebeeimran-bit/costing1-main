<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wire_rates', function (Blueprint $table) {
            $table->id();
            $table->date('period_month')->unique();
            $table->decimal('jpy_rate', 20, 5)->default(0);
            $table->decimal('usd_rate', 20, 5)->default(0);
            $table->decimal('lme_active', 20, 5)->default(0);
            $table->decimal('lme_reference', 20, 5)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wire_rates');
    }
};
