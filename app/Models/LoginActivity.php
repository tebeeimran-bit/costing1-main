<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'identifier', 'ip_address', 'user_agent', 'successful', 'occurred_at'];

    protected $casts = ['successful' => 'boolean', 'occurred_at' => 'datetime'];
}
