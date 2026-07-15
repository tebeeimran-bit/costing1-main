<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            if (!Schema::hasColumn('document_revisions', 'partlist')) {
                $table->string('partlist')->default('belum_ada')->after('a05_document_original_name');
            }

            if (!Schema::hasColumn('document_revisions', 'partlist_received_date')) {
                $table->date('partlist_received_date')->nullable()->after('partlist');
            }

            if (!Schema::hasColumn('document_revisions', 'partlist_file_path')) {
                $table->string('partlist_file_path')->nullable()->after('partlist_received_date');
            }

            if (!Schema::hasColumn('document_revisions', 'partlist_original_name')) {
                $table->string('partlist_original_name')->nullable()->after('partlist_file_path');
            }

            if (!Schema::hasColumn('document_revisions', 'partlist_revision_count')) {
                $table->unsignedInteger('partlist_revision_count')->default(0)->after('partlist_original_name');
            }

            if (!Schema::hasColumn('document_revisions', 'umh')) {
                $table->string('umh')->default('belum_ada')->after('partlist_revision_count');
            }

            if (!Schema::hasColumn('document_revisions', 'umh_received_date')) {
                $table->date('umh_received_date')->nullable()->after('umh');
            }

            if (!Schema::hasColumn('document_revisions', 'umh_file_path')) {
                $table->string('umh_file_path')->nullable()->after('umh_received_date');
            }

            if (!Schema::hasColumn('document_revisions', 'umh_original_name')) {
                $table->string('umh_original_name')->nullable()->after('umh_file_path');
            }

            if (!Schema::hasColumn('document_revisions', 'umh_revision_count')) {
                $table->unsignedInteger('umh_revision_count')->default(0)->after('umh_original_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            foreach ([
                'umh_revision_count',
                'umh_original_name',
                'umh_file_path',
                'umh_received_date',
                'umh',
                'partlist_revision_count',
                'partlist_original_name',
                'partlist_file_path',
                'partlist_received_date',
                'partlist',
            ] as $column) {
                if (Schema::hasColumn('document_revisions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
