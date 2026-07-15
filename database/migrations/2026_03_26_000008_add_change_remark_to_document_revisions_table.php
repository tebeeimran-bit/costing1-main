<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->text('change_remark')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn('change_remark');
        });
    }
};
