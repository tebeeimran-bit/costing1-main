<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cogm_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_revision_id')->constrained('document_revisions')->cascadeOnDelete();
            $table->timestamp('submitted_at');
            $table->string('pic_marketing');
            $table->decimal('cogm_value', 20, 2)->nullable();
            $table->string('submitted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['document_revision_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cogm_submissions');
    }
};
