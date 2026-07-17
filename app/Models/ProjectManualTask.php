<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectManualTask extends Model
{
    protected $fillable = [
        'document_project_id', 'assignee_id', 'created_by_id', 'depends_on_task_id', 'title', 'description',
        'category', 'priority', 'progress', 'status', 'due_at', 'recurrence',
    ];

    protected $casts = ['due_at' => 'date'];

    public function project()
    {
        return $this->belongsTo(DocumentProject::class, 'document_project_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function dependency()
    {
        return $this->belongsTo(self::class, 'depends_on_task_id');
    }
}
