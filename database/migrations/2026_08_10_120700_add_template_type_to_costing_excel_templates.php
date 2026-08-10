<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costing_excel_templates', function (Blueprint $table) {
            $table->string('template_type', 30)->default('costing')->after('id');
            $table->dropUnique(['assy_count']);
            $table->unique(['template_type', 'assy_count'], 'costing_excel_templates_type_assy_unique');
        });
    }

    public function down(): void
    {
        Schema::table('costing_excel_templates', function (Blueprint $table) {
            $table->dropUnique('costing_excel_templates_type_assy_unique');
            $table->unique('assy_count');
            $table->dropColumn('template_type');
        });
    }
};
