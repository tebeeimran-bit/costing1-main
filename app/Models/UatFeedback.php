<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UatFeedback extends Model
{
    protected $table = 'uat_feedback';

    protected $fillable = ['user_id', 'category', 'severity', 'title', 'description', 'page_url', 'route_name', 'browser', 'screenshot_path', 'status', 'resolution_notes', 'resolved_by_id', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
