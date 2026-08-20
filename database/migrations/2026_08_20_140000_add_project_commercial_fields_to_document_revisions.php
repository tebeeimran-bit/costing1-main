<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->decimal('forecast', 20, 4)->nullable();
            $table->string('forecast_uom', 20)->nullable();
            $table->string('forecast_basis', 20)->nullable();
            $table->unsignedInteger('project_period')->nullable();
            $table->boolean('spot_order')->default(false);
        });
    }
    public function down(): void {
        Schema::table('document_revisions', fn (Blueprint $table) => $table->dropColumn(['forecast','forecast_uom','forecast_basis','project_period','spot_order']));
    }
};
