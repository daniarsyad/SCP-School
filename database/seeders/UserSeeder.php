<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $wakaId      = DB::table('roles')->where('role_name', 'Waka Kurikulum')->value('id');
        $guruId      = DB::table('roles')->where('role_name', 'Guru')->value('id');
        $waliKelasId = DB::table('roles')->where('role_name', 'Wali Kelas')->value('id');
        $bkId        = DB::table('roles')->where('role_name', 'Guru BK')->value('id');
        $siswaId     = DB::table('roles')->where('role_name', 'Siswa')->value('id');
        $ortuId      = DB::table('roles')->where('role_name', 'Orang Tua')->value('id');

        $users = [
            // Waka Kurikulum
            [
                'role_id'   => $wakaId,
                'parent_id' => null,
                'class_id'  => null,
                'username'  => 'waka',
                'password'  => Hash::make('password'),
                'fullname'  => 'Dr. Andi Wijaya, M.Pd',
            ],
            // Guru
            [
                'role_id'   => $guruId,
                'parent_id' => null,
                'class_id'  => null,
                'username'  => 'guru1',
                'password'  => Hash::make('password'),
                'fullname'  => 'Budi Santoso, S.Kom',
            ],
            [
                'role_id'   => $guruId,
                'parent_id' => null,
                'class_id'  => null,
                'username'  => 'guru2',
                'password'  => Hash::make('password'),
                'fullname'  => 'Siti Aminah, S.Pd',
            ],
            // Wali Kelas
            [
                'role_id'   => $waliKelasId,
                'parent_id' => null,
                'class_id'  => 1,
                'username'  => 'walikelas1',
                'password'  => Hash::make('password'),
                'fullname'  => 'Rina Marlina, S.Pd',
            ],
            // Guru BK
            [
                'role_id'   => $bkId,
                'parent_id' => null,
                'class_id'  => null,
                'username'  => 'gurubk',
                'password'  => Hash::make('password'),
                'fullname'  => 'Dewi Lestari, S.Psi',
            ],
            // Siswa
            [
                'role_id'   => $siswaId,
                'parent_id' => null,
                'class_id'  => 1,
                'username'  => 'siswa1',
                'password'  => Hash::make('password'),
                'fullname'  => 'Ahmad Fauzi',
            ],
            [
                'role_id'   => $siswaId,
                'parent_id' => null,
                'class_id'  => 1,
                'username'  => 'siswa2',
                'password'  => Hash::make('password'),
                'fullname'  => 'Nur Aini',
            ],
            // Orang Tua
            [
                'role_id'   => $ortuId,
                'parent_id' => null,
                'class_id'  => null,
                'username'  => 'ortu1',
                'password'  => Hash::make('password'),
                'fullname'  => 'Bapak Fauzi (Ortu Ahmad)',
            ],
            [
                'role_id'   => $ortuId,
                'parent_id' => null,
                'class_id'  => null,
                'username'  => 'ortu2',
                'password'  => Hash::make('password'),
                'fullname'  => 'Ibu Aini (Ortu Nur Aini)',
            ],
        ];

        foreach ($users as $userData) {
            DB::table('users')->updateOrInsert(
                ['username' => $userData['username']],
                $userData
            );
        }

        // Update parent_id siswa
        $ortu1Id = DB::table('users')->where('username', 'ortu1')->value('id');
        $ortu2Id = DB::table('users')->where('username', 'ortu2')->value('id');

        DB::table('users')->where('username', 'siswa1')->update(['parent_id' => $ortu1Id]);
        DB::table('users')->where('username', 'siswa2')->update(['parent_id' => $ortu2Id]);
    }
}