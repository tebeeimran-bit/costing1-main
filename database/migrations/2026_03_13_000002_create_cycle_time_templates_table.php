<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cycle_time_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sequence')->unique();
            $table->string('process');
            $table->decimal('qty', 18, 4)->nullable();
            $table->decimal('time_hour', 18, 6)->nullable();
            $table->decimal('time_sec', 18, 4)->nullable();
            $table->decimal('time_sec_per_qty', 18, 4)->nullable();
            $table->decimal('cost_per_sec', 18, 4)->default(10.33);
            $table->decimal('cost_per_unit', 18, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_time_templates');
    }
};
