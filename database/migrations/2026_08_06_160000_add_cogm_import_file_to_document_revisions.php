<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('cogm_import_original_name')->nullable();
            $table->string('cogm_import_file_path')->nullable();
            $table->timestamp('cogm_import_uploaded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'cogm_import_original_name',
                'cogm_import_file_path',
                'cogm_import_uploaded_at',
            ]);
        });
    }
};
