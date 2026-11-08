<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WakaController extends Controller
{
    public function dashboardSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isWaka()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Waka Kurikulum yang memiliki akses ke dashboard ini.',
            ], 403);
        }

        $config    = SystemConfiguration::current();
        $now       = Carbon::now();
        $today     = $now->locale('id')->isoFormat('dddd');
        $todayDate = $now->toDateString();

        $todaySchedules = Schedule::with([
            'teacher:id,fullname',
            'class:id,class_name',
            'room:id,room_name',
            'period',
            'endPeriod',
            'journals' => function ($query) use ($todayDate) {
                $query->whereDate('date', $todayDate);
            },
        ])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        $totalSchedulesToday    = $todaySchedules->count();
        $submittedSchedules     = $todaySchedules->filter(fn ($s) => $s->journals->isNotEmpty());
        $journalsSubmittedToday = $submittedSchedules->count();
        $pendingSchedules       = $todaySchedules->filter(fn ($s) => $s->journals->isEmpty());
        $journalsPendingToday   = $pendingSchedules->count();

        // Statistik Kehadiran Siswa Terjadwal Hari Ini
        $todayClassIds       = $todaySchedules->pluck('class_id')->unique();
        $totalTargetStudents = \App\Models\User::whereIn('class_id', $todayClassIds)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->count();

        $todayHadirCount = \App\Models\Attendance::whereDate('date', $todayDate)
            ->where('status', 'Hadir')
            ->distinct('student_id')
            ->count('student_id');

        $todayAttendanceTotal = \App\Models\Attendance::whereDate('date', $todayDate)
            ->distinct('student_id')
            ->count('student_id');

        $todayAttendancePercentage = $totalTargetStudents === 0
            ? 0
            : round(($todayHadirCount / $totalTargetStudents) * 100, 2);

        $lateTeachers = [];

        foreach ($pendingSchedules as $s) {
            $endTimeStr = substr((string) $s->end_time, 0, 8);
            if ($endTimeStr !== '') {
                $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $todayDate . ' ' . $endTimeStr);
                // Terlambat jika sudah melewati 30 menit setelah jam pelajaran selesai
                if ($now->greaterThan($endDateTime->copy()->addMinutes(30))) {
                    $lateMinutes    = (int) $now->diffInMinutes($endDateTime);
                    $lateTeachers[] = [
                        'schedule_id'         => $s->id,
                        'teacher_id'          => $s->teacher_id,
                        'teacher_name'        => $s->teacher?->fullname ?? '-',
                        'nama_guru'           => $s->teacher?->fullname ?? '-',
                        'subject'             => $s->subject,
                        'mata_pelajaran'      => $s->subject,
                        'class_name'          => $s->class?->class_name ?? '-',
                        'kelas'               => $s->class?->class_name ?? '-',
                        'room_name'           => $s->room?->room_name ?? '-',
                        'ruangan'             => $s->room?->room_name ?? '-',
                        'time'                => substr((string) $s->start_time, 0, 5) . ' - ' . substr((string) $s->end_time, 0, 5),
                        'jam_kbm'             => substr((string) $s->start_time, 0, 5) . ' - ' . substr((string) $s->end_time, 0, 5),
                        'start_time'          => substr((string) $s->start_time, 0, 5),
                        'end_time'            => substr((string) $s->end_time, 0, 5),
                        'late_minutes'        => $lateMinutes,
                        'menit_keterlambatan' => $lateMinutes,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard Waka berhasil dimuat',
            'data'    => [
                'active_cycle'             => (string) $config->active_cycle,
                'current_cycle'            => 'Putaran ' . $config->active_cycle,
                'is_schedule_locked'       => (bool) $config->is_schedule_locked,
                'day'                      => $today,
                'date'                     => $todayDate,
                'total_schedules_today'       => $totalSchedulesToday,
                'journals_submitted_today'    => $journalsSubmittedToday,
                'journals_pending_today'      => $journalsPendingToday,
                'total_target_students'       => $totalTargetStudents,
                'today_hadir_count'           => $todayHadirCount,
                'today_attendance_total'      => $todayAttendanceTotal,
                'today_attendance_percentage' => $todayAttendancePercentage,
                'late_teachers'               => $lateTeachers,
            ],
        ]);
    }
}

