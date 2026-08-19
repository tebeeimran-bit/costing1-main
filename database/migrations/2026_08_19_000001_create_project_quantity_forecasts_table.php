<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_quantity_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->string('period_type', 10)->default('year');
            $table->unsignedSmallInteger('year_number');
            $table->unsignedTinyInteger('month_number')->nullable();
            $table->decimal('quantity', 20, 4);
            $table->string('uom', 20)->default('PCE');
            $table->timestamps();

            $table->unique(
                ['document_revision_id', 'period_type', 'year_number', 'month_number'],
                'project_quantity_forecasts_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_quantity_forecasts');
    }
};
