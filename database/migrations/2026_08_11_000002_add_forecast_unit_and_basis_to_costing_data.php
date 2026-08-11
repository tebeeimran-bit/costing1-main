<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            if (!Schema::hasColumn('costing_data', 'forecast_uom')) {
                $table->string('forecast_uom', 50)->default('PCE');
            }
            if (!Schema::hasColumn('costing_data', 'forecast_basis')) {
                $table->string('forecast_basis', 20)->default('per_month');
            }
        });

        // Pulihkan item A00 lama dari forecast costing terbaru. Sebelum kolom
        // basis tersedia, form costing selalu menampilkan default Per Bulan.
        $items = DB::table('project_a00_items')->get();
        foreach ($items as $item) {
            $costing = DB::table('costing_data')
                ->where('tracking_revision_id', $item->document_revision_id)
                ->latest('id')
                ->first();

            if (!$costing || $costing->forecast === null) {
                continue;
            }

            DB::table('project_a00_items')->where('id', $item->id)->update([
                'quantity' => $costing->forecast,
                'quantity_uom' => 'Pcs',
                'quantity_basis' => 'per Month',
                'updated_at' => now(),
            ]);

            if ((int) $item->line_number === 1) {
                DB::table('project_a00_forms')->where('id', $item->project_a00_form_id)->update([
                    'quantity' => $costing->forecast,
                    'quantity_uom' => 'Pcs',
                    'quantity_basis' => 'per Month',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('costing_data', function (Blueprint $table) {
            $columns = array_values(array_filter(['forecast_uom', 'forecast_basis'],
                fn (string $column) => Schema::hasColumn('costing_data', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
