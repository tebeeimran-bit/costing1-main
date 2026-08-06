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
        'update_count',
        'last_updated_by',
        'last_updated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'cogm_value' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    public function revision()
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }

    public function comments()
    {
        return $this->hasMany(CogmSubmissionComment::class)->latest();
    }
}
