<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

// Sinkronisasi otomatis izin atau sakit siswa ke data presensi jurnal KBM
class AttendanceLeaveSynchronizer
{
    public function recalculateForStudentDate(int $studentId, string $date): void
    {
        $status = DB::table('leave_requests')
            ->where('student_id', $studentId)
            ->where('status_approval', 'Approved')
            ->whereNull('cancelled_at')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByRaw("CASE leave_type WHEN 'Sakit' THEN 0 ELSE 1 END")
            ->value('leave_type');

        $attendances = Attendance::where('student_id', $studentId)
            ->whereDate('date', $date)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($attendances as $attendance) {
            $sync = DB::table('attendance_leave_syncs')
                ->where('attendance_id', $attendance->id)
                ->first();

            if ($status !== null) {
                if (! $sync) {
                    DB::table('attendance_leave_syncs')->insert([
                        'attendance_id' => $attendance->id,
                        'base_status' => $attendance->status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $attendance->update(['status' => $status]);

                continue;
            }

            if ($sync) {
                $attendance->update(['status' => $sync->base_status]);
                DB::table('attendance_leave_syncs')
                    ->where('attendance_id', $attendance->id)
                    ->delete();
            }
        }
    }

    public function setTeacherStatus(Attendance $attendance, string $status): void
    {
        DB::transaction(function () use ($attendance, $status) {
            DB::table('attendance_leave_syncs')
                ->where('attendance_id', $attendance->id)
                ->update(['base_status' => $status, 'updated_at' => now()]);

            $this->recalculateForStudentDate(
                (int) $attendance->student_id,
                $attendance->date->toDateString()
            );

            if (! DB::table('attendance_leave_syncs')->where('attendance_id', $attendance->id)->exists()) {
                $attendance->update(['status' => $status]);
            }
        });
    }
}
