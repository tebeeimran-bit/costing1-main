<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wire extends Model
{
    use HasFactory;

    protected $fillable = [
        'idcode',
        'item',
        'machine_maintenance',
        'fix_cost',
        'price',
    ];
}
