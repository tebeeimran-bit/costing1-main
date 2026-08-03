<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_a00_forms', function (Blueprint $table) {
            $table->string('prepared_signature_path')->nullable()->after('prepared_by');
            $table->string('acknowledged_signature_path')->nullable()->after('acknowledged_by');
            $table->string('approved_signature_path')->nullable()->after('approved_by');
            $table->timestamp('issued_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('project_a00_forms', function (Blueprint $table) {
            $table->dropColumn([
                'prepared_signature_path',
                'acknowledged_signature_path',
                'approved_signature_path',
                'issued_at',
            ]);
        });
    }
};
