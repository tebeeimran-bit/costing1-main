<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->decimal('amount1', 20, 6)->nullable()->default(null)->change();
            $table->decimal('unit_price_basis', 20, 6)->nullable()->default(null)->change();
            $table->string('currency')->nullable()->default(null)->change();
            $table->decimal('qty_moq', 20, 6)->nullable()->default(null)->change();
            $table->string('cn_type')->nullable()->default(null)->change();
            $table->decimal('import_tax_percent', 10, 4)->nullable()->default(null)->change();
            $table->string('supplier')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('material_breakdowns', function (Blueprint $table) {
            $table->dropColumn('supplier');
            $table->decimal('amount1', 20, 6)->default(0)->nullable(false)->change();
            $table->decimal('unit_price_basis', 20, 6)->default(0)->nullable(false)->change();
            $table->string('currency')->default('IDR')->nullable(false)->change();
            $table->decimal('qty_moq', 20, 6)->default(0)->nullable(false)->change();
            $table->string('cn_type')->default('N')->nullable(false)->change();
            $table->decimal('import_tax_percent', 10, 4)->default(0)->nullable(false)->change();
        });
    }
};
