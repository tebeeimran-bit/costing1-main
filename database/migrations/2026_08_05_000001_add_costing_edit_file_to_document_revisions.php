<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('costing_edit_original_name')->nullable();
            $table->string('costing_edit_file_path')->nullable();
            $table->timestamp('costing_edit_uploaded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn(['costing_edit_original_name', 'costing_edit_file_path', 'costing_edit_uploaded_at']);
        });
    }
};
