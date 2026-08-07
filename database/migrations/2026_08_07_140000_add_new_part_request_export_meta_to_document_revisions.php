<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->timestamp('new_part_request_exported_at')->nullable();
            $table->foreignId('new_part_request_exported_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('new_part_request_exported_by_id');
            $table->dropColumn('new_part_request_exported_at');
        });
    }
};
