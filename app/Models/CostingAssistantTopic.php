<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingAssistantTopic extends Model
{
    protected $table = 'assistant_topics';

    protected $fillable = [
        'menu',
        'title',
        'content',
        'role',
        'keywords',
        'active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'active' => 'boolean',
    ];
}
