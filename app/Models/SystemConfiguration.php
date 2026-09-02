<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_schedule_locked',
        'start_date',
        'end_date',
        'updated_by',
    ];

    protected $casts = [
        'is_schedule_locked' => 'boolean',
        'start_date'         => 'date',
        'end_date'           => 'date',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current()
    {
        return self::first();
    }
}