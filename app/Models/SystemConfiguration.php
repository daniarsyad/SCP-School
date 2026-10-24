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
    $config = self::first();

    if (!$config) {
        $config = self::create([
            'active_cycle'       => '1',
            'is_schedule_locked' => false,
            'start_date'         => \Carbon\Carbon::today(),
            'end_date'           => \Carbon\Carbon::today()->addDays(13),
            'updated_by'         => auth()->id(),
        ]);
    }

    return $config;
}
}