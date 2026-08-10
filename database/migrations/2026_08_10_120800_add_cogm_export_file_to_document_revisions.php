<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('cogm_export_original_name')->nullable()->after('cogm_import_uploaded_at');
            $table->string('cogm_export_file_path')->nullable()->after('cogm_export_original_name');
            $table->timestamp('cogm_exported_at')->nullable()->after('cogm_export_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn(['cogm_export_original_name', 'cogm_export_file_path', 'cogm_exported_at']);
        });
    }
};
