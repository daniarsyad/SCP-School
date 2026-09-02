<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class JournalController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id'       => 'required|exists:schedules,id',
            'topic_description' => 'required|string',
            'photo'             => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $schedule = Schedule::with('class')->findOrFail($request->schedule_id);

        // Pastikan jadwal milik guru yang login
        if ($schedule->teacher_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengisi jurnal untuk jadwal ini.'
            ], 403);
        }

        // Cek apakah sudah ada jurnal hari ini
        $existing = Journal::where('schedule_id', $schedule->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal untuk jadwal ini hari ini sudah diisi.'
            ], 409);
        }

        // Upload foto
        $photoPath = $request->file('photo')->store('journals', 'public');

        $journal = Journal::create([
            'schedule_id'       => $schedule->id,
            'date'              => Carbon::today()->toDateString(),
            'topic_description' => $request->topic_description,
            'photo_path'        => $photoPath,
            'created_at'        => now(),
        ]);

        // =====================================================
        // SINKRONISASI IZIN → PRESENSI (sesuai aturan bisnis)
        // =====================================================
        $today = Carbon::today()->toDateString();

        // Ambil semua siswa di kelas ini
        $students = User::where('class_id', $schedule->class_id)
            ->whereHas('role', function ($q) {
                $q->where('role_name', 'Siswa');
            })
            ->get();

        foreach ($students as $student) {
            // Cek apakah siswa punya izin Approved di tanggal hari ini
            $leave = LeaveRequest::where('student_id', $student->id)
                ->where('status_approval', 'Approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            $status = $leave ? 'Izin' : 'Hadir';

            // Buat attendance
            Attendance::create([
                'journal_id' => $journal->id,
                'student_id' => $student->id,
                'status'     => $status,
                'date'       => $today,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil dibuat dan presensi berhasil disinkronkan',
            'data'    => $journal->load('attendances')
        ], 201);
    }

    // Lihat jurnal milik guru yang login
    public function myJournals(Request $request)
    {
        $journals = Journal::with(['schedule.class', 'schedule.room'])
            ->whereHas('schedule', function ($q) use ($request) {
                $q->where('teacher_id', $request->user()->id);
            })
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $journals
        ]);
    }
}