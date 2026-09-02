<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $table = 'classes';
    protected $fillable = ['class_name'];

    public function users()
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
}