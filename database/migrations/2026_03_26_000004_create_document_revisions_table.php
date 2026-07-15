<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_project_id')->constrained('document_projects')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->date('received_date');
            $table->string('pic_engineering');
            $table->string('status')->default('pending_form_input');
            $table->timestamp('cogm_generated_at')->nullable();
            $table->string('pic_marketing')->nullable();
            $table->string('partlist_original_name');
            $table->string('partlist_file_path');
            $table->string('umh_original_name');
            $table->string('umh_file_path');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_project_id', 'version_number']);
            $table->index(['status', 'received_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_revisions');
    }
};
