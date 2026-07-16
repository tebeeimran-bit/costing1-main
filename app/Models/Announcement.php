<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'level', 'audiences', 'is_active', 'starts_at', 'ends_at', 'created_by'];

    protected $casts = ['audiences' => 'array', 'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function scopeVisibleTo($query, ?User $user)
    {
        return $query->where('is_active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->where(fn ($q) => $q->whereNull('audiences')->orWhereJsonContains('audiences', $user?->role));
    }
}
