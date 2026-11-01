<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    public $timestamps = false;

    protected $hidden = [
        'idempotency_key',
        'idempotency_hash',
    ];

    protected $fillable = [
        'schedule_id',
        'date',
        'topic_description',
        'activity_notes',
        'photo_path',
        'created_at',
        'idempotency_key',
        'idempotency_hash',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}