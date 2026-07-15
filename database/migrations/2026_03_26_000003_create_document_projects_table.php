<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_projects', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->string('model');
            $table->string('part_number');
            $table->string('part_name');
            $table->string('project_key', 64)->unique();
            $table->timestamps();

            $table->index(['customer', 'model', 'part_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_projects');
    }
};
