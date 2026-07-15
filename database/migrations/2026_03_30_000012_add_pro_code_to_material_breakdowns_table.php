<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (!Schema::hasColumn('material_breakdowns', 'pro_code')) {
                $table->string('pro_code')->nullable()->after('id_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            if (Schema::hasColumn('material_breakdowns', 'pro_code')) {
                $table->dropColumn('pro_code');
            }
        });
    }
};
