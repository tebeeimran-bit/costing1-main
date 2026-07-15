<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // First, add new columns if they don't exist
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'plant')) {
                $table->string('plant')->nullable()->after('id');
            }
            if (!Schema::hasColumn('materials', 'material_code')) {
                $table->string('material_code')->nullable()->after('plant');
            }
            if (!Schema::hasColumn('materials', 'material_description')) {
                $table->string('material_description')->nullable()->after('material_code');
            }
            if (!Schema::hasColumn('materials', 'material_type')) {
                $table->string('material_type')->nullable()->after('material_description');
            }
            if (!Schema::hasColumn('materials', 'material_group')) {
                $table->string('material_group')->nullable()->after('material_type');
            }
            if (!Schema::hasColumn('materials', 'base_uom')) {
                $table->string('base_uom')->default('PCS')->after('material_group');
            }
            if (!Schema::hasColumn('materials', 'price')) {
                $table->decimal('price', 20, 6)->default(0)->after('base_uom');
            }
            if (!Schema::hasColumn('materials', 'purchase_unit')) {
                $table->string('purchase_unit')->nullable()->after('price');
            }
            if (!Schema::hasColumn('materials', 'currency')) {
                $table->string('currency')->default('IDR')->after('purchase_unit');
            }
            if (!Schema::hasColumn('materials', 'moq')) {
                $table->decimal('moq', 20, 6)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('materials', 'cn')) {
                $table->string('cn')->nullable()->after('moq');
            }
            if (!Schema::hasColumn('materials', 'maker')) {
                $table->string('maker')->nullable()->after('cn');
            }
            if (!Schema::hasColumn('materials', 'add_cost_import_tax')) {
                $table->decimal('add_cost_import_tax', 5, 2)->nullable()->after('maker');
            }
            if (!Schema::hasColumn('materials', 'price_update')) {
                $table->date('price_update')->nullable()->after('add_cost_import_tax');
            }
            if (!Schema::hasColumn('materials', 'price_before')) {
                $table->decimal('price_before', 20, 6)->nullable()->after('price_update');
            }
        });

        // Migrate data from old columns to new columns if old columns exist
        if (Schema::hasColumn('materials', 'part_no') || Schema::hasColumn('materials', 'id_code')) {
            DB::table('materials')->get()->each(function ($material) {
                $updateData = [];

                if (isset($material->id_code) && !empty($material->id_code)) {
                    $updateData['material_code'] = $material->id_code;
                } elseif (isset($material->part_no) && !empty($material->part_no)) {
                    $updateData['material_code'] = $material->part_no;
                } else {
                    $updateData['material_code'] = 'MAT-' . $material->id;
                }

                if (isset($material->part_name)) {
                    $updateData['material_description'] = $material->part_name;
                }
                if (isset($material->unit)) {
                    $updateData['base_uom'] = $material->unit;
                }
                if (isset($material->supplier_name)) {
                    $updateData['maker'] = $material->supplier_name;
                }

                if (!empty($updateData)) {
                    DB::table('materials')
                        ->where('id', $material->id)
                        ->update($updateData);
                }
            });
        }

        // Drop old columns if they exist
        // Drop unique index on part_no first (SQLite compatibility)
        try {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropUnique('materials_part_no_unique');
            });
        } catch (\Exception $e) {
            // Index might not exist
        }
        Schema::table('materials', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('materials', 'part_no')) {
                $columnsToDrop[] = 'part_no';
            }
            if (Schema::hasColumn('materials', 'id_code')) {
                $columnsToDrop[] = 'id_code';
            }
            if (Schema::hasColumn('materials', 'part_name')) {
                $columnsToDrop[] = 'part_name';
            }
            if (Schema::hasColumn('materials', 'unit')) {
                $columnsToDrop[] = 'unit';
            }
            if (Schema::hasColumn('materials', 'pro_code')) {
                $columnsToDrop[] = 'pro_code';
            }
            if (Schema::hasColumn('materials', 'supplier_name')) {
                $columnsToDrop[] = 'supplier_name';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Add unique constraint on material_code if not already unique
        try {
            Schema::table('materials', function (Blueprint $table) {
                $table->unique('material_code');
            });
        } catch (\Exception $e) {
            // Unique constraint might already exist
        }
    }

    public function down(): void
    {
        // Remove unique constraint first
        try {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropUnique(['material_code']);
            });
        } catch (\Exception $e) {
            // Might not exist
        }

        // Add back old columns
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'part_no')) {
                $table->string('part_no')->nullable();
            }
            if (!Schema::hasColumn('materials', 'id_code')) {
                $table->string('id_code')->nullable();
            }
            if (!Schema::hasColumn('materials', 'part_name')) {
                $table->string('part_name')->nullable();
            }
            if (!Schema::hasColumn('materials', 'unit')) {
                $table->string('unit')->default('PCS');
            }
            if (!Schema::hasColumn('materials', 'pro_code')) {
                $table->string('pro_code')->nullable();
            }
            if (!Schema::hasColumn('materials', 'supplier_name')) {
                $table->string('supplier_name')->nullable();
            }
        });

        // Migrate data back
        DB::table('materials')->get()->each(function ($material) {
            DB::table('materials')
                ->where('id', $material->id)
                ->update([
                    'part_no' => $material->material_code ?? null,
                    'id_code' => $material->material_code ?? null,
                    'part_name' => $material->material_description ?? null,
                    'unit' => $material->base_uom ?? 'PCS',
                    'supplier_name' => $material->maker ?? null,
                ]);
        });

        // Drop new columns
        Schema::table('materials', function (Blueprint $table) {
            $columnsToDrop = [];
            $newColumns = [
                'plant',
                'material_code',
                'material_description',
                'material_type',
                'material_group',
                'base_uom',
                'price',
                'purchase_unit',
                'currency',
                'moq',
                'cn',
                'maker',
                'add_cost_import_tax',
                'price_update',
                'price_before'
            ];
            foreach ($newColumns as $col) {
                if (Schema::hasColumn('materials', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
