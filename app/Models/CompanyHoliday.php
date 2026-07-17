<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyHoliday extends Model
{
    protected $fillable = ['holiday_date', 'name', 'is_active', 'created_by'];

    protected $casts = ['holiday_date' => 'date', 'is_active' => 'boolean'];
}
