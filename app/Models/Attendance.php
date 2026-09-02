<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'journal_id',
        'student_id',
        'status',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}