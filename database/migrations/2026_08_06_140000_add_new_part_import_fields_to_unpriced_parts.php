<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unpriced_parts', function (Blueprint $table) {
            $table->string('purchase_unit')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('moq', 20, 6)->nullable();
            $table->string('cn_type')->nullable();
            $table->string('maker')->nullable();
            $table->decimal('add_cost_percent', 10, 4)->nullable();
            $table->timestamp('new_part_price_imported_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('unpriced_parts', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'currency', 'moq', 'cn_type', 'maker', 'add_cost_percent', 'new_part_price_imported_at']);
        });
    }
};
