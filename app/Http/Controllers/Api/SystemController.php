<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\CycleHistory;
use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemController extends Controller
{
    public function config(): JsonResponse
    {
        $config = SystemConfiguration::current();

        return response()->json([
            'success' => true,
            'data'    => [
                'active_cycle'       => (string) $config->active_cycle,
                'current_cycle'      => 'Putaran ' . $config->active_cycle,
                'is_schedule_locked' => (bool) $config->is_schedule_locked,
                'start_date'         => $config->start_date?->format('Y-m-d'),
                'end_date'           => $config->end_date?->format('Y-m-d'),
            ],
        ]);
    }

    public function updateCycle(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isWaka()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Waka Kurikulum yang dapat mengubah siklus putaran.',
            ], 403);
        }

        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa mengubah siklus karena jadwal sedang dikunci.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'current_cycle' => 'sometimes|string',
            'active_cycle'  => 'sometimes',
            'cycle'         => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $inputCycle = $request->input('current_cycle')
            ?? $request->input('active_cycle')
            ?? $request->input('cycle');

        $targetCycle = null;
        if ($inputCycle !== null) {
            $cleaned = trim((string) $inputCycle);
            if (in_array(strtolower($cleaned), ['1', 'putaran 1'])) {
                $targetCycle = '1';
            } elseif (in_array(strtolower($cleaned), ['2', 'putaran 2'])) {
                $targetCycle = '2';
            }
        } else {
            $targetCycle = $config->active_cycle == '1' ? '2' : '1';
        }

        if (! $targetCycle) {
            return response()->json([
                'success' => false,
                'message' => 'Nilai siklus tidak valid. Gunakan "Putaran 1" atau "Putaran 2".',
            ], 422);
        }

        if ((string) $config->active_cycle !== (string) $targetCycle) {
            $this->saveCycleHistory($config, $user->id);
        }

        $config->active_cycle = $targetCycle;
        $config->start_date   = Carbon::today();
        $config->end_date     = Carbon::today()->addDays(13);
        $config->updated_by   = $user->id;
        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Siklus berhasil diubah menjadi Putaran ' . $targetCycle,
            'data'    => [
                'active_cycle'       => (string) $config->active_cycle,
                'current_cycle'      => 'Putaran ' . $config->active_cycle,
                'is_schedule_locked' => (bool) $config->is_schedule_locked,
                'start_date'         => $config->start_date?->format('Y-m-d'),
                'end_date'           => $config->end_date?->format('Y-m-d'),
            ],
        ]);
    }

    public function updateScheduleLock(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isWaka()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Waka Kurikulum yang dapat mengubah status kunci jadwal.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_schedule_locked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $config = SystemConfiguration::current();
        $config->is_schedule_locked = (bool) $request->input('is_schedule_locked');
        $config->updated_by         = $user->id;
        $config->save();

        $statusText = $config->is_schedule_locked ? 'dikunci' : 'dibuka';

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil ' . $statusText,
            'data'    => [
                'is_schedule_locked' => (bool) $config->is_schedule_locked,
                'active_cycle'       => (string) $config->active_cycle,
                'current_cycle'      => 'Putaran ' . $config->active_cycle,
            ],
        ]);
    }

    public function classes(): JsonResponse
    {
        $classes = Classes::with([
            'homeroomTeacher:id,fullname,class_id',
            'students:id,fullname,nis,class_id',
        ])
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'grade', 'major', 'section']);

        $classes->each(function (Classes $class) {
            $class->homeroomTeacher?->makeHidden('class_id');
            $class->students->each(fn ($student) => $student->makeHidden('class_id'));
        });

        return response()->json([
            'success' => true,
            'data'    => $classes,
        ]);
    }

    public function rooms(): JsonResponse
    {
        $rooms = Room::orderBy('room_name')->get(['id', 'room_name']);

        return response()->json([
            'success' => true,
            'data'    => $rooms,
        ]);
    }

    private function saveCycleHistory(SystemConfiguration $config, ?int $userId): void
    {
        $startDate = $config->start_date ?? Carbon::today()->subDays(13);
        $endDate   = $config->end_date ?? Carbon::today();

        $journals = Journal::whereHas('schedule', function ($q) use ($config) {
            $q->where('cycle_type', $config->active_cycle);
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalJournals    = $journals->count();
        $totalAttendances = Attendance::whereIn('journal_id', $journals->pluck('id'))->count();

        $teacherIds = Schedule::where('cycle_type', $config->active_cycle)
            ->whereHas('journals', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->pluck('teacher_id')
            ->unique();

        $teachers = User::whereIn('id', $teacherIds)
            ->pluck('fullname')
            ->toArray();

        CycleHistory::create([
            'cycle_type'        => $config->active_cycle,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'total_journals'    => $totalJournals,
            'total_attendances' => $totalAttendances,
            'teachers'          => $teachers,
            'closed_by'         => $userId ?? 1,
        ]);
    }
}
