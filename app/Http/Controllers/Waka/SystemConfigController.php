<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\CycleHistory;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Classes;
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

    public function toggleCycle()
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Tidak bisa mengubah siklus karena jadwal sudah terkunci.');
        }

        $this->saveCycleHistory($config);

        $newCycle = $config->active_cycle == '1' ? '2' : '1';

        $config->active_cycle = $newCycle;
        $config->start_date   = Carbon::today();
        $config->end_date     = Carbon::today()->addDays(13);
        $config->updated_by   = Auth::id();
        $config->save();

        return back()->with(
            'success',
            'Siklus berhasil diubah menjadi Putaran ' . $newCycle .
            ' ( ' . $config->start_date->format('d M Y') . ' - ' . $config->end_date->format('d M Y') . ' )'
        );
    }

    public function toggleLock()
    {
        $config = SystemConfiguration::current();

        $config->is_schedule_locked = !$config->is_schedule_locked;
        $config->updated_by = Auth::id();
        $config->save();

        $status = $config->is_schedule_locked ? 'DIKUNCI' : 'DIBUKA';
        return back()->with('success', 'Jadwal berhasil ' . $status);
    }

    public function history(Request $request)
    {
        $histories = CycleHistory::with('closedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $config = SystemConfiguration::current();

        $selectedId = $request->get('history_id', $histories->first()?->id);
        $selected = $histories->firstWhere('id', $selectedId);

        $tab = $request->get('tab', 'presensi');

        $classRecap = collect();
        $journals = collect();

        if ($selected) {
            $journals = Journal::with(['schedule.teacher', 'schedule.class'])
                ->whereHas('schedule', fn($q) => $q->where('cycle_type', $selected->cycle_type))
                ->whereBetween('date', [$selected->start_date, $selected->end_date])
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
            'histories', 'config', 'selected', 'tab', 'classRecap', 'journals'
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