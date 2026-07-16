<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseCycle extends Model
{
    protected $fillable = ['name', 'version', 'status', 'target_release_at', 'notes', 'created_by', 'released_at'];

    protected $casts = ['target_release_at' => 'datetime', 'released_at' => 'datetime'];

    public function checks()
    {
        return $this->hasMany(ReleaseCheck::class)->orderBy('sort_order')->orderBy('id');
    }
}
