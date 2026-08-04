<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->string('unit', 30)->nullable()->after('part_name');
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
