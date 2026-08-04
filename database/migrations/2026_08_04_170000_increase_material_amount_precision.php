<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->decimal('amount2', 18, 8)->default(0)->change();
            $table->decimal('unit_price2', 18, 8)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->decimal('amount2', 15, 4)->default(0)->change();
            $table->decimal('unit_price2', 15, 4)->default(0)->change();
        });
    }
};
