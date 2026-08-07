<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('costing_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_a00_form_id')->unique()->constrained('project_a00_forms')->cascadeOnDelete();
            $table->string('mode', 20)->default('normal');
            $table->string('status', 30)->default('draft')->index();
            $table->string('pic_engineering')->nullable();
            $table->string('pic_marketing')->nullable();
            $table->unsignedInteger('current_version_number')->default(0);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('costing_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_group_id')->constrained('costing_groups')->cascadeOnDelete();
            $table->foreignId('project_a00_item_id')->unique()->constrained('project_a00_items')->cascadeOnDelete();
            $table->foreignId('document_project_id')->constrained('document_projects')->restrictOnDelete();
            $table->foreignId('active_document_revision_id')->constrained('document_revisions')->restrictOnDelete();
            $table->foreignId('costing_data_id')->nullable()->constrained('costing_data')->nullOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('status', 30)->default('pending')->index();
            $table->string('pic_engineering')->nullable();
            $table->string('pic_marketing')->nullable();
            $table->decimal('quantity', 20, 4)->nullable();
            $table->string('quantity_uom', 20)->nullable();
            $table->boolean('added_after_submission')->default(false);
            $table->text('change_reason')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('removal_reason')->nullable();
            $table->timestamps();
            $table->unique(['costing_group_id', 'document_project_id']);
            $table->unique(['costing_group_id', 'sequence']);
        });

        Schema::create('costing_group_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_group_id')->constrained('costing_groups')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('type', 20);
            $table->string('status', 20)->default('generated');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_checksum', 64)->nullable();
            $table->decimal('total_unit_cogm', 24, 4)->default(0);
            $table->decimal('total_extended_cogm', 24, 4)->nullable();
            $table->boolean('has_incomplete_price')->default(false);
            $table->boolean('has_incomplete_quantity')->default(false);
            $table->text('change_summary')->nullable();
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['costing_group_id', 'version_number', 'type'], 'costing_group_version_unique');
        });

        Schema::table('costing_groups', function (Blueprint $table) {
            $table->foreignId('last_submitted_version_id')->nullable()->constrained('costing_group_versions')->nullOnDelete();
        });

        Schema::create('costing_group_version_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_group_version_id')->constrained('costing_group_versions')->cascadeOnDelete();
            $table->foreignId('costing_group_item_id')->constrained('costing_group_items')->restrictOnDelete();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->restrictOnDelete();
            $table->foreignId('costing_data_id')->nullable()->constrained('costing_data')->nullOnDelete();
            $table->unsignedInteger('item_revision_number')->default(0);
            $table->string('assy_number')->nullable();
            $table->string('assy_name')->nullable();
            $table->string('project_name')->nullable();
            $table->string('customer')->nullable();
            $table->string('model')->nullable();
            $table->string('pic_engineering')->nullable();
            $table->string('pic_marketing')->nullable();
            $table->decimal('quantity', 20, 4)->nullable();
            $table->string('quantity_uom', 20)->nullable();
            $table->decimal('material_cost', 24, 4)->default(0);
            $table->decimal('labor_cost', 24, 4)->default(0);
            $table->decimal('overhead_cost', 24, 4)->default(0);
            $table->decimal('scrap_cost', 24, 4)->default(0);
            $table->decimal('unit_cogm', 24, 4)->default(0);
            $table->decimal('extended_cogm', 24, 4)->nullable();
            $table->unsignedInteger('unpriced_part_count')->default(0);
            $table->string('change_type', 20)->default('unchanged');
            $table->text('change_reason')->nullable();
            $table->timestamps();
            $table->unique(['costing_group_version_id', 'costing_group_item_id'], 'costing_group_version_item_unique');
        });

        Schema::create('costing_group_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_group_id')->constrained('costing_groups')->cascadeOnDelete();
            $table->foreignId('costing_group_item_id')->nullable()->constrained('costing_group_items')->nullOnDelete();
            $table->foreignId('costing_group_version_id')->nullable()->constrained('costing_group_versions')->nullOnDelete();
            $table->string('event_type', 60)->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_group_events');
        Schema::dropIfExists('costing_group_version_items');
        Schema::table('costing_groups', fn (Blueprint $table) => $table->dropConstrainedForeignId('last_submitted_version_id'));
        Schema::dropIfExists('costing_group_versions');
        Schema::dropIfExists('costing_group_items');
        Schema::dropIfExists('costing_groups');
    }
};
