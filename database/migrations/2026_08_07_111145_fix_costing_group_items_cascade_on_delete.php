<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // costing_group_version_items: change document_revision_id and costing_group_item_id to cascadeOnDelete
        Schema::table('costing_group_version_items', function (Blueprint $table) {
            $table->dropForeign(['costing_group_item_id']);
            $table->dropForeign(['document_revision_id']);
            $table->foreign('costing_group_item_id')->references('id')->on('costing_group_items')->cascadeOnDelete();
            $table->foreign('document_revision_id')->references('id')->on('document_revisions')->cascadeOnDelete();
        });

        // costing_group_items: change active_document_revision_id to cascadeOnDelete
        Schema::table('costing_group_items', function (Blueprint $table) {
            $table->dropForeign(['active_document_revision_id']);
            $table->foreign('active_document_revision_id')->references('id')->on('document_revisions')->cascadeOnDelete();
        });

        // costing_group_versions: change costing_group_id to cascadeOnDelete
        Schema::table('costing_group_versions', function (Blueprint $table) {
            $table->dropForeign(['costing_group_id']);
            $table->foreign('costing_group_id')->references('id')->on('costing_groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('costing_group_versions', function (Blueprint $table) {
            $table->dropForeign(['costing_group_id']);
            $table->foreign('costing_group_id')->references('id')->on('costing_groups')->restrictOnDelete();
        });

        Schema::table('costing_group_items', function (Blueprint $table) {
            $table->dropForeign(['active_document_revision_id']);
            $table->foreign('active_document_revision_id')->references('id')->on('document_revisions')->restrictOnDelete();
        });

        Schema::table('costing_group_version_items', function (Blueprint $table) {
            $table->dropForeign(['costing_group_item_id']);
            $table->dropForeign(['document_revision_id']);
            $table->foreign('costing_group_item_id')->references('id')->on('costing_group_items')->restrictOnDelete();
            $table->foreign('document_revision_id')->references('id')->on('document_revisions')->restrictOnDelete();
        });
    }
};
