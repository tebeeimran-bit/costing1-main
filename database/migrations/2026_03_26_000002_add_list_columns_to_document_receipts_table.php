<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_receipts', function (Blueprint $table) {
            $table->string('cust')->nullable()->after('document_number');
            $table->string('model')->nullable()->after('cust');
            $table->string('part_number')->nullable()->after('model');
            $table->string('part_name')->nullable()->after('part_number');
            $table->date('pl_date')->nullable()->after('part_name');
            $table->date('umh_date')->nullable()->after('pl_date');
            $table->string('pic_eng')->nullable()->after('umh_date');
            $table->string('pic_mkt')->nullable()->after('pic_eng');
            $table->date('send_1_date')->nullable()->after('pic_mkt');
            $table->date('send_2_date')->nullable()->after('send_1_date');
            $table->text('keterangan')->nullable()->after('send_2_date');
        });
    }

    public function down(): void
    {
        Schema::table('document_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'cust',
                'model',
                'part_number',
                'part_name',
                'pl_date',
                'umh_date',
                'pic_eng',
                'pic_mkt',
                'send_1_date',
                'send_2_date',
                'keterangan',
            ]);
        });
    }
};
