<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->string('model')->nullable()->after('wo_number');
            $table->string('assy_no')->nullable()->after('model');
            $table->string('assy_name')->nullable()->after('assy_no');
            $table->string('line')->nullable()->after('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->dropColumn(['model', 'assy_no', 'assy_name', 'line']);
        });
    }
};
