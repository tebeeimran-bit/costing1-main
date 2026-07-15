<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('a00')->nullable()->after('pic_marketing');
            $table->string('a05')->nullable()->after('a00');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn(['a00', 'a05']);
        });
    }
};
