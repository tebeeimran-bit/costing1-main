<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->decimal('manual_cogm_value', 24, 4)->nullable()->after('cogm_import_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', fn (Blueprint $table) => $table->dropColumn('manual_cogm_value'));
    }
};
