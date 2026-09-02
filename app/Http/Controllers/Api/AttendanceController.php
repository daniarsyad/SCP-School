<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // Lihat presensi berdasarkan journal_id
    public function byJournal($journalId)
    {
        $attendances = Attendance::with(['student:id,fullname'])
            ->where('journal_id', $journalId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    // Lihat presensi siswa yang sedang login (untuk Siswa / Orang Tua)
    public function myAttendances(Request $request)
    {
        $user = $request->user();

        if ($user->isSiswa()) {
            $studentIds = [$user->id];
        } elseif ($user->isOrangTua()) {
            $studentIds = \App\Models\User::where('parent_id', $user->id)->pluck('id');
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $attendances = Attendance::with(['journal.schedule:id,subject,day_of_week'])
            ->whereIn('student_id', $studentIds)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }
}