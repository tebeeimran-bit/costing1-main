<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingGroupEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array { return ['metadata'=>'array','created_at'=>'datetime']; }
    public function group() { return $this->belongsTo(CostingGroup::class, 'costing_group_id'); }
}
