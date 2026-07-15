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
        'part_name',
        'detected_price',
        'manual_price',
        'resolved_at',
        'resolution_source',
        'notes',
    ];

    protected $casts = [
        'detected_price' => 'decimal:4',
        'manual_price' => 'decimal:4',
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
}
