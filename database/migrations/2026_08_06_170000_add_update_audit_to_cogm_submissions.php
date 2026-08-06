<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cogm_submissions', function (Blueprint $table) {
            $table->unsignedInteger('update_count')->default(0);
            $table->string('last_updated_by')->nullable();
            $table->timestamp('last_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cogm_submissions', function (Blueprint $table) {
            $table->dropColumn(['update_count', 'last_updated_by', 'last_updated_at']);
        });
    }
};
