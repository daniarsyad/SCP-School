<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    // Semua jadwal di putaran aktif
    public function index()
    {
        $config = SystemConfiguration::current();

        $schedules = Schedule::with(['teacher:id,fullname', 'class:id,class_name', 'room:id,room_name'])
            ->where('cycle_type', $config->active_cycle)
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    // Jadwal milik guru yang sedang login
    public function mySchedules(Request $request)
    {
        $config = SystemConfiguration::current();
        $user = $request->user();

        $schedules = Schedule::with(['class:id,class_name', 'room:id,room_name'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $config->active_cycle)
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    // Input Jadwal — HANYA Waka Kurikulum
    public function store(Request $request)
    {
        if (!$request->user()->isWaka()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Waka Kurikulum yang dapat menambah jadwal.'
            ], 403);
        }

        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah dikunci. Tidak bisa menambah jadwal.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id'  => 'required|exists:users,id',
            'class_id'    => 'required|exists:classes,id',
            'room_id'     => 'required|exists:rooms,id',
            'subject'     => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['cycle_type'] = $config->active_cycle;

        // Validasi bentrok
        $conflict = Schedule::where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($data) {
                $q->where('start_time', '<', $data['end_time'])
                  ->where('end_time', '>', $data['start_time']);
            })
            ->where(function ($q) use ($data) {
                $q->where('room_id', $data['room_id'])
                  ->orWhere('class_id', $data['class_id'])
                  ->orWhere('teacher_id', $data['teacher_id']);
            })
            ->with(['teacher', 'class', 'room'])
            ->first();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal bentrok! Sudah ada jadwal di waktu / ruang / kelas / guru yang sama.',
                'conflict_with' => [
                    'subject' => $conflict->subject,
                    'teacher' => $conflict->teacher->fullname ?? '-',
                    'class'   => $conflict->class->class_name ?? '-',
                    'room'    => $conflict->room->room_name ?? '-',
                    'time'    => substr($conflict->start_time, 0, 5) . ' - ' . substr($conflict->end_time, 0, 5),
                ]
            ], 409);
        }

        $schedule = Schedule::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil ditambahkan',
            'data'    => $schedule->load(['teacher:id,fullname', 'class:id,class_name', 'room:id,room_name'])
        ], 201);
    }
}