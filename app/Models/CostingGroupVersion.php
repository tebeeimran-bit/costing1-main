<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingGroupVersion extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['generated_at'=>'datetime','submitted_at'=>'datetime','has_incomplete_price'=>'boolean','has_incomplete_quantity'=>'boolean']; }
    public function group() { return $this->belongsTo(CostingGroup::class, 'costing_group_id'); }
    public function items() { return $this->hasMany(CostingGroupVersionItem::class); }
}
