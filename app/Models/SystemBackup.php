<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemBackup extends Model
{
    protected $fillable = ['created_by', 'database_driver', 'filename', 'path', 'size_bytes', 'status', 'checksum', 'notes', 'verified_at'];

    protected $casts = ['verified_at' => 'datetime'];
}
