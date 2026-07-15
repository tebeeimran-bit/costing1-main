<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('unpriced_parts', 'qty')) {
            Schema::table('unpriced_parts', function (Blueprint $table) {
                $table->dropColumn('qty');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('unpriced_parts', 'qty')) {
            Schema::table('unpriced_parts', function (Blueprint $table) {
                $table->decimal('qty', 15, 4)->default(0)->after('part_name');
            });
        }
    }
};
