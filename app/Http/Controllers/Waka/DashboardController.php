<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\Schedule;
use App\Models\Attendance;
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
        $todayScheduleIds = $todaySchedules->pluck('id');
        $todayJournalCount = \App\Models\Journal::whereIn('schedule_id', $todayScheduleIds)
            ->whereDate('date', $todayDate)
            ->distinct('schedule_id')
            ->count('schedule_id');

        // Total target siswa dari seluruh kelas yang terjadwal hari ini
        $todayClassIds       = $todaySchedules->pluck('class_id')->unique();
        $totalTargetStudents = \App\Models\User::whereIn('class_id', $todayClassIds)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->count();

        // Jumlah siswa unik yang tercatat Hadir hari ini
        $todayHadirCount = Attendance::whereDate('date', $todayDate)
            ->where('status', 'Hadir')
            ->distinct('student_id')
            ->count('student_id');

        // Total siswa unik yang sudah terdata presensinya hari ini
        $todayAttendanceTotal = Attendance::whereDate('date', $todayDate)
            ->distinct('student_id')
            ->count('student_id');

        $todayJournalPercentage = $todaySchedules->isEmpty()
            ? 0
            : round(($todayJournalCount / $todaySchedules->count()) * 100, 2);

        // Persentase kehadiran dihitung proporsional terhadap total target siswa yang terjadwal hari ini
        $todayAttendancePercentage = $totalTargetStudents === 0
            ? 0
            : round(($todayHadirCount / $totalTargetStudents) * 100, 2);

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

        return view('waka.dashboard', compact(
            'config',
            'lateJournals',
            'todayJournalPercentage',
            'todayAttendancePercentage',
            'todayJournalCount',
            'todaySchedules',
            'todayAttendanceTotal',
            'todayHadirCount',
            'totalTargetStudents'
        ));
    }
}