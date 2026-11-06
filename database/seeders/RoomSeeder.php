<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['room_name' => 'Lab RPL 1'],
            ['room_name' => 'Lab RPL 2'],
            ['room_name' => 'Ruang 101'],
            ['room_name' => 'Ruang 102'],
            ['room_name' => 'Ruang 201'],
            ['room_name' => 'Ruang 202'],
            ['room_name' => 'Aula'],
        ];

        foreach ($rooms as $room) {
            DB::table('rooms')->updateOrInsert(
                ['room_name' => $room['room_name']],
                $room
            );
        }
    }
}