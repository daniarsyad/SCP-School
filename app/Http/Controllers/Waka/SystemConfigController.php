<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\CycleHistory;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SystemConfigController extends Controller
{
    public function index()
    {
        $config = SystemConfiguration::current();
        return view('waka.system-config', compact('config'));
    }

    public function toggleCycle()
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Tidak bisa mengubah siklus karena jadwal sudah terkunci.');
        }

        // Simpan history putaran yang akan ditutup
        $this->saveCycleHistory($config);

        // Ganti putaran
        $newCycle = $config->active_cycle == '1' ? '2' : '1';

        $config->active_cycle = $newCycle;
        $config->start_date   = Carbon::today();
        $config->end_date     = Carbon::today()->addDays(13);
        $config->updated_by   = Auth::id();
        $config->save();

        return back()->with('success', 'Siklus berhasil diubah menjadi Putaran ' . $newCycle . ' ( ' . $config->start_date->format('d M Y') . ' - ' . $config->end_date->format('d M Y') . ' )');
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

    public function history()
    {
        $histories = CycleHistory::with('closedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $config = SystemConfiguration::current();

        return view('waka.cycle-history', compact('histories', 'config'));
    }

    public function historyDetail($id)
    {
        $history = CycleHistory::with('closedBy')->findOrFail($id);
        $config = SystemConfiguration::current();

        // Jurnal di periode tersebut
        $journals = Journal::with(['schedule.teacher', 'schedule.class', 'schedule.room'])
            ->whereHas('schedule', function ($q) use ($history) {
                $q->where('cycle_type', $history->cycle_type);
            })
            ->whereBetween('date', [$history->start_date, $history->end_date])
            ->orderBy('date')
            ->get();

        // Presensi dikelompokkan per kelas
        $attendances = Attendance::with(['student', 'journal.schedule.class'])
            ->whereIn('journal_id', $journals->pluck('id'))
            ->get()
            ->groupBy(function ($item) {
                return $item->journal->schedule->class->class_name ?? 'Tidak diketahui';
            });

        // Guru + kelas yang diajar
        $teachersData = Schedule::with(['teacher', 'class'])
            ->where('cycle_type', $history->cycle_type)
            ->whereHas('journals', function ($q) use ($history) {
                $q->whereBetween('date', [$history->start_date, $history->end_date]);
            })
            ->get()
            ->groupBy('teacher_id')
            ->map(function ($schedules) {
                $teacher = $schedules->first()->teacher;
                $classes = $schedules->pluck('class.class_name')->unique()->values();
                return [
                    'name'    => $teacher->fullname ?? '-',
                    'classes' => $classes,
                ];
            })
            ->values();

        return view('waka.cycle-history-detail', compact(
            'history', 'config', 'journals', 'attendances', 'teachersData'
        ));
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