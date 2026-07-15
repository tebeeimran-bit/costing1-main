<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change qty_req column from decimal(15,4) to integer
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->integer('qty_req')->change();
        });
    }

    public function down(): void
    {
        // Revert column back to decimal(15,4)
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->decimal('qty_req', 15, 4)->change();
        });
    }
};
