<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_backups', function (Blueprint $table) {
            $table->string('database_driver', 20)->default('sqlite')->after('created_by');
            $table->index(['database_driver', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('system_backups', function (Blueprint $table) {
            $table->dropIndex(['database_driver', 'status']);
            $table->dropColumn('database_driver');
        });
    }
};
