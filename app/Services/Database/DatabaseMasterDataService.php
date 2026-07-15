<?php

namespace App\Services\Database;

use Closure;
use Illuminate\Database\Eloquent\Model;

class DatabaseMasterDataService
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function create(string $modelClass, array $attributes, array $trimFields = []): Model
    {
        return $modelClass::create($this->normalizeAttributes($attributes, $trimFields));
    }

    public function update(Model $model, array $attributes, array $trimFields = []): Model
    {
        $model->update($this->normalizeAttributes($attributes, $trimFields));

        return $model->refresh();
    }

    public function delete(Model $model, ?Closure $guard = null): void
    {
        if ($guard !== null) {
            $guard($model);
        }

        $model->delete();
    }

    private function normalizeAttributes(array $attributes, array $trimFields): array
    {
        foreach ($trimFields as $field) {
            if (array_key_exists($field, $attributes) && is_string($attributes[$field])) {
                $attributes[$field] = trim($attributes[$field]);
            }
        }

        return $attributes;
    }
}
