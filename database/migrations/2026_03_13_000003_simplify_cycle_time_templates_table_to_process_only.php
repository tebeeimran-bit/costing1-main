<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop index safely for both SQLite and MySQL
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS cycle_time_templates_sequence_unique');
        } else {
            // In MySQL, try to drop the index; ignore error if it doesn't exist
            try {
                Schema::table('cycle_time_templates', function (Blueprint $table) {
                    $table->dropUnique('cycle_time_templates_sequence_unique');
                });
            } catch (\Exception $e) {
                // Index doesn't exist, skip
            }
        }

        Schema::table('cycle_time_templates', function (Blueprint $table) {
            $table->dropColumn([
                'sequence',
                'qty',
                'time_hour',
                'time_sec',
                'time_sec_per_qty',
                'cost_per_sec',
                'cost_per_unit',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cycle_time_templates', function (Blueprint $table) {
            $table->unsignedInteger('sequence')->nullable();
            $table->decimal('qty', 18, 4)->nullable();
            $table->decimal('time_hour', 18, 6)->nullable();
            $table->decimal('time_sec', 18, 4)->nullable();
            $table->decimal('time_sec_per_qty', 18, 4)->nullable();
            $table->decimal('cost_per_sec', 18, 4)->nullable();
            $table->decimal('cost_per_unit', 18, 4)->nullable();
        });
    }
};
