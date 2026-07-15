<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Round all qty_req values to integers in material_breakdowns
        $castType = DB::getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';
        DB::table('material_breakdowns')->update([
            'qty_req' => DB::raw("CAST(ROUND(qty_req, 0) AS $castType)")
        ]);
    }

    public function down(): void
    {
        // No down migration needed - we're just rounding values
    }
};
