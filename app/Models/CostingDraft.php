<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingDraft extends Model
{
    protected $fillable = [
        'user_id',
        'tracking_revision_id',
        'costing_data_id',
        'draft_key',
        'payload',
        'saved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'saved_at' => 'datetime',
    ];
}
