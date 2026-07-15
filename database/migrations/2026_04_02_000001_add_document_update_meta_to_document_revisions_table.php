<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->unsignedInteger('partlist_update_count')->default(0)->after('partlist_file_path');
            $table->timestamp('partlist_updated_at')->nullable()->after('partlist_update_count');
            $table->unsignedInteger('umh_update_count')->default(0)->after('umh_file_path');
            $table->timestamp('umh_updated_at')->nullable()->after('umh_update_count');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'partlist_update_count',
                'partlist_updated_at',
                'umh_update_count',
                'umh_updated_at',
            ]);
        });
    }
};