<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentControlRegistration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'pd_distribution' => 'date', 'qa_distribution' => 'date',
            'pnp_qt_distribution' => 'date', 'ppe_pme_distribution' => 'date',
            'pd_return' => 'date', 'qa_return' => 'date', 'pnp_return' => 'date',
            'ppe_pme_return' => 'date', 'return_date' => 'date', 'crusher_date' => 'date',
        ];
    }

    public function customCells()
    {
        return $this->hasMany(DocumentControlCustomCell::class, 'registration_id');
    }
    public function project(){return $this->belongsTo(DocumentProject::class,'document_project_id');}
    public function revision(){return $this->belongsTo(DocumentRevision::class,'document_revision_id');}
    public function workflowTask(){return $this->belongsTo(ProjectWorkflowTask::class,'workflow_task_id');}
}
