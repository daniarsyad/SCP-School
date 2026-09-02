<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'student_id',
        'applicant_id',
        'verified_by',
        'start_date',
        'end_date',
        'reason',
        'document_path',
        'status_approval',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}