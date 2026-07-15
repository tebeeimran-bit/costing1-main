<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CogmSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_revision_id',
        'submitted_at',
        'pic_marketing',
        'cogm_value',
        'submitted_by',
        'notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'cogm_value' => 'decimal:2',
    ];

    public function revision()
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }
}
