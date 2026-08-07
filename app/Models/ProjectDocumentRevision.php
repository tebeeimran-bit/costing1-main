<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDocumentRevision extends Model
{
    protected $guarded = [];

    public function project(){return $this->belongsTo(DocumentProject::class,'document_project_id');}
    public function revision(){return $this->belongsTo(DocumentRevision::class,'document_revision_id');}
    public function workflowTask(){return $this->belongsTo(ProjectWorkflowTask::class,'workflow_task_id');}
    public function uploader(){return $this->belongsTo(User::class,'uploaded_by');}

    public function getTypeLabelAttribute(): string
    {
        return match($this->revision_type){
            'design'=>'Revisi Design','partlist'=>'Revisi Partlist',
            'drawing'=>'Revisi Drawing','umh'=>'Revisi UMH','price'=>'Update Harga',
            default=>ucfirst((string)$this->revision_type),
        };
    }
}
