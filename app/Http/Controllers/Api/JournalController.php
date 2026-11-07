<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\AttendanceLeaveSynchronizer;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class JournalController extends Controller
{
    public function store(Request $request, AttendanceLeaveSynchronizer $leaveSynchronizer)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|integer|exists:schedules,id',
            'date' => 'sometimes|date|before_or_equal:today',
            'topic_description' => 'required|string',
            'activity_notes' => 'nullable|string|max:5000',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'attendances' => 'sometimes|array|min:1',
            'attendances.*.student_id' => 'required|integer|distinct|exists:users,id',
            'attendances.*.status' => 'required|in:Hadir,Izin,Sakit,Alpha',
            'attendances.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user = $request->user();
        $schedule = Schedule::with('class')->findOrFail($data['schedule_id']);

        if ((! $user->isGuru() && ! $user->isWaliKelas() && ! $user->isGuruBK()) ||
            (int) $schedule->teacher_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengisi jurnal untuk jadwal ini.',
            ], 403);
        }

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();
        $config = SystemConfiguration::current();
        $day = Carbon::parse($date)->locale('id')->isoFormat('dddd');

        if ($schedule->day_of_week !== $day ||
            (string) $schedule->cycle_type !== (string) $config->active_cycle ||
            ($config->start_date && Carbon::parse($date)->lt($config->start_date->startOfDay())) ||
            ($config->end_date && Carbon::parse($date)->gt($config->end_date->endOfDay()))) {
            return $this->validationError([
                'date' => ['Tanggal tidak sesuai dengan jadwal atau periode KBM yang aktif.'],
            ]);
        }

        // Anti-curang: Cegah guru mengisi jurnal sebelum jam pelajaran dimulai
        $now = Carbon::now();
        $nowTime = $now->format('H:i:s');
        $isToday = Carbon::parse($date)->isToday();

        if ($isToday && $schedule->start_time && $nowTime < $schedule->start_time) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal belum dapat diisi karena jam pelajaran belum dimulai (Pukul ' . substr($schedule->start_time, 0, 5) . ').',
            ], 422);
        }

        // Cek duplikasi jurnal untuk jadwal dan tanggal yang sama
        $alreadyExists = Journal::where('schedule_id', $schedule->id)
            ->whereDate('date', $date)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal untuk jadwal ini pada hari ini sudah pernah diisi.',
            ], 409);
        }

        // Hitung menit keterlambatan (jika diisi setelah jam pelajaran selesai)
        $lateMinutes = 0;
        if ($isToday && $schedule->end_time && $nowTime > $schedule->end_time) {
            $endTime = Carbon::createFromTimeString($schedule->end_time);
            $lateMinutes = (int) $now->diffInMinutes($endTime);
        }

        $students = User::where('class_id', $schedule->class_id)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (isset($data['attendances'])) {
            $submittedStudentIds = collect($data['attendances'])
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($submittedStudentIds !== $students) {
                return $this->validationError([
                    'attendances' => ['Daftar presensi harus memuat tepat satu status untuk setiap siswa di kelas jadwal.'],
                ]);
            }
        }

        $photo = $request->file('photo');
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey !== null && (strlen($idempotencyKey) > 255 || trim($idempotencyKey) === '')) {
            return $this->validationError([
                'Idempotency-Key' => ['Idempotency-Key harus berisi 1 sampai 255 karakter.'],
            ]);
        }

        $normalizedAttendances = collect($data['attendances'] ?? [])
            ->sortBy('student_id')
            ->values()
            ->all();
        $requestHash = hash('sha256', json_encode([
            'schedule_id' => (int) $schedule->id,
            'date' => $date,
            'topic_description' => $data['topic_description'],
            'activity_notes' => $data['activity_notes'] ?? null,
            'attendances' => $normalizedAttendances,
            'photo_sha256' => hash_file('sha256', $photo->getRealPath()),
        ], JSON_THROW_ON_ERROR));

        $photoPath = null;

        try {
            $result = DB::transaction(function () use (
                $schedule,
                $user,
                $config,
                $date,
                $data,
                $students,
                $normalizedAttendances,
                $photo,
                $idempotencyKey,
                $requestHash,
                $lateMinutes,
                $leaveSynchronizer,
                &$photoPath
            ) {
                $lockedSchedule = Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();

                if ((int) $lockedSchedule->teacher_id !== (int) $user->id ||
                    (int) $lockedSchedule->class_id !== (int) $schedule->class_id) {
                    return ['forbidden' => true];
                }

                if ($lockedSchedule->day_of_week !== Carbon::parse($date)->locale('id')->isoFormat('dddd') ||
                    (string) $lockedSchedule->cycle_type !== (string) $config->active_cycle) {
                    return ['conflict' => true];
                }

                $currentStudentIds = User::where('class_id', $lockedSchedule->class_id)
                    ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                if ($currentStudentIds !== $students) {
                    return ['roster_changed' => true];
                }

                if ($idempotencyKey !== null) {
                    $sameKey = Journal::where('idempotency_key', $idempotencyKey)->first();
                    if ($sameKey) {
                        return hash_equals((string) $sameKey->idempotency_hash, $requestHash)
                            ? ['journal' => $sameKey, 'replayed' => true]
                            : ['conflict' => true];
                    }
                }

                if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
                    return ['conflict' => true];
                }

                $photoPath = $photo->store('journals', 'public');
                if (! $photoPath) {
                    throw new RuntimeException('Foto jurnal gagal disimpan.');
                }

                $journal = Journal::create([
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'topic_description' => $data['topic_description'],
                    'activity_notes' => $data['activity_notes'] ?? null,
                    'photo_path' => $photoPath,
                    'late_minutes' => $lateMinutes,
                    'created_at' => now(),
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_hash' => $idempotencyKey === null ? null : $requestHash,
                ]);

                $attendances = $normalizedAttendances
                    ? collect($normalizedAttendances)
                    : collect($students)->map(fn ($studentId) => [
                        'student_id' => $studentId,
                        'status' => 'Hadir',
                    ]);

                foreach ($attendances as $item) {
                    Attendance::create([
                        'journal_id' => $journal->id,
                        'student_id' => $item['student_id'],
                        'status' => $item['status'],
                        'notes' => $item['notes'] ?? null,
                        'date' => $date,
                    ]);
                }

                foreach ($students as $studentId) {
                    $leaveSynchronizer->recalculateForStudentDate($studentId, $date);
                }

                return ['journal' => $journal, 'replayed' => false];
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            if ($photoPath !== null) {
                Storage::disk('public')->delete($photoPath);
            }
            $sameKey = $idempotencyKey === null
                ? null
                : Journal::where('idempotency_key', $idempotencyKey)->first();

            if ($sameKey && hash_equals((string) $sameKey->idempotency_hash, $requestHash)) {
                return $this->journalResponse($sameKey, true, 200);
            }

            if (Journal::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jurnal untuk jadwal ini pada tanggal tersebut sudah tersimpan.',
                ], 409);
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($photoPath !== null) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        if (isset($result['conflict'])) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal untuk jadwal ini pada tanggal tersebut sudah tersimpan atau Idempotency-Key digunakan untuk payload berbeda.',
            ], 409);
        }

        if (isset($result['forbidden'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak lagi berhak mengisi jurnal untuk jadwal ini.',
            ], 403);
        }

        if (isset($result['roster_changed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Daftar siswa kelas berubah saat jurnal dikirim. Muat ulang presensi lalu coba kembali.',
            ], 409);
        }

        return $this->journalResponse(
            $result['journal'],
            $result['replayed'],
            $result['replayed'] ? 200 : 201
        );
    }

    public function myJournals(Request $request)
    {
        if (! $request->user()->isGuru() && ! $request->user()->isWaliKelas() && ! $request->user()->isGuruBK()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $journals = Journal::with(['schedule.class', 'schedule.room'])
            ->whereHas('schedule', function ($query) use ($request) {
                $query->where('teacher_id', $request->user()->id);
            })
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $journals,
        ]);
    }

    public function monitor(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPrimaryRole('Waka Kurikulum') && ! $user->isKepalaSekolah()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $validated = $request->validate([
            'class_id' => 'sometimes|integer|exists:classes,id',
            'grade' => 'sometimes|in:X,XI,XII',
            'major' => 'sometimes|string|max:20',
            'section' => 'sometimes|string|max:10',
            'teacher_id' => 'sometimes|integer|exists:users,id',
            'date' => 'sometimes|date',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
        ]);
        if (isset($validated['date']) && (isset($validated['from_date']) || isset($validated['to_date']))) {
            return response()->json([
                'success' => false,
                'message' => 'Gunakan date atau from_date/to_date, tidak keduanya.',
            ], 422);
        }

        $query = Schedule::with([
            'class:id,class_name,grade,major,section',
            'teacher:id,fullname',
            'journals' => function ($journalQuery) use ($validated) {
                $journalQuery->withCount('attendances')
                    ->when(isset($validated['date']), fn ($q) => $q->whereDate('date', $validated['date']))
                    ->when(isset($validated['from_date']), fn ($q) => $q->whereDate('date', '>=', $validated['from_date']))
                    ->when(isset($validated['to_date']), fn ($q) => $q->whereDate('date', '<=', $validated['to_date']));
            },
        ])
            ->when(isset($validated['class_id']), fn ($q) => $q->where('class_id', $validated['class_id']))
            ->when(isset($validated['teacher_id']), fn ($q) => $q->where('teacher_id', $validated['teacher_id']))
            ->whereHas('class', fn ($class) => $class
                ->when(isset($validated['grade']), fn ($q) => $q->where('grade', $validated['grade']))
                ->when(isset($validated['major']), fn ($q) => $q->where('major', $validated['major']))
                ->when(isset($validated['section']), fn ($q) => $q->where('section', $validated['section'])))
            ->orderBy('class_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        $expectedCounts = User::whereNotNull('class_id')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->selectRaw('class_id, COUNT(*) as aggregate')
            ->groupBy('class_id')
            ->pluck('aggregate', 'class_id');

        $data = $query->get()->flatMap(function (Schedule $schedule) use ($expectedCounts) {
            return $schedule->journals->map(fn (Journal $journal) => [
                'journal_id' => $journal->id,
                'schedule_id' => $schedule->id,
                'date' => $journal->date->toDateString(),
                'class' => $schedule->class,
                'teacher' => $schedule->teacher,
                'subject' => $schedule->subject,
                'topic_description' => $journal->topic_description,
                'activity_notes' => $journal->activity_notes,
                'submitted_at' => $journal->created_at,
                'attendance_count' => $journal->attendances_count,
                'attendance_complete' => $journal->attendances_count === (int) ($expectedCounts[$schedule->class_id] ?? 0),
            ]);
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function journalResponse(Journal $journal, bool $replayed, int $status)
    {
        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil dibuat dan presensi berhasil disinkronkan',
            'replayed' => $replayed,
            'data' => $journal->load('attendances'),
        ], $status);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505', '19'], true);
    }

    private function validationError(array $errors)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $errors,
        ], 422);
    }
}
