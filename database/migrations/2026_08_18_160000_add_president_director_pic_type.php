<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pics', function (Blueprint $table) {
            $table->enum('type', ['engineering', 'marketing', 'president_director', 'director', 'div_marketing'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('pics', function (Blueprint $table) {
            $table->enum('type', ['engineering', 'marketing', 'director', 'div_marketing'])->change();
        });
    }
};
