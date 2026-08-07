<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingGroupItem extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['quantity'=>'decimal:4','added_after_submission'=>'boolean','removed_at'=>'datetime']; }
    public function group() { return $this->belongsTo(CostingGroup::class, 'costing_group_id'); }
    public function a00Item() { return $this->belongsTo(ProjectA00Item::class, 'project_a00_item_id'); }
    public function project() { return $this->belongsTo(DocumentProject::class, 'document_project_id'); }
    public function revision() { return $this->belongsTo(DocumentRevision::class, 'active_document_revision_id'); }
    public function costingData() { return $this->belongsTo(CostingData::class); }
    public function effectivePicEngineering(): ?string { return $this->pic_engineering ?: $this->group?->pic_engineering; }
    public function effectivePicMarketing(): ?string { return $this->pic_marketing ?: $this->group?->pic_marketing; }
}
