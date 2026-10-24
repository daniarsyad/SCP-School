<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'parent_id',
        'class_id',
        'username',
        'email',
        'password',
        'fullname',
        'subject',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
    
    public function attendances()
    {
    return $this->hasMany(\App\Models\Attendance::class, 'student_id');
    }   

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }

    public function isWaka()
    {
        return $this->role && $this->role->role_name === 'Waka Kurikulum';
    }

    public function isGuru()
    {
        return $this->role && $this->role->role_name === 'Guru';
    }

    public function isWaliKelas()
    {
        return $this->role && $this->role->role_name === 'Wali Kelas';
    }

    public function isGuruBK()
    {
        return $this->role && $this->role->role_name === 'Guru BK';
    }

    public function isSiswa()
    {
        return $this->role && $this->role->role_name === 'Siswa';
    }

    public function isOrangTua()
    {
        return $this->role && $this->role->role_name === 'Orang Tua';
    }
}