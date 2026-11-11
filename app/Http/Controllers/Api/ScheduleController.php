<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'cycle' => 'sometimes|in:1,2',
            'class_id' => 'sometimes|integer|exists:classes,id',
            'day' => 'sometimes|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
        ]);

        $config = SystemConfiguration::current();
        $user = $request->user();
        $query = $this->baseScheduleQuery()
            ->with(['journals' => function ($query) {
                $query->whereDate('date', Carbon::today());
            }])
            ->where('cycle_type', $validated['cycle'] ?? $config->active_cycle);

        $accessError = $this->applyUserScheduleScope(
            $query,
            $user,
            isset($validated['class_id']) ? (string) $validated['class_id'] : null
        );

        if ($accessError) {
            return $accessError;
        }

        if (isset($validated['day'])) {
            $query->where('day_of_week', $validated['day']);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeSchedules($query->orderBy('start_time')->get()),
        ]);
    }

    // Jadwal milik guru yang sedang login
    public function mySchedules(Request $request)
    {
        $request->validate([
            'cycle' => 'sometimes|in:1,2',
        ]);

        $config = SystemConfiguration::current();
        $user = $request->user();

        if (!$user->isGuru() && !$user->isWaliKelas() && !$user->isGuruBK()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya guru yang dapat mengakses jadwal mengajarnya.',
            ], 403);
        }

        $cycle = $request->query('cycle', $config->active_cycle);
        $schedules = $this->baseScheduleQuery()
            ->with(['journals' => function ($query) {
                $query->whereDate('date', Carbon::today());
            }])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $cycle)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $this->serializeSchedules($schedules),
        ]);
    }

    // Jadwal hari ini (Guru / Siswa / Orang Tua)
    public function today(Request $request)
    {
        $request->validate([
            'class_id' => 'sometimes|integer|exists:classes,id',
        ]);

        $user = $request->user();
        $config = SystemConfiguration::current();
        $today = Carbon::now()->locale('id')->isoFormat('dddd');

        $query = $this->baseScheduleQuery()
            ->with(['journals' => function ($query) {
                $query->whereDate('date', Carbon::today());
            }])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today);

        $accessError = $this->applyUserScheduleScope(
            $query,
            $user,
            $request->query('class_id')
        );

        if ($accessError) {
            return $accessError;
        }

        return response()->json([
            'success' => true,
            'day' => $today,
            'cycle' => (int) $config->active_cycle,
            'data' => $this->serializeSchedules($query->orderBy('start_time')->get()),
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
        $targetCycle = $request->input('cycle_type', $request->input('cycle', $config->active_cycle));

        if ($config->isCycleLocked($targetCycle)) {
            return response()->json([
                'success' => false,
                'message' => "Jadwal Putaran {$targetCycle} sudah dikunci. Tidak bisa menambah jadwal."
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
            'data'    => $this->serializeSchedules(collect([
                $schedule->load(['teacher:id,fullname', 'class:id,class_name', 'room:id,room_name', 'period']),
            ]))[0],
        ], 201);
    }

    private function baseScheduleQuery(): Builder
    {
        return Schedule::with([
            'teacher:id,fullname',
            'class:id,class_name',
            'room:id,room_name',
            'period:id,period_number,label,start_time,end_time,is_break',
        ]);
    }

    private function applyUserScheduleScope(
        Builder $query,
        User $user,
        ?string $requestedClassId = null
    ): ?JsonResponse {
        if ($user->isWaliKelas()) {
            if ($requestedClassId !== null) {
                $query->where('class_id', (int) $requestedClassId);
                return null;
            }

            $query->where('teacher_id', $user->id);
            return null;
        }

        if ($user->isSiswa()) {
            if (!$user->class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun siswa belum terhubung ke kelas.',
                ], 422);
            }

            if ($requestedClassId !== null && (int) $requestedClassId !== (int) $user->class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak mengakses jadwal kelas tersebut.',
                ], 403);
            }

            $query->where('class_id', $user->class_id);
            return null;
        }

        if ($user->isOrangTua()) {
            $user->syncChildrenParentId();
            $classIds = $user->getChildrenQuery()
                ->whereNotNull('class_id')
                ->pluck('class_id')
                ->unique()
                ->values();

            if ($classIds->isEmpty() && $user->class_id) {
                $classIds = collect([(int) $user->class_id]);
            }

            if ($requestedClassId !== null && !$classIds->contains((int) $requestedClassId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak mengakses jadwal kelas tersebut.',
                ], 403);
            }

            if ($requestedClassId !== null) {
                $query->where('class_id', $requestedClassId);
            } else {
                $query->whereIn('class_id', $classIds);
            }
            return null;
        }

        if ($user->isGuru() || $user->isGuruBK()) {
            $query->where('teacher_id', $user->id);
            if ($requestedClassId !== null) {
                $query->where('class_id', $requestedClassId);
            }
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak.',
        ], 403);
    }

    private function serializeSchedules(Collection $schedules): array
    {
        return $schedules->map(function (Schedule $schedule) {
            $data = $schedule->toArray();
            $data['cycle'] = (int) $schedule->cycle_type;

            return $data;
        })->all();
    }
}