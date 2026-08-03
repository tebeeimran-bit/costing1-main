<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_control_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('drawing_type')->nullable();
            $table->string('registration_no')->nullable()->index();
            $table->date('registration_date')->nullable();
            $table->string('customer')->nullable()->index();
            $table->string('project')->nullable()->index();
            $table->string('a00')->nullable(); $table->string('a04')->nullable(); $table->string('a05')->nullable();
            $table->string('vm')->nullable(); $table->string('years')->nullable();
            $table->string('part_number')->nullable()->index(); $table->string('part_name')->nullable();
            $table->string('revision_number')->nullable(); $table->string('revision_record')->nullable();
            $table->string('page')->nullable(); $table->text('drawing_remark')->nullable();
            $table->date('pd_distribution')->nullable(); $table->date('qa_distribution')->nullable();
            $table->date('pnp_qt_distribution')->nullable(); $table->date('ppe_pme_distribution')->nullable();
            $table->date('pd_return')->nullable(); $table->date('qa_return')->nullable();
            $table->date('pnp_return')->nullable(); $table->date('ppe_pme_return')->nullable();
            $table->text('return_remark')->nullable(); $table->date('return_date')->nullable();
            $table->text('crusher_remark')->nullable(); $table->date('crusher_date')->nullable();
            $table->string('drawing_status')->nullable()->index();
            $table->string('business_category')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach (['admin', 'document_control'] as $role) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => $role, 'module' => 'document_control'],
                ['access' => 'full', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_control_registrations');
        DB::table('role_permissions')->where('module', 'document_control')->delete();
    }
};
