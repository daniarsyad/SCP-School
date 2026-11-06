<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'Waka Kurikulum'],
            ['role_name' => 'Guru'],
            ['role_name' => 'Wali Kelas'],
            ['role_name' => 'Guru BK'],
            ['role_name' => 'Siswa'],
            ['role_name' => 'Orang Tua'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $role['role_name']],
                $role
            );
        }
    }
}