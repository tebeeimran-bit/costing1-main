<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProjectA00Item extends Model
{
 protected $guarded=[];
 protected function casts(): array{return ['spot_order'=>'boolean'];}
 public function form(){return $this->belongsTo(ProjectA00Form::class,'project_a00_form_id');}
 public function project(){return $this->belongsTo(DocumentProject::class,'document_project_id');}
 public function projectRevision(){return $this->belongsTo(DocumentRevision::class,'document_revision_id');}
 public function quantityForecasts(){return $this->hasMany(ProjectQuantityForecast::class,'document_revision_id','document_revision_id')->orderBy('year_number')->orderBy('month_number');}
 public function costingGroupItem(){return $this->hasOne(CostingGroupItem::class,'project_a00_item_id');}
}
