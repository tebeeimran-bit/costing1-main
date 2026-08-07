<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostingGroup extends Model
{
    public const MODE_NORMAL = 'normal';
    public const MODE_BULKY = 'bulky';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_APPROVAL = 'waiting_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVISION = 'under_revision';

    protected $guarded = [];

    public function a00Form() { return $this->belongsTo(ProjectA00Form::class, 'project_a00_form_id'); }
    public function items() { return $this->hasMany(CostingGroupItem::class)->orderBy('sequence'); }
    public function activeItems() { return $this->items()->whereNull('removed_at'); }
    public function versions() { return $this->hasMany(CostingGroupVersion::class); }
    public function lastSubmittedVersion() { return $this->belongsTo(CostingGroupVersion::class, 'last_submitted_version_id'); }
    public function events() { return $this->hasMany(CostingGroupEvent::class)->latest('created_at'); }
}
