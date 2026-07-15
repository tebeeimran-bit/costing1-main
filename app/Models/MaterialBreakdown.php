<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialBreakdown extends Model
{
    use HasFactory;

    protected $fillable = [
        'costing_data_id',
        'material_id',
        'row_no',
        'part_no',
        'id_code',
        'part_name',
        'pro_code',
        'qty_req',
        'amount1',
        'unit_price_basis',
        'unit_price_basis_text',
        'currency',
        'qty_moq',
        'cn_type',
        'import_tax_percent',
        'amount2',
        'currency2',
        'unit_price2',
    ];

    protected $casts = [
        'qty_req' => 'integer',
    ];

    public function costingData()
    {
        return $this->belongsTo(CostingData::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
