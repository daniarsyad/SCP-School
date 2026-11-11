<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_schedule_locked',
        'is_cycle_1_locked',
        'is_cycle_2_locked',
        'start_date',
        'end_date',
        'updated_by',
    ];

    protected $casts = [
        'is_schedule_locked' => 'boolean',
        'is_cycle_1_locked'  => 'boolean',
        'is_cycle_2_locked'  => 'boolean',
        'start_date'         => 'date',
        'end_date'           => 'date',
    ];

    public function isCycleLocked($cycle): bool
    {
        $cycle = (string) $cycle;
        if ($cycle === '1') {
            return (bool) ($this->is_cycle_1_locked ?? false);
        }
        if ($cycle === '2') {
            return (bool) ($this->is_cycle_2_locked ?? false);
        }

        return (bool) ($this->is_schedule_locked ?? false);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current()
{
    $config = self::first();

    if (!$config) {
        $config = self::create([
            'active_cycle'       => '1',
            'is_schedule_locked' => false,
            'is_cycle_1_locked'  => false,
            'is_cycle_2_locked'  => false,
            'start_date'         => \Carbon\Carbon::today(),
            'end_date'           => \Carbon\Carbon::today()->addDays(13),
            'updated_by'         => auth()->id(),
        ]);
    }

    return $config;
}
}