<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRevision extends Model
{
    use HasFactory;

    public const STATUS_PENDING_FORM_INPUT = 'pending_form_input';
    public const STATUS_SUDAH_COSTING = 'sudah_costing';
    public const STATUS_PENDING_PRICING = 'pending_pricing';
    public const STATUS_COGM_GENERATED = 'cogm_generated';
    public const STATUS_WAITING_COORDINATOR_APPROVAL = 'waiting_coordinator_approval';
    public const STATUS_APPROVED_BY_COORDINATOR = 'approved_by_coordinator';
    public const STATUS_REJECTED_BY_COORDINATOR = 'rejected_by_coordinator';
    public const STATUS_SUBMITTED_TO_MARKETING = 'submitted_to_marketing';
    public const STATUS_A00_ISSUED = 'a00_issued_waiting_decision';

    protected $fillable = [
        'document_project_id',
        'version_number',
        'received_date',
        'plant_id',
        'period',
        'pic_engineering',
        'status',
        'cogm_generated_at',
        'pic_marketing',
        'a00',
        'a00_received_date',
        'a00_document_original_name',
        'a00_document_file_path',
        'a04',
        'a04_received_date',
        'a04_document_original_name',
        'a04_document_file_path',
        'a05',
        'a05_received_date',
        'a05_document_original_name',
        'a05_document_file_path',
        'partlist_original_name',
        'partlist_file_path',
        'partlist_update_count',
        'partlist_updated_at',
        'umh_original_name',
        'umh_file_path',
        'umh_update_count',
        'umh_updated_at',
        'new_part_request_exported_at',
        'new_part_request_exported_by_id',
        'costing_edit_original_name',
        'costing_edit_file_path',
        'costing_edit_uploaded_at',
        'cogm_import_original_name',
        'cogm_import_file_path',
        'cogm_import_uploaded_at',
        'notes',
        'change_remark',
    ];

    protected $casts = [
        'received_date' => 'date',
        'cogm_generated_at' => 'datetime',
        'a00_received_date' => 'date',
        'a04_received_date' => 'date',
        'a05_received_date' => 'date',
        'partlist_updated_at' => 'datetime',
        'umh_updated_at' => 'datetime',
        'new_part_request_exported_at' => 'datetime',
        'costing_edit_uploaded_at' => 'datetime',
        'cogm_import_uploaded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(DocumentProject::class, 'document_project_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function cogmSubmissions()
    {
        return $this->hasMany(CogmSubmission::class);
    }

    public function latestSubmission()
    {
        return $this->hasOne(CogmSubmission::class)->latestOfMany('submitted_at');
    }

    public function costingApprovals()
    {
        return $this->hasMany(CostingApproval::class);
    }

    public function latestApproval()
    {
        return $this->hasOne(CostingApproval::class)->latestOfMany();
    }

    public function unpricedParts()
    {
        return $this->hasMany(UnpricedPart::class, 'document_revision_id');
    }

    public function costingData()
    {
        return $this->hasOne(CostingData::class, 'tracking_revision_id')->latestOfMany();
    }

    public function workflowTasks(){return $this->hasMany(ProjectWorkflowTask::class,'document_revision_id');}

    public function latestCostingRevision()
    {
        return $this->hasOne(ProjectDocumentRevision::class, 'document_revision_id')
            ->whereIn('revision_type', ['price', 'partlist', 'umh'])
            ->latestOfMany();
    }

    public function getVersionLabelAttribute(): string
    {
        $displayVersion = max(0, (int) $this->version_number - 1);
        return 'V' . $displayVersion;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_FORM_INPUT => 'Pending Form Costing',
            self::STATUS_SUDAH_COSTING => 'Sudah Costing',
            self::STATUS_PENDING_PRICING => 'Draft / Pending Pricing',
            self::STATUS_COGM_GENERATED => 'COGM Generated',
            self::STATUS_WAITING_COORDINATOR_APPROVAL => 'Waiting Approval Coordinator',
            self::STATUS_APPROVED_BY_COORDINATOR => 'Approved by Coordinator',
            self::STATUS_REJECTED_BY_COORDINATOR => 'Rejected by Coordinator',
            self::STATUS_SUBMITTED_TO_MARKETING => 'Submitted to Marketing',
            self::STATUS_A00_ISSUED => 'A00 Issued / Waiting Decision',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
