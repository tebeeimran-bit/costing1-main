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
        Schema::table('plants', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
        });

        // Existing rows used old name field as code; keep backward compatibility.
        DB::table('plants')
            ->whereNull('code')
            ->update(['code' => DB::raw('name')]);

        Schema::table('plants', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropUnique('plants_code_unique');
            $table->dropColumn('code');
        });
    }
};
