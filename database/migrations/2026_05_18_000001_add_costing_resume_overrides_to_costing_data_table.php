<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('costing_data', 'costing_resume_overrides')) {
            return;
        }

        Schema::table('costing_data', function (Blueprint $table) {
            $table->json('costing_resume_overrides')->nullable()->after('cycle_times');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('costing_data', 'costing_resume_overrides')) {
            return;
        }

        Schema::table('costing_data', function (Blueprint $table) {
            $table->dropColumn('costing_resume_overrides');
        });
    }
};
