<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingGroupVersionItem extends Model
{
    protected $guarded = [];
    public function version() { return $this->belongsTo(CostingGroupVersion::class, 'costing_group_version_id'); }
    public function groupItem() { return $this->belongsTo(CostingGroupItem::class, 'costing_group_item_id'); }
}
