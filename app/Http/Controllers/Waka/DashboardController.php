<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\Schedule;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $config = SystemConfiguration::current();
        $now    = Carbon::now('Asia/Jakarta');

        // Auto-geser periode kalau sudah lewat
        if (!$config->end_date || $now->copy()->startOfDay()->gt($config->end_date)) {
            $config->start_date = $now->copy()->startOfDay();
            $config->end_date   = $now->copy()->startOfDay()->addDays(13);
            $config->save();
        }

        $dayMap = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];

        $today     = $dayMap[$now->format('l')] ?? 'Senin';
        $todayDate = $now->toDateString();

        $todaySchedules = Schedule::with(['teacher', 'class', 'room', 'journals'])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        $belumIsi = $todaySchedules->filter(function ($s) use ($todayDate) {
            $punyaJurnalHariIni = $s->journals->contains(function ($j) use ($todayDate) {
                return Carbon::parse($j->date)->toDateString() === $todayDate;
            });

            return !$punyaJurnalHariIni;
        });

        $lateJournals = $belumIsi->filter(function ($s) use ($now, $todayDate) {
            $endTime = substr((string) $s->end_time, 0, 8);
            $end     = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $todayDate . ' ' . $endTime,
                'Asia/Jakarta'
            );

            return $now->greaterThan($end->copy()->addMinutes(30));
        })->values();

        return view('waka.dashboard', compact('config', 'lateJournals'));
    }
}