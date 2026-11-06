<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah Kelas yang belum ada di master
        $missingClasses = [
            ['grade' => 'XI', 'major' => 'DKV', 'section' => '2', 'class_name' => 'XI DKV 2'],
            ['grade' => 'XI', 'major' => 'AKL', 'section' => '3', 'class_name' => 'XI AKL 3'],
        ];

        foreach ($missingClasses as $cls) {
            $exists = DB::table('classes')->where('class_name', $cls['class_name'])->exists();
            if (!$exists) {
                DB::table('classes')->insert(array_merge($cls, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 2. Tambah Ruangan yang belum ada di master
        $missingRooms = [
            'Lab IPA',
            'Ruang Siniar',
        ];

        foreach ($missingRooms as $roomName) {
            $exists = DB::table('rooms')->where('room_name', $roomName)->exists();
            if (!$exists) {
                DB::table('rooms')->insert([
                    'room_name' => $roomName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Tambah Guru Tatik Herawati jika belum ada
        $tatikExists = DB::table('users')->where('username', 'tatik.herawati')->orWhere('fullname', 'TATIK HERAWATI')->exists();
        if (!$tatikExists) {
            $guruRole = Role::where('role_name', 'Guru')->first();
            if ($guruRole) {
                DB::table('users')->insert([
                    'fullname' => 'TATIK HERAWATI',
                    'username' => 'tatik.herawati',
                    'role_id' => $guruRole->id,
                    'subject' => 'Pendidikan Pancasila',
                    'password' => Hash::make('password123'),
                    'is_alumni' => false,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('classes')->whereIn('class_name', ['XI DKV 2', 'XI AKL 3'])->delete();
        DB::table('rooms')->whereIn('room_name', ['Lab IPA', 'Ruang Siniar'])->delete();
        DB::table('users')->where('username', 'tatik.herawati')->delete();
    }
};

