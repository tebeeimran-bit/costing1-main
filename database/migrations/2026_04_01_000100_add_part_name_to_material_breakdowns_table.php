<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartNameToMaterialBreakdownsTable extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (!Schema::hasColumn('material_breakdowns', 'part_name')) {
                $table->string('part_name')->nullable()->after('id_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (Schema::hasColumn('material_breakdowns', 'part_name')) {
                $table->dropColumn('part_name');
            }
        });
    }
}
