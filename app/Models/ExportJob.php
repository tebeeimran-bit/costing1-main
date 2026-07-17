<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportJob extends Model
{
    protected $fillable = ['user_id', 'type', 'filters', 'filename', 'path', 'status', 'row_count', 'frequency', 'scheduled_for', 'last_run_at'];

    protected $casts = ['filters' => 'array', 'scheduled_for' => 'datetime', 'last_run_at' => 'datetime'];
}
