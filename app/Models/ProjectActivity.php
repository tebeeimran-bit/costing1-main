<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectActivity extends Model
{
    protected $fillable = ['document_revision_id', 'user_id', 'event_type', 'title', 'description', 'metadata', 'occurred_at'];
    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];

    public function revision() { return $this->belongsTo(DocumentRevision::class, 'document_revision_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
