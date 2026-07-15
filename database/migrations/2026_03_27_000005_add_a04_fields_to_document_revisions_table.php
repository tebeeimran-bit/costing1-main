<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('a04')->nullable()->after('a00');
            $table->date('a04_received_date')->nullable()->after('a04');
            $table->string('a04_document_original_name')->nullable()->after('a04_received_date');
            $table->string('a04_document_file_path')->nullable()->after('a04_document_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'a04',
                'a04_received_date',
                'a04_document_original_name',
                'a04_document_file_path',
            ]);
        });
    }
};
