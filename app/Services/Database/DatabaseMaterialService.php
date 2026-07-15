<?php

namespace App\Services\Database;

use App\Models\Material;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseMaterialService
{
    public function create(array $attributes): Material
    {
        return Material::create($this->normalize($attributes));
    }

    public function update(Material $material, array $attributes): Material
    {
        $material->update($this->normalize($attributes));

        return $material->refresh();
    }

    public function destroy(Material $material): void
    {
        DB::transaction(function () use ($material) {
            DB::table('material_breakdowns')->where('material_id', $material->id)->delete();
            $material->delete();
        });
    }

    public function destroyBulk(Collection $ids): int
    {
        return DB::transaction(function () use ($ids) {
            DB::table('material_breakdowns')->whereIn('material_id', $ids)->delete();

            return Material::whereIn('id', $ids)->delete();
        });
    }

    public function destroyAll(): int
    {
        return DB::transaction(function () {
            DB::table('material_breakdowns')->delete();

            return Material::query()->delete();
        });
    }

    private function normalize(array $attributes): array
    {
        $trimFields = [
            'plant',
            'material_code',
            'material_description',
            'material_type',
            'material_group',
            'base_uom',
            'purchase_unit',
            'currency',
            'cn',
            'maker',
        ];

        foreach ($trimFields as $field) {
            if (array_key_exists($field, $attributes) && is_string($attributes[$field])) {
                $attributes[$field] = trim($attributes[$field]);
            }
        }

        return $attributes;
    }
}
