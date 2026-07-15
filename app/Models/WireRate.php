<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WireRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_month',
        'request_name',
        'jpy_rate',
        'usd_rate',
        'lme_active',
        'lme_reference',
    ];

    protected $casts = [
        'period_month' => 'date',
        'jpy_rate' => 'decimal:10',
        'usd_rate' => 'decimal:10',
        'lme_active' => 'decimal:10',
        'lme_reference' => 'decimal:1',
    ];
}
