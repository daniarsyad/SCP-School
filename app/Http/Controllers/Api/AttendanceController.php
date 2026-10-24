<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    // List presensi per jurnal (hanya guru pemilik jadwal)
    public function byJournal(Request $request, $journalId)
    {
        $journal = Journal::with('schedule')->findOrFail($journalId);

        if ($journal->schedule->teacher_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak melihat presensi ini.'
            ], 403);
        }

        $attendances = Attendance::with(['student:id,fullname'])
            ->where('journal_id', $journalId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    // Ubah status: Hadir / Izin / Alpha
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Hadir,Izin,Alpha',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::with('journal.schedule')->findOrFail($id);

        if ($attendance->journal->schedule->teacher_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengubah presensi ini.'
            ], 403);
        }

        $attendance->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status presensi diperbarui',
            'data'    => $attendance->load('student:id,fullname'),
        ]);
    }

    // Riwayat presensi (Siswa / Orang Tua)
    public function myAttendances(Request $request)
    {
        $user = $request->user();

        if ($user->isSiswa()) {
            $studentIds = [$user->id];
        } elseif ($user->isOrangTua()) {
            $studentIds = User::where('parent_id', $user->id)->pluck('id');
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $attendances = Attendance::with([
                'journal.schedule:id,subject,day_of_week,class_id,room_id',
                'student:id,fullname',
            ])
            ->whereIn('student_id', $studentIds)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    public function report(Request $request)
    {
        $user = $request->user();

        if (!$user->isWaliKelas() && !$user->isGuruBK()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->isWaliKelas() && !$user->class_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Wali Kelas belum terhubung ke kelas.'
            ], 422);
        }

        $date = $request->input('date', Carbon::today()->toDateString());
        $query = Attendance::with([
            'student:id,fullname',
            'journal:id,schedule_id,date',
            'journal.schedule:id,subject,class_id,teacher_id',
            'journal.schedule.class:id,class_name',
            'journal.schedule.teacher:id,fullname',
        ])->whereDate('date', $date);

        if ($user->isWaliKelas()) {
            $query->whereHas('journal.schedule', function ($schedule) use ($user) {
                $schedule->where('class_id', $user->class_id);
            });
        } elseif ($request->filled('class_id')) {
            $query->whereHas('journal.schedule', function ($schedule) use ($request) {
                $schedule->where('class_id', $request->input('class_id'));
            });
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $query->orderBy('id')->get(),
        ]);
    }
}