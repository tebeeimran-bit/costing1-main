<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('project_a00_items',function(Blueprint $table){
  $table->id(); $table->foreignId('project_a00_form_id')->constrained('project_a00_forms')->cascadeOnDelete();
  $table->foreignId('document_project_id')->unique()->constrained('document_projects')->cascadeOnDelete();
  $table->foreignId('document_revision_id')->unique()->constrained('document_revisions')->cascadeOnDelete();
  $table->unsignedInteger('line_number'); $table->string('model'); $table->string('assy_number'); $table->string('assy_name');
  $table->unsignedBigInteger('quantity')->nullable(); $table->string('quantity_uom',20)->default('Pcs');
  $table->string('quantity_basis',30)->default('per Year'); $table->unsignedInteger('product_life_years')->nullable();
  $table->boolean('spot_order')->default(false); $table->timestamps();
  $table->unique(['project_a00_form_id','line_number']);
 }); }
 public function down(): void { Schema::dropIfExists('project_a00_items'); }
};
