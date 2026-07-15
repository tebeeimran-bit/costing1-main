<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_name', 'action', 'module', 'target', 'description', 'ip_address',
    ];
}
