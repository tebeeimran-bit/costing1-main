<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->string('pricing_status', 30)->nullable()->after('status');
            $table->unsignedInteger('manual_missing_price_count')->nullable()->after('pricing_status');
            $table->text('pricing_status_note')->nullable()->after('manual_missing_price_count');
            $table->timestamp('pricing_status_updated_at')->nullable()->after('pricing_status_note');
        });
    }

    public function down(): void
    {
        Schema::table('document_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_status', 'manual_missing_price_count',
                'pricing_status_note', 'pricing_status_updated_at',
            ]);
        });
    }
};
