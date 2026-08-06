<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnpricedPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_revision_id',
        'costing_data_id',
        'part_number',
        'id_code',
        'part_name',
        'detected_price',
        'manual_price',
        'purchase_unit',
        'currency',
        'moq',
        'cn_type',
        'maker',
        'add_cost_percent',
        'new_part_price_imported_at',
        'new_part_price_imported_by_id',
        'resolved_at',
        'resolved_by_id',
        'resolution_source',
        'notes',
    ];

    protected $casts = [
        'detected_price' => 'decimal:4',
        'manual_price' => 'decimal:4',
        'moq' => 'decimal:6',
        'add_cost_percent' => 'decimal:4',
        'new_part_price_imported_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function revision()
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }

    public function costingData()
    {
        return $this->belongsTo(CostingData::class);
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'new_part_price_imported_by_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
