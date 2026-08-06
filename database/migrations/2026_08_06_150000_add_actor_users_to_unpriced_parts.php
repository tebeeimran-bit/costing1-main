<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('unpriced_parts', function (Blueprint $table) {
            $table->foreignId('new_part_price_imported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unpriced_parts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('new_part_price_imported_by_id');
            $table->dropConstrainedForeignId('resolved_by_id');
        });
    }
};
