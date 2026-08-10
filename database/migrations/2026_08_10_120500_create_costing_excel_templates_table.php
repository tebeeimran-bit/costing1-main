<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_excel_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('assy_count')->unique();
            $table->string('name');
            $table->string('original_name');
            $table->string('file_path');
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('costing_excel_templates');
    }
};
