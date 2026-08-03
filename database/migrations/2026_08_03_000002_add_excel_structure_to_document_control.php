<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_control_registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('row_order')->nullable()->index()->after('id');
        });
        DB::table('document_control_registrations')->orderBy('id')->eachById(function ($row) {
            DB::table('document_control_registrations')->where('id', $row->id)->update(['row_order' => $row->id * 1000]);
        });
        Schema::create('document_control_columns', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedInteger('display_order');
            $table->unsignedInteger('width')->default(140); $table->timestamps();
        });
        Schema::create('document_control_custom_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('document_control_registrations')->cascadeOnDelete();
            $table->foreignId('column_id')->constrained('document_control_columns')->cascadeOnDelete();
            $table->text('value')->nullable(); $table->timestamps();
            $table->unique(['registration_id', 'column_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('document_control_custom_cells');
        Schema::dropIfExists('document_control_columns');
        Schema::table('document_control_registrations', fn (Blueprint $table) => $table->dropColumn('row_order'));
    }
};
