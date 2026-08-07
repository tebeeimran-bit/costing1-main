<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_document_revisions MODIFY revision_type ENUM('design','partlist','drawing','umh','price') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_document_revisions MODIFY revision_type ENUM('design','partlist','drawing','umh') NOT NULL");
        }
    }
};
