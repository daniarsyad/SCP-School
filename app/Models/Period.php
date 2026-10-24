<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'period_number',
        'label',
        'start_time',
        'end_time',
        'is_break',
        'day_group',
    ];

    protected $casts = [
        'is_break' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}