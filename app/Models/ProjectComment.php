<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectComment extends Model
{
    protected $fillable = ['document_revision_id', 'user_id', 'body', 'mentioned_user_ids'];
    protected $casts = ['mentioned_user_ids' => 'array'];

    public function revision() { return $this->belongsTo(DocumentRevision::class, 'document_revision_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
