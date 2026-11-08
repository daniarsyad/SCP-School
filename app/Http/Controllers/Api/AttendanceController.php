<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\User;
use App\Services\AttendanceLeaveSynchronizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceController extends Controller
{
    public function byJournal(Request $request, $journalId)
    {
        $journal = Journal::with('schedule')->findOrFail($journalId);

        if ((! $request->user()->isGuru() && ! $request->user()->isWaliKelas() && ! $request->user()->isGuruBK()) ||
            (int) $journal->schedule->teacher_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak melihat presensi ini.',
            ], 403);
        }

        $attendances = Attendance::with(['student:id,fullname'])
            ->where('journal_id', $journalId)
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $attendances]);
    }

    public function updateStatus(
        Request $request,
        $id,
        AttendanceLeaveSynchronizer $leaveSynchronizer
    ) {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Hadir,Izin,Sakit,Alpha',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::with('journal.schedule')->findOrFail($id);
        if ((! $request->user()->isGuru() && ! $request->user()->isWaliKelas() && ! $request->user()->isGuruBK()) ||
            (int) $attendance->journal->schedule->teacher_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengubah presensi ini.',
            ], 403);
        }

        $leaveSynchronizer->setTeacherStatus($attendance, $request->status);
        if ($request->has('notes')) {
            $attendance->update(['notes' => $request->notes]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status presensi diperbarui',
            'data' => $attendance->refresh()->load('student:id,fullname'),
        ]);
    }

    public function myAttendances(Request $request)
    {
        $user = $request->user();
        if (! $user->isSiswa() && ! $user->isOrangTua()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->query(), [
            'student_id' => 'sometimes|required|integer',
            'start_date' => 'sometimes|nullable|date',
            'end_date'   => 'sometimes|nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $studentId = isset($validated['student_id']) ? (int) $validated['student_id'] : null;

        $query = Attendance::query();
        if ($user->isSiswa()) {
            if ($studentId !== null && $studentId !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya dapat melihat riwayat presensi sendiri.',
                ], 403);
            }

            $query->where('student_id', $user->id);
        } else {
            $user->syncChildrenParentId();
            $childrenQuery = $user->getChildrenQuery();

            if ($studentId !== null) {
                if (! (clone $childrenQuery)->whereKey($studentId)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Siswa tidak terhubung dengan akun Orang Tua ini.',
                    ], 403);
                }

                $query->where('student_id', $studentId);
            } else {
                $query->whereIn('student_id', $childrenQuery->select('id'));
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->query('end_date'));
        }

        $attendances = $query->with([
            'journal.schedule:id,subject,day_of_week,start_time,end_time,class_id,room_id',
            'journal.schedule.room:id,room_name',
            'student:id,fullname,class_id',
            'student.class:id,class_name',
        ])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $data = $attendances->map(function (Attendance $attendance) {
            $record = $attendance->toArray();
            $record['date'] = $attendance->date?->format('Y-m-d');
            if ($attendance->journal) {
                $record['journal']['date'] = $attendance->journal->date?->format('Y-m-d');
            }

            return $record;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function todaySummary(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $todayDayName = $now->locale('id')->isoFormat('dddd');
        $activeCycle = \App\Models\SystemConfig::current()?->active_cycle ?? '1';

        if ($user->isSiswa()) {
            $students = collect([$user]);
        } elseif ($user->isOrangTua()) {
            $user->syncChildrenParentId();
            $studentsQuery = $user->getChildrenQuery();

            if ($request->filled('student_id')) {
                $studentsQuery->whereKey($request->validate([
                    'student_id' => 'required|integer',
                ])['student_id']);
            }

            $students = $studentsQuery->orderBy('fullname')->get();
            if ($request->filled('student_id') && $students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa tidak terhubung dengan akun Orang Tua ini.',
                ], 403);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $data = $students->map(function (User $student) use ($today, $todayDayName, $activeCycle) {
            $counts = Attendance::where('student_id', $student->id)
                ->whereDate('date', $today)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
            $total = (int) $counts->sum();

            $schedules = $student->class_id
                ? \App\Models\Schedule::with([
                    'class:id,class_name',
                    'teacher:id,fullname',
                    'room:id,room_name',
                    'period:id,period_number,label,start_time,end_time',
                ])
                    ->where('class_id', $student->class_id)
                    ->where('cycle_type', $activeCycle)
                    ->where('day_of_week', $todayDayName)
                    ->orderBy('start_time')
                    ->get()
                : collect();

            return [
                'student' => [
                    'id' => $student->id,
                    'fullname' => $student->fullname,
                    'nis' => $student->nis,
                    'class_id' => $student->class_id,
                    'class' => $student->class?->class_name,
                ],
                'date' => $today,
                'total_sessions' => $total,
                'hadir' => (int) ($counts['Hadir'] ?? 0),
                'izin' => (int) ($counts['Izin'] ?? 0),
                'sakit' => (int) ($counts['Sakit'] ?? 0),
                'alpha' => (int) ($counts['Alpha'] ?? 0),
                'present_percentage' => $total === 0 ? 0 : round((($counts['Hadir'] ?? 0) / $total) * 100, 2),
                'schedules' => $schedules,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'date' => $today,
            'schedules' => $data->first()['schedules'] ?? [],
            'data' => $data,
        ]);
    }

    public function reportFilters(Request $request)
    {
        $user = $request->user();
        if (! $user->isWaliKelas() && ! $user->isGuruBK() && ! $user->hasPrimaryRole('Waka Kurikulum') && ! $user->isKepalaSekolah()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $bkClassIds = ($user->isGuruBK() && ! $user->isWaliKelas())
            ? $user->bkClasses()->pluck('classes.id')->all()
            : [];

        $classes = \App\Models\Classes::query()
            ->when($user->isWaliKelas(), fn ($query) => $query->whereKey($user->class_id))
            ->when(! empty($bkClassIds), fn ($query) => $query->whereIn('id', $bkClassIds))
            ->orderBy('grade')->orderBy('major')->orderBy('section')
            ->get(['id', 'class_name', 'grade', 'major', 'section']);
        $teachers = User::whereHas('role', fn ($query) => $query->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
            ->when($user->isWaliKelas(), fn ($query) => $query->whereHas(
                'schedules',
                fn ($schedule) => $schedule->where('class_id', $user->class_id)
            ))
            ->when(! empty($bkClassIds), fn ($query) => $query->whereHas(
                'schedules',
                fn ($schedule) => $schedule->whereIn('class_id', $bkClassIds)
            ))
            ->orderBy('fullname')
            ->get(['id', 'fullname']);
        $periods = \App\Models\Period::orderBy('period_number')
            ->get(['id', 'period_number', 'label', 'start_time', 'end_time']);

        return response()->json([
            'success' => true,
            'data' => [
                'classes' => $classes,
                'grades' => ['X', 'XI', 'XII'],
                'majors' => $classes->pluck('major')->filter()->unique()->values()->all(),
                'sections' => $classes->pluck('section')->filter()->unique()->values()->all(),
                'periods' => $periods,
                'teachers' => $teachers,
            ],
        ]);
    }

    public function report(Request $request)
    {
        $user = $request->user();
        if (! $user->isWaliKelas() && ! $user->isGuruBK()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'grade' => 'nullable|in:X,XI,XII',
            'major' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:10',
            'teacher_id' => 'nullable|exists:users,id',
            'from_date' => 'nullable|required_with:to_date|date',
            'to_date' => 'nullable|required_with:from_date|date|after_or_equal:from_date',
            'month' => 'nullable|date_format:Y-m',
            'period_id' => 'nullable|integer|exists:periods,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if (isset($validated['month']) &&
            (isset($validated['date']) || isset($validated['from_date']) || isset($validated['to_date']))) {
            return response()->json([
                'success' => false,
                'message' => 'Gunakan month atau filter tanggal, tidak keduanya.',
            ], 422);
        }

        if (isset($validated['date']) &&
            (isset($validated['from_date']) || isset($validated['to_date']))) {
            return response()->json([
                'success' => false,
                'message' => 'Gunakan date atau from_date/to_date, tidak keduanya.',
            ], 422);
        }

        if ($user->isWaliKelas() && ! $user->class_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Wali Kelas belum terhubung ke kelas.',
            ], 422);
        }

        if ($user->isWaliKelas() && isset($validated['class_id']) &&
            (int) $validated['class_id'] !== (int) $user->class_id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak mengakses kelas tersebut.'], 403);
        }

        if (isset($validated['month'])) {
            $from = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } else {
            $date = $validated['date'] ?? Carbon::today()->toDateString();
            $from = Carbon::parse($validated['from_date'] ?? $date)->startOfDay();
            $to = Carbon::parse($validated['to_date'] ?? $date)->endOfDay();
        }

        $query = Attendance::with([
            'student:id,fullname',
            'journal:id,schedule_id,date',
            'journal.schedule:id,subject,class_id,teacher_id,period_id',
            'journal.schedule.class:id,class_name,grade,major,section',
            'journal.schedule.class.homeroomTeacher:id,fullname,class_id',
            'journal.schedule.teacher:id,fullname',
            'journal.schedule.period:id,period_number,label',
        ])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString());

        if ($user->isWaliKelas()) {
            $query->whereHas('journal.schedule', fn ($schedule) => $schedule->where('class_id', $user->class_id));
        }

        $query->whereHas('journal.schedule', function ($schedule) use ($validated, $user) {
            $schedule->when(isset($validated['class_id']), fn ($q) => $q->where('class_id', $validated['class_id']))
                ->when(isset($validated['teacher_id']), fn ($q) => $q->where('teacher_id', $validated['teacher_id']))
                ->whereHas('class', fn ($class) => $class
                    ->when(isset($validated['grade']), fn ($q) => $q->where('grade', $validated['grade']))
                    ->when(isset($validated['major']), fn ($q) => $q->where('major', $validated['major']))
                    ->when(isset($validated['section']), fn ($q) => $q->where('section', $validated['section'])));
        });

        if (isset($validated['period_id'])) {
            $query->whereHas('journal.schedule', fn ($schedule) => $schedule->where('period_id', $validated['period_id']));
        }

        $data = $query->orderBy('date')->orderBy('id')->get();
        $data->each(function (Attendance $attendance) {
            $attendance->journal->schedule->class->homeroomTeacher?->makeHidden('class_id');
        });
        $statusSummary = $data->countBy('status');
        $reportClassId = $user->isWaliKelas()
            ? (int) $user->class_id
            : (isset($validated['class_id']) ? (int) $validated['class_id'] : null);
        $studentSummary = $data->groupBy('student_id')->map(function ($rows) use ($to) {
            $alphaDates = $rows->where('status', 'Alpha')
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->sort()
                ->values();

            $maxStreak = 0;
            $currentStreak = 0;
            $previousDate = null;
            foreach ($alphaDates as $alphaDate) {
                $currentStreak = $previousDate !== null &&
                    Carbon::parse($previousDate)->addDay()->toDateString() === $alphaDate
                    ? $currentStreak + 1
                    : 1;
                $maxStreak = max($maxStreak, $currentStreak);
                $previousDate = $alphaDate;
            }
            $trailingStreak = $previousDate === $to->toDateString() ? $currentStreak : 0;

            $student = $rows->first()->student;
            return [
                'student_id' => $rows->first()->student_id,
                'student' => $student,
                'fullname' => $student?->fullname ?? '',
                'name' => $student?->fullname ?? '',
                'student_name' => $student?->fullname ?? '',
                'total_sessions' => $rows->count(),
                'hadir' => $rows->where('status', 'Hadir')->count(),
                'izin' => $rows->where('status', 'Izin')->count(),
                'sakit' => $rows->where('status', 'Sakit')->count(),
                'alpha' => $rows->where('status', 'Alpha')->count(),
                'alpha_consecutive_days' => $trailingStreak,
                'max_alpha_consecutive_days' => $maxStreak,
                'consecutive_alpha' => $maxStreak,
                'repeated_alpha' => $maxStreak > 3,
            ];
        })->keyBy('student_id');
        $totalRows = $data->count();

        if ($reportClassId !== null) {
            User::where('class_id', $reportClassId)
                ->whereHas('role', fn ($role) => $role->where('role_name', 'Siswa'))
                ->where('is_alumni', false)
                ->orderBy('fullname')
                ->get(['id', 'fullname'])
                ->each(function (User $student) use ($studentSummary) {
                    if (! $studentSummary->has($student->id)) {
                        $studentSummary->put($student->id, [
                            'student_id' => $student->id,
                            'student' => $student,
                            'fullname' => $student->fullname,
                            'name' => $student->fullname,
                            'student_name' => $student->fullname,
                            'total_sessions' => 0,
                            'hadir' => 0,
                            'izin' => 0,
                            'sakit' => 0,
                            'alpha' => 0,
                            'alpha_consecutive_days' => 0,
                            'max_alpha_consecutive_days' => 0,
                            'repeated_alpha' => false,
                        ]);
                    }
                });
        }

        $totalStudents = $reportClassId === null
            ? $data->pluck('student_id')->unique()->count()
            : $studentSummary->count();
        $studentSummary = $studentSummary->sortBy(function ($summary) {
            $name = $summary['student']?->fullname ?? $summary['fullname'] ?? '';
            return strtolower(ltrim(trim((string) $name), "'\"`"));
        })->values();

        return response()->json([
            'success' => true,
            'date' => $from->isSameDay($to) ? $from->toDateString() : null,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'summary' => [
                'total_students' => $totalStudents,
                'total_sessions' => $totalRows,
                'hadir' => (int) ($statusSummary['Hadir'] ?? 0),
                'izin' => (int) ($statusSummary['Izin'] ?? 0),
                'sakit' => (int) ($statusSummary['Sakit'] ?? 0),
                'alpha' => (int) ($statusSummary['Alpha'] ?? 0),
                'present_percentage' => $totalRows === 0
                    ? 0
                    : round((($statusSummary['Hadir'] ?? 0) / $totalRows) * 100, 2),
            ],
            'student_summary' => $studentSummary,
            'data' => $data,
        ]);
    }

    public function exportReport(Request $request)
    {
        $reportResponse = $this->report($request);
        if ($reportResponse->getStatusCode() !== 200) {
            return $reportResponse;
        }

        $report = $reportResponse->getData(true);
        $spreadsheet = new Spreadsheet;
        $details = $spreadsheet->getActiveSheet();
        $details->setTitle('Presensi');
        $this->writeTextRow($details, 1, [
            'Tanggal',
            'Kelas',
            'Siswa',
            'Mata Pelajaran',
            'Guru',
            'Jam Pelajaran',
            'Status',
        ]);

        foreach ($report['data'] as $index => $attendance) {
            $schedule = $attendance['journal']['schedule'] ?? [];
            $this->writeTextRow($details, $index + 2, [
                $attendance['date'] ?? '',
                $schedule['class']['class_name'] ?? '',
                $attendance['student']['fullname'] ?? '',
                $schedule['subject'] ?? '',
                $schedule['teacher']['fullname'] ?? '',
                $schedule['period']['label'] ?? '',
                $attendance['status'] ?? '',
            ]);
        }

        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Rekap');
        $this->writeTextRow($summary, 1, ['Rekap Presensi', $report['from_date'].' s.d. '.$report['to_date']]);
        $this->writeTextRow($summary, 2, ['Total siswa', $report['summary']['total_students']]);
        $this->writeTextRow($summary, 3, ['Total catatan presensi', $report['summary']['total_sessions']]);
        $this->writeTextRow($summary, 4, ['Hadir', $report['summary']['hadir']]);
        $this->writeTextRow($summary, 5, ['Izin', $report['summary']['izin']]);
        $this->writeTextRow($summary, 6, ['Sakit', $report['summary']['sakit']]);
        $this->writeTextRow($summary, 7, ['Alpha', $report['summary']['alpha']]);
        $this->writeTextRow($summary, 8, ['Persentase hadir', $report['summary']['present_percentage'].'%']);
        $this->writeTextRow($summary, 10, [
            'Nama siswa',
            'Total sesi',
            'Hadir',
            'Izin',
            'Sakit',
            'Alpha',
            'Alpha berturut-turut',
            'Alpha berulang (>3 hari)',
        ]);

        foreach ($report['student_summary'] as $index => $student) {
            $this->writeTextRow($summary, $index + 11, [
                $student['student']['fullname'] ?? '',
                $student['total_sessions'],
                $student['hadir'],
                $student['izin'],
                $student['sakit'],
                $student['alpha'],
                $student['max_alpha_consecutive_days'],
                $student['repeated_alpha'] ? 'Ya' : 'Tidak',
            ]);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'rekap-presensi-'.$report['from_date'].'-'.$report['to_date'].'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function writeTextRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValueExplicit(
                $column.$row,
                (string) ($value ?? ''),
                DataType::TYPE_STRING
            );
        }
    }
}
