<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wire_rates', function (Blueprint $table) {
            $table->string('request_name')->nullable()->after('period_month');
            $table->date('period_month')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wire_rates', function (Blueprint $table) {
            $table->dropColumn('request_name');
            $table->date('period_month')->nullable(false)->change();
        });
    }
};
