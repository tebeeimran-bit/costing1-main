<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostingApproval extends Model
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting_coordinator_approval';
    public const STATUS_APPROVED = 'approved_by_coordinator';
    public const STATUS_REJECTED = 'rejected_by_coordinator';
    public const STATUS_SUBMITTED_TO_MARKETING = 'submitted_to_marketing';

    protected $fillable = [
        'document_revision_id',
        'costing_data_id',
        'status',
        'cogm_value',
        'submitted_by_id',
        'approved_by_id',
        'rejected_by_id',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'submit_notes',
        'approval_notes',
        'rejection_notes',
    ];

    protected $casts = [
        'cogm_value' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function revision()
    {
        return $this->belongsTo(DocumentRevision::class, 'document_revision_id');
    }

    public function costingData()
    {
        return $this->belongsTo(CostingData::class, 'costing_data_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING => 'Waiting Approval',
            self::STATUS_APPROVED => 'Approved by Coordinator',
            self::STATUS_REJECTED => 'Rejected by Coordinator',
            self::STATUS_SUBMITTED_TO_MARKETING => 'Submitted to Marketing',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}