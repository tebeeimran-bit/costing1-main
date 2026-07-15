<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'cust',
        'model',
        'part_number',
        'part_name',
        'pl_date',
        'umh_date',
        'pic_eng',
        'pic_mkt',
        'send_1_date',
        'send_2_date',
        'keterangan',
        'received_date',
        'partlist_original_name',
        'partlist_file_path',
        'umh_original_name',
        'umh_file_path',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
        'pl_date' => 'date',
        'umh_date' => 'date',
        'send_1_date' => 'date',
        'send_2_date' => 'date',
    ];
}
