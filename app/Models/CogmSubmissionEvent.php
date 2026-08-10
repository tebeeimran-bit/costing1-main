<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CogmSubmissionEvent extends Model
{
    protected $fillable = ['cogm_submission_id','user_id','event_type','source','title','description','cogm_value'];
    protected $casts = ['cogm_value' => 'decimal:4'];
    public function submission(){ return $this->belongsTo(CogmSubmission::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
