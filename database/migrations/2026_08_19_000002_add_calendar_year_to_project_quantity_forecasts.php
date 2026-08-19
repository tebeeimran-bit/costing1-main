<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_quantity_forecasts', function (Blueprint $table) {
            $table->unsignedSmallInteger('calendar_year')->nullable()->after('year_number');
        });
    }

    public function down(): void
    {
        Schema::table('project_quantity_forecasts', function (Blueprint $table) {
            $table->dropColumn('calendar_year');
        });
    }
};
