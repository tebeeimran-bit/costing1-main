<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('part_no')->unique();
            $table->string('id_code')->nullable();
            $table->string('part_name');
            $table->string('unit')->default('PCS');
            $table->string('pro_code')->nullable();
            $table->string('supplier_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
