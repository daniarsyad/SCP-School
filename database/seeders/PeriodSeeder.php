<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Period;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        Period::query()->delete();

        $rows = [];

        // ========== SENIN (upacara 07:00-08:00, 10 jam) ==========
        foreach ([
            ['Jam ke-1',  '08:00', '08:35', false, 1],
            ['Jam ke-2',  '08:35', '09:10', false, 2],
            ['Jam ke-3',  '09:10', '09:45', false, 3],
            ['Istirahat 1','09:45','10:00', true,  null],
            ['Jam ke-4',  '10:00', '10:35', false, 4],
            ['Jam ke-5',  '10:35', '11:10', false, 5],
            ['Jam ke-6',  '11:10', '11:45', false, 6],
            ['Istirahat 2','11:45','12:45', true,  null],
            ['Jam ke-7',  '12:45', '13:25', false, 7],
            ['Jam ke-8',  '13:25', '14:00', false, 8],
            ['Jam ke-9',  '14:00', '14:35', false, 9],
            ['Jam ke-10', '14:35', '15:10', false, 10],
        ] as $r) {
            $rows[] = [
                'label' => $r[0], 'start_time' => $r[1], 'end_time' => $r[2],
                'is_break' => $r[3], 'period_number' => $r[4], 'day_group' => 'senin',
            ];
        }

        // ========== SELASA (ada literasi, 10 jam) ==========
        foreach ([
            ['Jam ke-1',  '07:30', '08:10', false, 1],
            ['Jam ke-2',  '08:10', '08:50', false, 2],
            ['Jam ke-3',  '08:50', '09:30', false, 3],
            ['Istirahat 1','09:30','09:45', true,  null],
            ['Jam ke-4',  '09:45', '10:25', false, 4],
            ['Jam ke-5',  '10:25', '11:05', false, 5],
            ['Jam ke-6',  '11:05', '11:45', false, 6],
            ['Istirahat 2','11:45','12:45', true,  null],
            ['Jam ke-7',  '12:45', '13:25', false, 7],
            ['Jam ke-8',  '13:25', '14:00', false, 8],
            ['Jam ke-9',  '14:00', '14:35', false, 9],
            ['Jam ke-10', '14:45', '15:10', false, 10],
        ] as $r) {
            $rows[] = [
                'label' => $r[0], 'start_time' => $r[1], 'end_time' => $r[2],
                'is_break' => $r[3], 'period_number' => $r[4], 'day_group' => 'selasa',
            ];
        }

        // ========== RABU & KAMIS (10 jam, sama) ==========
        foreach ([
            ['Jam ke-1',  '07:15', '07:55', false, 1],
            ['Jam ke-2',  '07:55', '08:35', false, 2],
            ['Jam ke-3',  '08:35', '09:15', false, 3],
            ['Istirahat 1','09:15','09:30', true,  null],
            ['Jam ke-4',  '09:30', '10:10', false, 4],
            ['Jam ke-5',  '10:10', '10:50', false, 5],
            ['Jam ke-6',  '10:50', '11:30', false, 6],
            ['Istirahat 2','11:30','12:30', true,  null],
            ['Jam ke-7',  '12:30', '13:10', false, 7],
            ['Jam ke-8',  '13:10', '13:50', false, 8],
            ['Jam ke-9',  '13:50', '14:30', false, 9],
            ['Jam ke-10', '14:30', '15:10', false, 10],
        ] as $r) {
            $rows[] = [
                'label' => $r[0], 'start_time' => $r[1], 'end_time' => $r[2],
                'is_break' => $r[3], 'period_number' => $r[4], 'day_group' => 'rabu_kamis',
            ];
        }

        // ========== JUMAT (8 jam) ==========
        foreach ([
            ['Jam ke-1',  '07:15', '07:55', false, 1],
            ['Jam ke-2',  '07:55', '08:35', false, 2],
            ['Jam ke-3',  '08:35', '09:15', false, 3],
            ['Istirahat 1','09:15','09:30', true,  null],
            ['Jam ke-4',  '09:30', '10:10', false, 4],
            ['Jam ke-5',  '10:10', '10:50', false, 5],
            ['Jam ke-6',  '10:50', '11:25', false, 6],
            ['Istirahat 2','11:25','12:40', true,  null],
            ['Jam ke-7',  '12:40', '13:15', false, 7],
            ['Jam ke-8',  '13:15', '13:50', false, 8],
        ] as $r) {
            $rows[] = [
                'label' => $r[0], 'start_time' => $r[1], 'end_time' => $r[2],
                'is_break' => $r[3], 'period_number' => $r[4], 'day_group' => 'jumat',
            ];
        }

        foreach ($rows as $row) {
            Period::create($row);
        }
    }
}