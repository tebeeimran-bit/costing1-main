<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop unique constraint on material_code to allow duplicates
        try {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropUnique(['material_code']);
            });
        } catch (\Exception $e) {
            // Constraint might not exist
        }
    }

    public function down(): void
    {
        // Restore unique constraint if needed
        try {
            Schema::table('materials', function (Blueprint $table) {
                $table->unique('material_code');
            });
        } catch (\Exception $e) {
            // Constraint restoration failed
        }
    }
};
