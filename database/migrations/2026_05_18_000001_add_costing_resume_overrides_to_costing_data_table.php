<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->json('costing_resume_overrides')->nullable()->after('cycle_times');
        });
    }

    public function down(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->dropColumn('costing_resume_overrides');
        });
    }
};