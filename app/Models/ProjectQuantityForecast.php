<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectQuantityForecast extends Model
{
    protected $fillable = [
        'document_revision_id',
        'period_type',
        'year_number',
        'calendar_year',
        'month_number',
        'quantity',
        'uom',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    public function revision()
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }
}
