<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Recalculate material_cost for all costing data based on material_breakdowns
        // Uses qty_req * unit_price_basis sum
        DB::statement('
            UPDATE costing_data
            SET material_cost = (
                SELECT COALESCE(SUM(qty_req * unit_price_basis), 0)
                FROM material_breakdowns
                WHERE costing_data_id = costing_data.id
            )
        ');
    }

    public function down(): void
    {
        // No rollback - this fixes data integrity
    }
};
