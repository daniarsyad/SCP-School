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

        if ((! $request->user()->isGuru() && ! $request->user()->isWaliKelas()) ||
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::with('journal.schedule')->findOrFail($id);
        if ((! $request->user()->isGuru() && ! $request->user()->isWaliKelas()) ||
            (int) $attendance->journal->schedule->teacher_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak mengubah presensi ini.',
            ], 403);
        }

        $leaveSynchronizer->setTeacherStatus($attendance, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Status presensi diperbarui',
            'data' => $attendance->refresh()->load('student:id,fullname'),
        ]);
    }

    public function myAttendances(Request $request)
    {
        $user = $request->user();
        if ($user->isSiswa()) {
            $studentIds = [$user->id];
        } elseif ($user->isOrangTua()) {
            $studentIds = User::where('parent_id', $user->id)->pluck('id');
        } else {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $attendances = Attendance::with([
            'journal.schedule:id,subject,day_of_week,class_id,room_id',
            'student:id,fullname',
        ])
            ->whereIn('student_id', $studentIds)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $attendances]);
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
            'journal.schedule.class:id,class_name',
            'journal.schedule.class.homeroomTeacher:id,fullname,class_id',
            'journal.schedule.teacher:id,fullname',
            'journal.schedule.period:id,period_number,label',
        ])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString());

        if ($user->isWaliKelas()) {
            $query->whereHas('journal.schedule', fn ($schedule) => $schedule->where('class_id', $user->class_id));
        } elseif (isset($validated['class_id'])) {
            $query->whereHas('journal.schedule', fn ($schedule) => $schedule->where('class_id', $validated['class_id']));
        }

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

            return [
                'student_id' => $rows->first()->student_id,
                'student' => $rows->first()->student,
                'total_sessions' => $rows->count(),
                'hadir' => $rows->where('status', 'Hadir')->count(),
                'izin' => $rows->where('status', 'Izin')->count(),
                'sakit' => $rows->where('status', 'Sakit')->count(),
                'alpha' => $rows->where('status', 'Alpha')->count(),
                'alpha_consecutive_days' => $trailingStreak,
                'max_alpha_consecutive_days' => $maxStreak,
                'repeated_alpha' => $maxStreak > 3,
            ];
        })->keyBy('student_id');
        $totalRows = $data->count();

        if ($reportClassId !== null) {
            User::where('class_id', $reportClassId)
                ->whereHas('role', fn ($role) => $role->where('role_name', 'Siswa'))
                ->orderBy('fullname')
                ->get(['id', 'fullname'])
                ->each(function (User $student) use ($studentSummary) {
                    if (! $studentSummary->has($student->id)) {
                        $studentSummary->put($student->id, [
                            'student_id' => $student->id,
                            'student' => $student,
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
        $studentSummary = $studentSummary->sortBy(fn ($summary) => $summary['student']->fullname)->values();

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
