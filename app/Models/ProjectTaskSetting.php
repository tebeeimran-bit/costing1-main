<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskSetting extends Model
{
    protected $fillable = ['document_revision_id', 'due_at', 'workflow_stage', 'stage_entered_at', 'set_by_id'];

    protected $casts = ['due_at' => 'datetime', 'stage_entered_at' => 'datetime'];

    public function revision()
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by_id');
    }
}
