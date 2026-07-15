<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tube extends Model
{
    use HasFactory;

    protected $fillable = [
        'tube_code',
        'tube_name',
        'spec',
        'material_type',
        'diameter',
        'thickness',
        'length',
        'unit',
        'price',
        'price_unit',
        'currency',
        'supplier',
        'effective_date',
        'is_estimate',
        'notes',
    ];

    protected $casts = [
        'diameter' => 'decimal:4',
        'thickness' => 'decimal:4',
        'length' => 'decimal:4',
        'price' => 'decimal:4',
        'effective_date' => 'date',
        'is_estimate' => 'boolean',
    ];
}
