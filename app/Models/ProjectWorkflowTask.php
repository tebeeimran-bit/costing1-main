<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectWorkflowTask extends Model
{
    public const STAGE_DRAWING = 'drawing';
    public const STAGE_BREAKDOWN = 'breakdown';
    public const STAGE_COSTING = 'costing';
    public const STAGE_COGM = 'cogm';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $guarded = [];
    protected function casts(): array { return ['available_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','metadata'=>'array']; }
    public function project(){return $this->belongsTo(DocumentProject::class,'document_project_id');}
    public function revision(){return $this->belongsTo(DocumentRevision::class,'document_revision_id');}
    public function assignedUser(){return $this->belongsTo(User::class,'assigned_user_id');}
    public function completedBy(){return $this->belongsTo(User::class,'completed_by_id');}
}
