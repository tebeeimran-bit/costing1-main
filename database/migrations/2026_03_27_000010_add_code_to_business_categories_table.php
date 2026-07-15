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
        Schema::table('business_categories', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        DB::table('business_categories')
            ->whereNull('code')
            ->update(['code' => DB::raw('name')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_categories', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
