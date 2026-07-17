<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationState extends Model
{
    protected $fillable = ['user_id', 'notification_key', 'read_at', 'dismissed_at'];
    protected $casts = ['read_at' => 'datetime', 'dismissed_at' => 'datetime'];
}
