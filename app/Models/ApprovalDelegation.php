<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalDelegation extends Model
{
    protected $fillable = ['delegator_id', 'delegate_id', 'starts_at', 'ends_at', 'reason', 'is_active'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    public function delegator()
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now());
    }
}
