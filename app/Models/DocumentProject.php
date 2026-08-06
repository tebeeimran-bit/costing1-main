<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer',
        'model',
        'part_number',
        'part_name',
        'project_key',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function revisions()
    {
        return $this->hasMany(DocumentRevision::class);
    }

    public function a00Form()
    {
        return $this->hasOne(ProjectA00Form::class);
    }

    public function a00Item()
    {
        return $this->hasOne(ProjectA00Item::class, 'document_project_id');
    }

    public function workflowTasks(){return $this->hasMany(ProjectWorkflowTask::class,'document_project_id');}
}
