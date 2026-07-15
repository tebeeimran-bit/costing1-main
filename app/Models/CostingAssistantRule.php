<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingAssistantRule extends Model
{
    protected $table = 'assistant_rules';

    protected $fillable = [
        'code',
        'title',
        'condition_type',
        'condition_payload',
        'severity',
        'message',
        'action_label',
        'action_url',
        'active',
    ];

    protected $casts = [
        'condition_payload' => 'array',
        'active' => 'boolean',
    ];
}
