<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRowNoToMaterialBreakdownsTable extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (!Schema::hasColumn('material_breakdowns', 'row_no')) {
                $table->string('row_no')->nullable()->after('material_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (Schema::hasColumn('material_breakdowns', 'row_no')) {
                $table->dropColumn('row_no');
            }
        });
    }
}
