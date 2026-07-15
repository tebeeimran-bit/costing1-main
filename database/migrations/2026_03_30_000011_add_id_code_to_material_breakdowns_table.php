<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (!Schema::hasColumn('material_breakdowns', 'id_code')) {
                $table->string('id_code')->nullable()->after('material_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (Schema::hasColumn('material_breakdowns', 'id_code')) {
                $table->dropColumn('id_code');
            }
        });
    }
};
