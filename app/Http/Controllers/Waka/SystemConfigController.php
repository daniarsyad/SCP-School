<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\CycleHistory;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Classes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SystemConfigController extends Controller
{
    public function index()
    {
        $config = SystemConfiguration::current();
        $this->ensureCurrentPeriod($config);

        return view('waka.system-config', compact('config'));
    }

    // Pergantian siklus KBM dua mingguan (Putaran 1 dan Putaran 2)
    public function toggleCycle()
    {
        $config = SystemConfiguration::current();

        if ($config->is_cycle_1_locked && $config->is_cycle_2_locked) {
            return back()->with('error', 'Tidak bisa mengubah siklus karena kedua putaran jadwal sedang terkunci.');
        }

        $this->saveCycleHistory($config);

        $newCycle = $config->active_cycle == '1' ? '2' : '1';

        $config->active_cycle = $newCycle;
        $config->start_date   = Carbon::today();
        $config->end_date     = Carbon::today()->addDays(13);
        $config->is_schedule_locked = $config->isCycleLocked($newCycle);
        $config->updated_by   = Auth::id();
        $config->save();

        return back()->with(
            'success',
            'Siklus berhasil diubah menjadi Putaran ' . $newCycle .
            ' ( ' . $config->start_date->format('d M Y') . ' - ' . $config->end_date->format('d M Y') . ' )'
        );
    }

    // Penguncian jadwal KBM (kunci atau buka jadwal per putaran)
    public function toggleLock(Request $request)
    {
        $config = SystemConfiguration::current();
        $cycle = (string) $request->input('cycle', '');

        if ($cycle === '1') {
            $config->is_cycle_1_locked = !$config->isCycleLocked('1');
            $status = $config->is_cycle_1_locked ? 'DIKUNCI' : 'DIBUKA';
            $label = 'Putaran 1';
        } elseif ($cycle === '2') {
            $config->is_cycle_2_locked = !$config->isCycleLocked('2');
            $status = $config->is_cycle_2_locked ? 'DIKUNCI' : 'DIBUKA';
            $label = 'Putaran 2';
        } else {
            $target = (string) $config->active_cycle;
            if ($target === '1') {
                $config->is_cycle_1_locked = !$config->isCycleLocked('1');
                $status = $config->is_cycle_1_locked ? 'DIKUNCI' : 'DIBUKA';
                $label = 'Putaran 1';
            } else {
                $config->is_cycle_2_locked = !$config->isCycleLocked('2');
                $status = $config->is_cycle_2_locked ? 'DIKUNCI' : 'DIBUKA';
                $label = 'Putaran 2';
            }
        }

        $config->is_schedule_locked = $config->isCycleLocked($config->active_cycle);
        $config->updated_by = Auth::id();
        $config->save();

        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        return back()->with('success', "Jadwal {$label} berhasil {$status}.");
    }

    // Bersihkan cache tampilan dan optimasi sistem
    public function clearCache()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            return back()->with('success', 'Cache sistem dan tampilan berhasil dibersihkan.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membersihkan cache: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $filters = $request->validate([
            'history_id' => 'nullable|integer|exists:cycle_histories,id',
            'tab' => 'nullable|in:presensi,jurnal',
            'date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'grade' => 'nullable|in:X,XI,XII',
            'major' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:10',
            'teacher_id' => 'nullable|exists:users,id',
        ]);
        $histories = CycleHistory::with('closedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $config = SystemConfiguration::current();

        $selectedId = $filters['history_id'] ?? $histories->first()?->id;
        $selected = $histories->firstWhere('id', $selectedId);

        $tab = $filters['tab'] ?? 'presensi';

        $classRecap = collect();
        $journals = collect();
        $classes = Classes::orderBy('grade')->orderBy('major')->orderBy('section')->get();
        $teachers = \App\Models\User::whereHas('role', fn ($query) => $query->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
            ->orderBy('fullname')
            ->get(['id', 'fullname']);
        $dates = collect();

        if ($selected) {
            $cycleJournals = Journal::whereHas('schedule', fn ($query) => $query->where('cycle_type', $selected->cycle_type))
                ->whereBetween('date', [$selected->start_date, $selected->end_date]);
            $dates = (clone $cycleJournals)->distinct()->orderBy('date')->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->values();

            $journals = Journal::with(['schedule.teacher', 'schedule.class'])
                ->whereHas('schedule', fn($q) => $q->where('cycle_type', $selected->cycle_type))
                ->whereBetween('date', [$selected->start_date, $selected->end_date])
                ->when(isset($filters['date']), fn ($query) => $query->whereDate('date', $filters['date']))
                ->whereHas('schedule', function ($query) use ($filters) {
                    $query->when(isset($filters['teacher_id']), fn ($q) => $q->where('teacher_id', $filters['teacher_id']))
                        ->when(isset($filters['class_id']), fn ($q) => $q->where('class_id', $filters['class_id']))
                        ->whereHas('class', fn ($q) => $q
                            ->when(isset($filters['grade']), fn ($classQuery) => $classQuery->where('grade', $filters['grade']))
                            ->when(isset($filters['major']), fn ($classQuery) => $classQuery->where('major', $filters['major']))
                            ->when(isset($filters['section']), fn ($classQuery) => $classQuery->where('section', $filters['section'])));
                })
                ->get();

            $classRecap = $journals
                ->groupBy(fn($j) => $j->schedule->class_id)
                ->map(function ($items) {
                    $class = $items->first()->schedule->class;
                    $journalIds = $items->pluck('id');
                    $att = Attendance::whereIn('journal_id', $journalIds)->get();

                    return [
                        'class_id'   => $class->id ?? 0,
                        'class_name' => $class->class_name ?? '-',
                        'dates'      => $items->pluck('date')
                            ->map(fn ($date) => $date->format('Y-m-d'))
                            ->unique()
                            ->sort()
                            ->values()
                            ->map(fn ($date) => Carbon::parse($date)->format('d M Y')),
                        'journals'   => $items->count(),
                        'hadir'      => $att->where('status', 'Hadir')->count(),
                        'izin'       => $att->where('status', 'Izin')->count(),
                        'alpha'      => $att->where('status', 'Alpha')->count(),
                        'sakit'      => $att->where('status', 'Sakit')->count(),
                    ];
                })
                ->sortBy('class_name')
                ->values();
        }

        return view('waka.cycle-history', compact(
            'histories', 'config', 'selected', 'tab', 'classRecap', 'journals', 'classes', 'teachers', 'dates'
        ));
    }

    public function historyDetail($id)
    {
        $history = CycleHistory::with('closedBy')->findOrFail($id);
        $config = SystemConfiguration::current();

        $journals = Journal::with(['schedule.teacher', 'schedule.class'])
            ->whereHas('schedule', function ($q) use ($history) {
                $q->where('cycle_type', $history->cycle_type);
            })
            ->whereBetween('date', [$history->start_date, $history->end_date])
            ->get();

        $classRecap = $journals
            ->groupBy(fn ($j) => $j->schedule->class_id)
            ->map(function ($items) {
                $class = $items->first()->schedule->class;
                $journalIds = $items->pluck('id');
                $att = Attendance::whereIn('journal_id', $journalIds)->get();

                return [
                    'class_id'   => $class->id ?? 0,
                    'class_name' => $class->class_name ?? 'Tidak diketahui',
                    'journals'   => $items->count(),
                    'hadir'      => $att->where('status', 'Hadir')->count(),
                    'izin'       => $att->where('status', 'Izin')->count(),
                    'alpha'      => $att->where('status', 'Alpha')->count(),
                    'total_att'  => $att->count(),
                ];
            })
            ->sortBy('class_name')
            ->values();

        $teacherRecap = $journals
            ->groupBy(fn ($j) => $j->schedule->teacher_id)
            ->map(function ($items) {
                $teacher = $items->first()->schedule->teacher;
                $classes = $items->pluck('schedule.class.class_name')->unique()->filter()->values();

                return [
                    'name'     => $teacher->fullname ?? '-',
                    'journals' => $items->count(),
                    'classes'  => $classes,
                ];
            })
            ->sortBy('name')
            ->values();

        return view('waka.cycle-history-detail', compact(
            'history', 'config', 'classRecap', 'teacherRecap'
        ));
    }

    public function historyByClass($id, $classId)
    {
        $history = CycleHistory::findOrFail($id);
        $class = Classes::findOrFail($classId);
        $config = SystemConfiguration::current();

        $journals = Journal::with(['schedule.teacher', 'schedule.room', 'attendances.student'])
            ->whereHas('schedule', function ($q) use ($history, $classId) {
                $q->where('cycle_type', $history->cycle_type)
                  ->where('class_id', $classId);
            })
            ->whereBetween('date', [$history->start_date, $history->end_date])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('waka.cycle-history-class', compact(
            'history', 'class', 'config', 'journals'
        ));
    }

    // Cetak dan ekspor laporan rekapitulasi presensi siklus ke format PDF
    public function printCycleAttendance(Request $request, $id)
    {
        $history = CycleHistory::findOrFail($id);
        $filters = $request->validate([
            'date' => 'nullable|date',
            'grade' => 'nullable|in:X,XI,XII',
            'major' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:10',
            'class_id' => 'nullable|exists:classes,id',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $appliedFilters = [];
        if (!empty($filters['date'])) $appliedFilters[] = 'Tanggal: ' . Carbon::parse($filters['date'])->format('d/m/Y');
        if (!empty($filters['grade'])) $appliedFilters[] = 'Tingkat ' . $filters['grade'];
        if (!empty($filters['major'])) $appliedFilters[] = 'Jurusan ' . $filters['major'];
        if (!empty($filters['section'])) $appliedFilters[] = 'Rombel ' . $filters['section'];

        $journals = Journal::with(['schedule.teacher', 'schedule.class'])
            ->whereHas('schedule', fn($q) => $q->where('cycle_type', $history->cycle_type))
            ->whereBetween('date', [$history->start_date, $history->end_date])
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('date', $filters['date']))
            ->whereHas('schedule', function ($query) use ($filters) {
                $query->when(isset($filters['teacher_id']), fn ($q) => $q->where('teacher_id', $filters['teacher_id']))
                    ->when(isset($filters['class_id']), fn ($q) => $q->where('class_id', $filters['class_id']))
                    ->whereHas('class', fn ($q) => $q
                        ->when(isset($filters['grade']), fn ($classQuery) => $classQuery->where('grade', $filters['grade']))
                        ->when(isset($filters['major']), fn ($classQuery) => $classQuery->where('major', $filters['major']))
                        ->when(isset($filters['section']), fn ($classQuery) => $classQuery->where('section', $filters['section'])));
            })
            ->get();

        $classRecap = $journals
            ->groupBy(fn($j) => $j->schedule->class_id)
            ->map(function ($items) {
                $class = $items->first()->schedule->class;
                $journalIds = $items->pluck('id');
                $att = Attendance::whereIn('journal_id', $journalIds)->get();

                return [
                    'class_id'   => $class->id ?? 0,
                    'class_name' => $class->class_name ?? '-',
                    'dates'      => $items->pluck('date')
                        ->map(fn ($date) => $date->format('Y-m-d'))
                        ->unique()
                        ->sort()
                        ->values()
                        ->map(fn ($date) => Carbon::parse($date)->format('d M Y')),
                    'journals'   => $items->count(),
                    'hadir'      => $att->where('status', 'Hadir')->count(),
                    'izin'       => $att->where('status', 'Izin')->count(),
                    'alpha'      => $att->where('status', 'Alpha')->count(),
                    'sakit'      => $att->where('status', 'Sakit')->count(),
                ];
            })
            ->sortBy('class_name')
            ->values();

        return view('waka.print.cycle-attendance-summary', compact(
            'history', 'classRecap', 'appliedFilters'
        ));
    }

    // Cetak dan ekspor laporan rekapitulasi presensi kelas per siklus ke format PDF
    public function printClassCycleAttendance($id, $classId)
    {
        $history = CycleHistory::findOrFail($id);
        $class = Classes::with('homeroomTeacher')->findOrFail($classId);

        $students = User::where('class_id', $classId)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->orderBy('fullname')
            ->get();

        $studentIds = $students->pluck('id');

        $attendancesGrouped = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$history->start_date, $history->end_date])
            ->selectRaw('student_id, status, count(*) as total')
            ->groupBy('student_id', 'status')
            ->get();

        $studentStats = [];
        foreach ($students as $student) {
            $att = $attendancesGrouped->where('student_id', $student->id);
            $hadir = (int) ($att->firstWhere('status', 'Hadir')->total ?? 0);
            $izin = (int) ($att->firstWhere('status', 'Izin')->total ?? 0);
            $sakit = (int) ($att->firstWhere('status', 'Sakit')->total ?? 0);
            $alpha = (int) ($att->firstWhere('status', 'Alpha')->total ?? 0);
            $total = $hadir + $izin + $sakit + $alpha;
            $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

            $studentStats[$student->id] = [
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'total' => $total,
                'percentage' => $percentage,
            ];
        }

        $classTotalHadir = (int) $attendancesGrouped->where('status', 'Hadir')->sum('total');
        $classTotalIzin = (int) $attendancesGrouped->where('status', 'Izin')->sum('total');
        $classTotalSakit = (int) $attendancesGrouped->where('status', 'Sakit')->sum('total');
        $classTotalAlpha = (int) $attendancesGrouped->where('status', 'Alpha')->sum('total');
        $classTotalPresensi = $classTotalHadir + $classTotalIzin + $classTotalSakit + $classTotalAlpha;
        $classAttendanceRate = $classTotalPresensi > 0
            ? round(($classTotalHadir / $classTotalPresensi) * 100, 1)
            : 0;

        $periodText = 'Putaran ' . $history->cycle_type . ' (' . $history->start_date->translatedFormat('d F Y') . ' – ' . $history->end_date->translatedFormat('d F Y') . ')';

        return view('waka.print.class-attendance', compact(
            'class',
            'history',
            'students',
            'studentStats',
            'classTotalHadir',
            'classTotalIzin',
            'classTotalSakit',
            'classTotalAlpha',
            'classTotalPresensi',
            'classAttendanceRate',
            'periodText'
        ));
    }

    // Cetak dan ekspor laporan rekapitulasi jurnal KBM siklus ke format PDF
    public function printCycleJournals(Request $request, $id)
    {
        $history = CycleHistory::findOrFail($id);
        $filters = $request->validate([
            'date' => 'nullable|date',
            'grade' => 'nullable|in:X,XI,XII',
            'major' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:10',
            'class_id' => 'nullable|exists:classes,id',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $appliedFilters = [];
        if (!empty($filters['date'])) $appliedFilters[] = 'Tanggal: ' . Carbon::parse($filters['date'])->format('d/m/Y');
        if (!empty($filters['grade'])) $appliedFilters[] = 'Tingkat ' . $filters['grade'];
        if (!empty($filters['major'])) $appliedFilters[] = 'Jurusan ' . $filters['major'];
        if (!empty($filters['section'])) $appliedFilters[] = 'Rombel ' . $filters['section'];

        $journals = Journal::with(['schedule.teacher', 'schedule.class', 'schedule.room', 'attendances'])
            ->whereHas('schedule', fn($q) => $q->where('cycle_type', $history->cycle_type))
            ->whereBetween('date', [$history->start_date, $history->end_date])
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('date', $filters['date']))
            ->whereHas('schedule', function ($query) use ($filters) {
                $query->when(isset($filters['teacher_id']), fn ($q) => $q->where('teacher_id', $filters['teacher_id']))
                    ->when(isset($filters['class_id']), fn ($q) => $q->where('class_id', $filters['class_id']))
                    ->whereHas('class', fn ($q) => $q
                        ->when(isset($filters['grade']), fn ($classQuery) => $classQuery->where('grade', $filters['grade']))
                        ->when(isset($filters['major']), fn ($classQuery) => $classQuery->where('major', $filters['major']))
                        ->when(isset($filters['section']), fn ($classQuery) => $classQuery->where('section', $filters['section'])));
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $periodText = 'Putaran ' . $history->cycle_type . ' (' . $history->start_date->translatedFormat('d F Y') . ' – ' . $history->end_date->translatedFormat('d F Y') . ')';
        $docSubtitle = 'Rekapitulasi Jurnal Mengajar Guru pada Putaran ' . $history->cycle_type;

        return view('waka.print.journals', compact(
            'history',
            'journals',
            'periodText',
            'docSubtitle',
            'appliedFilters'
        ));
    }

    private function ensureCurrentPeriod($config)
    {
        if (!$config->end_date || Carbon::today()->gt($config->end_date)) {
            $config->start_date = Carbon::today();
            $config->end_date   = Carbon::today()->addDays(13);
            $config->save();
        }
    }

    private function saveCycleHistory($config)
    {
        $journals = Journal::whereHas('schedule', function ($q) use ($config) {
            $q->where('cycle_type', $config->active_cycle);
        })
        ->whereBetween('date', [$config->start_date, $config->end_date])
        ->get();

        $totalJournals = $journals->count();
        $totalAttendances = Attendance::whereIn('journal_id', $journals->pluck('id'))->count();

        $teacherIds = Schedule::where('cycle_type', $config->active_cycle)
            ->whereHas('journals', function ($q) use ($config) {
                $q->whereBetween('date', [$config->start_date, $config->end_date]);
            })
            ->pluck('teacher_id')
            ->unique();

        $teachers = \App\Models\User::whereIn('id', $teacherIds)
            ->pluck('fullname')
            ->toArray();

        CycleHistory::create([
            'cycle_type'        => $config->active_cycle,
            'start_date'        => $config->start_date ?? Carbon::today()->subDays(13),
            'end_date'          => $config->end_date ?? Carbon::today(),
            'total_journals'    => $totalJournals,
            'total_attendances' => $totalAttendances,
            'teachers'          => $teachers,
            'closed_by'         => Auth::id(),
        ]);
    }
}