<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $table = 'classes';
    protected $fillable = [
    'class_name',
    'grade',
    'major',
    'section',
];
    public function users()
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function homeroomTeacher()
    {
        return $this->hasOne(User::class, 'class_id')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Wali Kelas'));
    }

    public function counselingTeachers()
    {
        return $this->belongsToMany(User::class, 'guru_bk_classes', 'class_id', 'user_id');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'class_id')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->orderBy('fullname');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
}