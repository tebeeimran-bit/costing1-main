<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->index('period');
            $table->index('model');
        });

        Schema::table('document_revisions', function (Blueprint $table) {
            $table->index('received_date');
        });
    }

    public function down(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $table->dropIndex(['period']);
            $table->dropIndex(['model']);
        });

        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropIndex(['received_date']);
        });
    }
};
