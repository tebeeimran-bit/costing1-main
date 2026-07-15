<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostingData extends Model
{
    use HasFactory;

    protected $table = 'costing_data';

    protected $fillable = [
        'product_id',
        'customer_id',
        'tracking_revision_id',
        'period',
        'line',
        'wo_number',
        'model',
        'assy_no',
        'assy_name',
        'exchange_rate_usd',
        'exchange_rate_jpy',
        'lme_rate',
        'rate_periode',
        'forecast',
        'project_period',
        'material_cost',
        'labor_cost',
        'overhead_cost',
        'scrap_cost',
        'revenue',
        'qty_good',
        'cycle_times',
        'costing_resume_overrides',
    ];

    protected $casts = [
        'exchange_rate_usd' => 'decimal:2',
        'exchange_rate_jpy' => 'decimal:2',
        'lme_rate' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'scrap_cost' => 'decimal:2',
        'revenue' => 'decimal:2',
        'cycle_times' => 'array',
        'costing_resume_overrides' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function materialBreakdowns()
    {
        return $this->hasMany(MaterialBreakdown::class);
    }

    public function trackingRevision()
    {
        return $this->belongsTo(DocumentRevision::class, 'tracking_revision_id');
    }

    public function unpricedParts()
    {
        return $this->hasMany(UnpricedPart::class);
    }

    public function approvals()
    {
        return $this->hasMany(CostingApproval::class);
    }

    public function getTotalCostAttribute()
    {
        return $this->material_cost + $this->labor_cost + $this->overhead_cost + $this->scrap_cost;
    }

    public function getCostPerUnitAttribute()
    {
        if ($this->qty_good <= 0)
            return 0;
        return $this->total_cost / $this->qty_good;
    }

    public function getMarginAttribute()
    {
        if ($this->revenue <= 0)
            return 0;
        return (($this->revenue - $this->total_cost) / $this->revenue) * 100;
    }
}
