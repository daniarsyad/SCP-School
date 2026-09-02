<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    public $timestamps = false; // karena hanya ada created_at

    protected $fillable = [
        'schedule_id',
        'date',
        'topic_description',
        'photo_path',
        'created_at',
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