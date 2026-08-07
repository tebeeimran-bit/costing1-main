<?php

namespace App\Support;

use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Builder;

class BusinessCategoryContext
{
    public const SESSION_KEY = 'active_business_category_id';

    public static function selected(): ?BusinessCategory
    {
        $id = (int) session(self::SESSION_KEY, 0);

        return $id > 0 ? BusinessCategory::find($id) : null;
    }

    public static function selectedId(): ?int
    {
        return self::selected()?->id;
    }

    public static function apply(Builder $query, string $projectRelation = 'project'): Builder
    {
        $category = self::selected();
        if (! $category) {
            return $query;
        }

        return $query->whereHas($projectRelation.'.product', fn (Builder $product) => $product
            ->where('code', $category->code));
    }

    public static function applyToProjects(Builder $query): Builder
    {
        $category = self::selected();
        if (! $category) {
            return $query;
        }

        return $query->whereHas('product', fn (Builder $product) => $product
            ->where('code', $category->code));
    }

    public static function resetCache(): void
    {
        // Kept for callers; context is resolved fresh from the current session.
    }
}
