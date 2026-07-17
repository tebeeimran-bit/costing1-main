<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemEvent extends Model
{
    protected $fillable = ['type', 'severity', 'route', 'method', 'status_code', 'duration_ms', 'memory_kb', 'user_id', 'message', 'context', 'occurred_at'];

    protected $casts = ['context' => 'array', 'occurred_at' => 'datetime'];
}
