<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRun extends Model
{
    protected $fillable = ['user_id', 'costing_data_id', 'document_revision_id', 'type', 'original_name', 'status', 'before_snapshot', 'after_summary', 'rolled_back_at', 'rolled_back_by'];

    protected $casts = ['before_snapshot' => 'array', 'after_summary' => 'array', 'rolled_back_at' => 'datetime'];
}
