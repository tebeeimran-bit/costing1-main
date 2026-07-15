<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'period_date', 'usd_to_idr', 'jpy_to_idr', 'lme_copper', 'source', 'notes',
    ];

    protected $casts = [
        'period_date' => 'date',
        'usd_to_idr' => 'decimal:2',
        'jpy_to_idr' => 'decimal:5',
        'lme_copper' => 'decimal:2',
    ];
}
