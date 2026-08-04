<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->foreignId('plant_id')->nullable()->after('received_date')->constrained('plants')->nullOnDelete();
            $table->string('period', 7)->nullable()->after('plant_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plant_id');
            $table->dropColumn('period');
        });
    }
};
