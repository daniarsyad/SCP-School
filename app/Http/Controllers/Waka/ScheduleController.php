<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Period;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $config = SystemConfiguration::current();

        $cycle = $request->get('cycle', $config->active_cycle);
        $dayFilter = $request->get('day');
        $classFilter = $request->get('class_id');

        $query = Schedule::with(['teacher', 'class', 'room', 'period'])
            ->where('cycle_type', $cycle);

        if ($dayFilter) {
            $query->where('day_of_week', $dayFilter);
        }

        if ($classFilter) {
            $query->where('class_id', $classFilter);
        }

        if ($request->filled('grade') || $request->filled('major') || $request->filled('section')) {
            $query->whereHas('class', function ($q) use ($request) {
                if ($request->filled('grade')) {
                    $q->where('grade', $request->grade);
                }
                if ($request->filled('major')) {
                    $q->where('major', $request->major);
                }
                if ($request->filled('section')) {
                    $q->where('section', $request->section);
                }
            });
        }

        $schedules = $query->orderBy('start_time')->get();

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $times = $schedules->pluck('start_time')->unique()->sort()->values();

        $matrix = [];
        foreach ($times as $time) {
            $matrix[$time] = [];
            foreach ($days as $day) {
                $matrix[$time][$day] = $schedules
                    ->where('start_time', $time)
                    ->where('day_of_week', $day)
                    ->values();
            }
        }

        $teachers = User::with('role')->whereHas('role', function ($q) {
            $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']);
        })->orderBy('fullname')->get();

        $classesQuery = Classes::query();
        if ($request->filled('grade')) {
            $classesQuery->where('grade', $request->grade);
        }
        if ($request->filled('major')) {
            $classesQuery->where('major', $request->major);
        }
        if ($request->filled('section')) {
            $classesQuery->where('section', $request->section);
        }
        $classes = $classesQuery->orderBy('class_name')->get();

        $rooms = Room::orderBy('room_name')->get();

        $all = Period::orderBy('start_time')->get();
        $learn = $all->where('is_break', false)->values();

        $periodsByDay = [
            'Senin'  => $learn->where('day_group', 'senin')->values(),
            'Selasa' => $learn->where('day_group', 'selasa')->values(),
            'Rabu'   => $learn->where('day_group', 'rabu_kamis')->values(),
            'Kamis'  => $learn->where('day_group', 'rabu_kamis')->values(),
            'Jumat'  => $learn->where('day_group', 'jumat')->values(),
        ];

        $periodsByDayJson = [];
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day) {
            $periodsByDayJson[$day] = $periodsByDay[$day]->map(function ($p) {
                return [
                    'id'    => $p->id,
                    'label' => $p->label,
                    'start' => substr((string) $p->start_time, 0, 5),
                    'end'   => substr((string) $p->end_time, 0, 5),
                ];
            })->values()->all();
        }

        return view('waka.schedules.index', compact(
            'schedules', 'config', 'teachers', 'classes', 'rooms',
            'matrix', 'days', 'cycle', 'dayFilter', 'classFilter',
            'periodsByDayJson'
        ));
    }

    public function store(Request $request)
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang terkunci. Tidak bisa menambah jadwal.');
        }

        $request->validate([
            'teacher_id'      => 'required|exists:users,id',
            'class_id'        => 'required|exists:classes,id',
            'room_id'         => 'required|exists:rooms,id',
            'subject'         => 'required|string|max:100',
            'day_of_week'     => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'period_start_id' => 'required|exists:periods,id',
            'period_end_id'   => 'required|exists:periods,id',
            'cycle_type'      => 'required|in:1,2',
        ]);

        $startPeriod = Period::findOrFail($request->period_start_id);
        $endPeriod   = Period::findOrFail($request->period_end_id);

        if ($startPeriod->is_break || $endPeriod->is_break) {
            return back()->with('error', 'Tidak bisa memilih slot istirahat.')->withInput();
        }

        if ($startPeriod->start_time > $endPeriod->start_time) {
            return back()->with('error', 'Jam selesai harus sama atau setelah jam mulai.')->withInput();
        }

        $start = substr((string) $startPeriod->start_time, 0, 5);
        $end   = substr((string) $endPeriod->end_time, 0, 5);

        $conflict = $this->checkConflict(
            $request->cycle_type,
            $request->day_of_week,
            $start,
            $end,
            $request->teacher_id,
            $request->class_id,
            $request->room_id
        );

        if ($conflict) {
            return back()->with('error', $conflict)->withInput();
        }

        Schedule::create([
            'teacher_id'  => $request->teacher_id,
            'class_id'    => $request->class_id,
            'room_id'     => $request->room_id,
            'subject'     => $request->subject,
            'day_of_week' => $request->day_of_week,
            'start_time'  => $start,
            'end_time'    => $end,
            'period_id'   => $startPeriod->id,
            'cycle_type'  => $request->cycle_type,
        ]);

        $label = $startPeriod->id === $endPeriod->id
            ? $startPeriod->label
            : $startPeriod->label . ' – ' . $endPeriod->label;

        return back()->with('success', "Jadwal berhasil ditambahkan ke Putaran {$request->cycle_type} ({$label}: {$start} – {$end}).");
    }

    public function update(Request $request, $id)
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang terkunci. Tidak bisa mengubah jadwal.');
        }

        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'teacher_id'      => 'required|exists:users,id',
            'class_id'        => 'required|exists:classes,id',
            'room_id'         => 'required|exists:rooms,id',
            'subject'         => 'required|string|max:100',
            'day_of_week'     => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'period_start_id' => 'required|exists:periods,id',
            'period_end_id'   => 'required|exists:periods,id',
            'cycle_type'      => 'required|in:1,2',
        ]);

        $startPeriod = Period::findOrFail($request->period_start_id);
        $endPeriod   = Period::findOrFail($request->period_end_id);

        if ($startPeriod->is_break || $endPeriod->is_break) {
            return back()->with('error', 'Tidak bisa memilih slot istirahat.')->withInput();
        }

        if ($startPeriod->start_time > $endPeriod->start_time) {
            return back()->with('error', 'Jam selesai harus sama atau setelah jam mulai.')->withInput();
        }

        $start = substr((string) $startPeriod->start_time, 0, 5);
        $end   = substr((string) $endPeriod->end_time, 0, 5);

        $conflict = $this->checkConflict(
            $request->cycle_type,
            $request->day_of_week,
            $start,
            $end,
            $request->teacher_id,
            $request->class_id,
            $request->room_id,
            $schedule->id
        );

        if ($conflict) {
            return back()->with('error', $conflict)->withInput();
        }

        $schedule->update([
            'teacher_id'  => $request->teacher_id,
            'class_id'    => $request->class_id,
            'room_id'     => $request->room_id,
            'subject'     => $request->subject,
            'day_of_week' => $request->day_of_week,
            'start_time'  => $start,
            'end_time'    => $end,
            'period_id'   => $startPeriod->id,
            'cycle_type'  => $request->cycle_type,
        ]);

        $label = $startPeriod->id === $endPeriod->id
            ? $startPeriod->label
            : $startPeriod->label . ' – ' . $endPeriod->label;

        return back()->with('success', "Jadwal berhasil diupdate ({$label}: {$start} – {$end}).");
    }

    public function destroy($id)
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang terkunci. Tidak bisa menghapus jadwal.');
        }

        Schedule::findOrFail($id)->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function journalRecap()
    {
        $config = SystemConfiguration::current();
        $today = Carbon::now()->locale('id')->isoFormat('dddd');
        $todayDate = Carbon::today()->toDateString();
        $now = Carbon::now();

        $todaySchedules = Schedule::with(['teacher', 'class', 'room', 'period', 'journals'])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        $sudahIsi = $todaySchedules->filter(function ($s) use ($todayDate) {
            return $s->journals->contains(function ($j) use ($todayDate) {
                return Carbon::parse($j->date)->toDateString() === $todayDate;
            });
        });

        $belumIsi = $todaySchedules->filter(function ($s) use ($todayDate) {
            return !$s->journals->contains(function ($j) use ($todayDate) {
                return Carbon::parse($j->date)->toDateString() === $todayDate;
            });
        });

        $terlambat = $belumIsi->filter(function ($s) use ($now) {
            $endTime = Carbon::parse($s->end_time);
            return $now->gt($endTime->copy()->addMinutes(30));
        });

        return view('waka.schedules.journal-recap', compact(
            'config',
            'today',
            'todaySchedules',
            'sudahIsi',
            'belumIsi',
            'terlambat'
        ));
    }

    private function checkConflict($cycle, $day, $start, $end, $teacherId, $classId, $roomId, $excludeId = null)
    {
        $query = Schedule::where('cycle_type', $cycle)
            ->where('day_of_week', $day)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->where(function ($q) use ($teacherId, $classId, $roomId) {
                $q->where('teacher_id', $teacherId)
                  ->orWhere('class_id', $classId)
                  ->orWhere('room_id', $roomId);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflict = $query->with(['teacher', 'class', 'room'])->first();

        if (!$conflict) {
            return null;
        }

        $reasons = [];
        if ((int) $conflict->teacher_id === (int) $teacherId) {
            $reasons[] = 'Guru (' . ($conflict->teacher->fullname ?? '-') . ')';
        }
        if ((int) $conflict->class_id === (int) $classId) {
            $reasons[] = 'Kelas (' . ($conflict->class->class_name ?? '-') . ')';
        }
        if ((int) $conflict->room_id === (int) $roomId) {
            $reasons[] = 'Ruangan (' . ($conflict->room->room_name ?? '-') . ')';
        }

        return 'Bentrok dengan jadwal: ' . implode(', ', $reasons) .
            ' pada ' . $conflict->day_of_week .
            ' ' . substr($conflict->start_time, 0, 5) . '-' . substr($conflict->end_time, 0, 5);
    }
}