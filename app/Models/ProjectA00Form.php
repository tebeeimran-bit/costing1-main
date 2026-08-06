<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProjectA00Form extends Model
{
    protected $guarded=[];
    protected function casts(): array { return [
        'document_date'=>'date','request_received_date'=>'date','due_part_list'=>'date','due_umh'=>'date',
        'due_new_part_price'=>'date','due_costing'=>'date','due_submit_quotation'=>'date','pp1_date'=>'date',
        'pp2_date'=>'date','pp3_date'=>'date','sop_mp_date'=>'date','spot_order'=>'boolean','sop_mp_tba'=>'boolean',
        'issued_at'=>'datetime',
    ]; }
    public function project(){return $this->belongsTo(DocumentProject::class,'document_project_id');}
    public function projectRevision(){return $this->belongsTo(DocumentRevision::class,'document_revision_id');}
    public function items(){return $this->hasMany(ProjectA00Item::class)->orderBy('line_number');}
    public function creator(){return $this->belongsTo(User::class,'created_by');}
}
