<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('document_revisions', fn (Blueprint $table) => $table->uuid('project_bundle_key')->nullable()->index());
    }
    public function down(): void {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropIndex(['project_bundle_key']);
            $table->dropColumn('project_bundle_key');
        });
    }
};
