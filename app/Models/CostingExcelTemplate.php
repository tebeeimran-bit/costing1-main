<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingExcelTemplate extends Model
{
    public const TYPES = [
        'costing' => 'Template Costing',
        'partlist' => 'Template Partlist',
        'umh' => 'Template UMH',
        'a00' => 'Template A00',
    ];

    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
