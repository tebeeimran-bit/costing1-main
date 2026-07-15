<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TubeBreakdown extends Model
{
    use HasFactory;

    protected $fillable = [
        'costing_data_id',
        'tube_id',
        'tube_code',
        'tube_name',
        'spec',
        'usage_qty',
        'usage_unit',
        'price',
        'price_unit',
        'amount',
        'is_estimate',
        'notes',
    ];

    protected $casts = [
        'usage_qty' => 'decimal:4',
        'price' => 'decimal:4',
        'amount' => 'decimal:4',
        'is_estimate' => 'boolean',
    ];

    public function costingData()
    {
        return $this->belongsTo(CostingData::class);
    }

    public function tube()
    {
        return $this->belongsTo(Tube::class);
    }
}
