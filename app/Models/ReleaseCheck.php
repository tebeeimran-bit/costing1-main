<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseCheck extends Model
{
    protected $fillable = ['release_cycle_id', 'category', 'title', 'description', 'status', 'tester_id', 'tested_at', 'notes', 'sort_order'];

    protected $casts = ['tested_at' => 'datetime'];

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }
}
