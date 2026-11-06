<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $wakaId = DB::table('users')->where('username', 'waka')->value('id');

        if (DB::table('system_configurations')->count() === 0) {
            DB::table('system_configurations')->insert([
                'active_cycle'       => '1',
                'is_schedule_locked' => false,
                'updated_by'         => $wakaId,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}