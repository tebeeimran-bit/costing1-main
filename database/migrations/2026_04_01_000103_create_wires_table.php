<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wires', function (Blueprint $table) {
            $table->id();
            $table->string('idcode')->unique();
            $table->string('item');
            $table->string('machine_maintenance');
            $table->decimal('fix_cost', 20, 5)->default(0);
            $table->decimal('price', 20, 5)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wires');
    }
};
