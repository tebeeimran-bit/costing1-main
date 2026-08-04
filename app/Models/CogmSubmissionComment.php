<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CogmSubmissionComment extends Model
{
    protected $fillable = ['cogm_submission_id', 'user_id', 'comment'];

    public function submission()
    {
        return $this->belongsTo(CogmSubmission::class, 'cogm_submission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
