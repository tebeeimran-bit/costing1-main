<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_a00_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_project_id')->unique()->constrained('document_projects')->cascadeOnDelete();
            $table->foreignId('document_revision_id')->unique()->constrained('document_revisions')->cascadeOnDelete();
            $table->string('document_number')->unique(); $table->date('document_date');
            $table->string('revision', 10)->default('00'); $table->string('from_department')->default('MKT');
            $table->string('to_department')->default('TEAM PROJECT'); $table->string('request_type')->nullable();
            $table->string('request_number')->nullable(); $table->date('request_received_date')->nullable();
            $table->string('source_file_name')->nullable(); $table->string('source_file_path')->nullable();
            $table->string('customer'); $table->string('model'); $table->string('assy_name'); $table->string('assy_number');
            $table->unsignedBigInteger('quantity')->nullable(); $table->string('quantity_uom', 20)->default('Pcs');
            $table->string('quantity_basis', 30)->default('per Year'); $table->unsignedInteger('product_life_years')->nullable();
            $table->boolean('spot_order')->default(false);
            $table->date('due_part_list')->nullable(); $table->date('due_umh')->nullable();
            $table->date('due_new_part_price')->nullable(); $table->date('due_costing')->nullable();
            $table->date('due_submit_quotation')->nullable();
            $table->date('pp1_date')->nullable(); $table->date('pp2_date')->nullable(); $table->date('pp3_date')->nullable();
            $table->date('sop_mp_date')->nullable(); $table->boolean('sop_mp_tba')->default(false);
            $table->string('issue_location')->default('Cikarang'); $table->text('notes')->nullable();
            $table->string('prepared_by')->nullable(); $table->string('acknowledged_by')->nullable(); $table->string('approved_by')->nullable();
            $table->string('status')->default('issued'); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        foreach (['admin','admin_control_project'] as $role) {
            DB::table('role_permissions')->updateOrInsert(['role'=>$role,'module'=>'control_project'], ['access'=>'full','created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('project_a00_forms');
        DB::table('role_permissions')->where('module','control_project')->delete();
    }
};
