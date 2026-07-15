<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (!Schema::hasColumn('material_breakdowns', 'unit_price_basis_text')) {
                $table->string('unit_price_basis_text')->nullable()->after('unit_price_basis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (Schema::hasColumn('material_breakdowns', 'unit_price_basis_text')) {
                $table->dropColumn('unit_price_basis_text');
            }
        });
    }
};
