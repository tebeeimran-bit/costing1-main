<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->foreignId('tracking_revision_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('document_revisions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tracking_revision_id');
        });
    }
};
