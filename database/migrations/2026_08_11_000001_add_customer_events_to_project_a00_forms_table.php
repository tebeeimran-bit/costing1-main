<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_a00_forms', function (Blueprint $table) {
            $table->json('customer_events')->nullable()->after('sop_mp_tba');
        });
    }

    public function down(): void
    {
        Schema::table('project_a00_forms', function (Blueprint $table) {
            $table->dropColumn('customer_events');
        });
    }
};
