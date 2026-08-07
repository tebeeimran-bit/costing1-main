<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->enum('revision_type', ['design', 'partlist', 'drawing', 'umh', 'price'])->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->enum('revision_type', ['design', 'partlist', 'drawing', 'umh'])->change();
        });
    }
};
