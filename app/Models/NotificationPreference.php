<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    public const TYPES = ['document', 'project', 'pricing', 'mention', 'sla'];

    protected $fillable = ['user_id', 'enabled_types'];

    protected $casts = ['enabled_types' => 'array'];
}
