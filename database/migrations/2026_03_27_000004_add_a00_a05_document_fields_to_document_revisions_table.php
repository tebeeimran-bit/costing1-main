<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('a00_document_original_name')->nullable()->after('a00_received_date');
            $table->string('a00_document_file_path')->nullable()->after('a00_document_original_name');
            $table->string('a05_document_original_name')->nullable()->after('a05_received_date');
            $table->string('a05_document_file_path')->nullable()->after('a05_document_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'a00_document_original_name',
                'a00_document_file_path',
                'a05_document_original_name',
                'a05_document_file_path',
            ]);
        });
    }
};
