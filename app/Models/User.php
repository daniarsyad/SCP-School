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
        'nis',
        'nip',
        'email',
        'phone',
        'address',
        'password',
        'fullname',
        'subject',
        'is_alumni',
    ];

    protected $casts = [
        'is_alumni' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function additionalRoles()
    {
        return $this->belongsToMany(Role::class, 'user_additional_roles');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role?->role_name === $roleName
            || $this->additionalRoles->contains('role_name', $roleName);
    }

    public function hasPrimaryRole(string $roleName): bool
    {
        return $this->role?->role_name === $roleName;
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

    public function getChildrenQuery(bool $includeAlumni = false)
    {
        $query = User::where(function ($q) {
            $q->where('parent_id', $this->id);
            if ($this->nis) {
                $q->orWhere(function ($sub) {
                    $sub->where('nis', $this->nis)
                        ->whereHas('role', fn ($r) => $r->where('role_name', 'Siswa'));
                });
            }
        })->whereHas('role', fn ($r) => $r->where('role_name', 'Siswa'));

        if (!$includeAlumni) {
            $query->where('is_alumni', false);
        }

        return $query;
    }

    public function syncChildrenParentId()
    {
        if (!$this->isOrangTua()) {
            return collect();
        }

        $allChildren = $this->getChildrenQuery(true)->get();
        foreach ($allChildren as $child) {
            if ($child->parent_id !== $this->id) {
                $child->update(['parent_id' => $this->id]);
            }
        }

        return $this->getChildrenQuery(false)->with('class:id,class_name')->get();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function bkClasses()
    {
        return $this->belongsToMany(Classes::class, 'guru_bk_classes', 'user_id', 'class_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }

    public function isWaka()
    {
        return $this->hasRole('Waka Kurikulum');
    }

    public function isGuru()
    {
        return $this->hasRole('Guru');
    }

    public function isWaliKelas()
    {
        return $this->hasRole('Wali Kelas');
    }

    public function isGuruBK()
    {
        return $this->hasRole('Guru BK');
    }

    public function isKepalaSekolah()
    {
        return $this->hasRole('Kepala Sekolah');
    }

    public function isSiswa()
    {
        return $this->hasRole('Siswa');
    }

    public function isOrangTua()
    {
        return $this->hasRole('Orang Tua');
    }
}