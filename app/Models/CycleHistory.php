<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CycleHistory extends Model
{
    protected $fillable = [
        'cycle_type',
        'start_date',
        'end_date',
        'total_journals',
        'total_attendances',
        'teachers',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'teachers'   => 'array',
    ];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}