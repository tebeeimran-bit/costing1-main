<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_revisions', 'a04_reason')) {
            Schema::table('document_revisions', function (Blueprint $table) {
                $table->text('a04_reason')->nullable()->after('a04_received_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_revisions', 'a04_reason')) {
            Schema::table('document_revisions', function (Blueprint $table) {
                $table->dropColumn('a04_reason');
            });
        }
    }
};
