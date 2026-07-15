<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable();
            $table->date('received_date');
            $table->string('partlist_original_name');
            $table->string('partlist_file_path');
            $table->string('umh_original_name');
            $table->string('umh_file_path');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_receipts');
    }
};
