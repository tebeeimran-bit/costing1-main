<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->foreignId('exchange_rate_id')
                ->nullable()
                ->after('assy_name')
                ->constrained('exchange_rates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exchange_rate_id');
        });
    }
};
