<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaSnapshot extends Model
{
    protected $fillable = ['snapshot_date', 'document_revision_id', 'stage', 'pic', 'due_at', 'is_overdue', 'aging_days', 'compliance'];

    protected $casts = ['snapshot_date' => 'date', 'due_at' => 'datetime', 'is_overdue' => 'boolean'];
}
