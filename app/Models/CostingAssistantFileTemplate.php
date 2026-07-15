<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingAssistantFileTemplate extends Model
{
    protected $table = 'assistant_file_templates';

    protected $fillable = [
        'type',
        'name',
        'required_columns',
        'optional_columns',
        'validation_rules',
        'active',
    ];

    protected $casts = [
        'required_columns' => 'array',
        'optional_columns' => 'array',
        'validation_rules' => 'array',
        'active' => 'boolean',
    ];
}
