<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        $config = SystemConfiguration::current();

        $schedules = Schedule::with(['teacher', 'class', 'room'])
            ->where('cycle_type', $config->active_cycle)
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get();

        $teachers = User::whereHas('role', function ($q) {
            $q->where('role_name', 'Guru');
        })->orderBy('fullname')->get();

        $classes = Classes::orderBy('class_name')->get();
        $rooms   = Room::orderBy('room_name')->get();

        return view('waka.schedules.index', compact(
            'schedules', 'config', 'teachers', 'classes', 'rooms'
        ));
    }

    public function store(Request $request)
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang terkunci. Tidak bisa menambah jadwal.');
        }

        $request->validate([
            'teacher_id'  => 'required|exists:users,id',
            'class_id'    => 'required|exists:classes,id',
            'room_id'     => 'required|exists:rooms,id',
            'subject'     => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        $conflict = $this->checkConflict(
            $config->active_cycle,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
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
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'cycle_type'  => $config->active_cycle,
        ]);

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang terkunci. Tidak bisa mengubah jadwal.');
        }

        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'teacher_id'  => 'required|exists:users,id',
            'class_id'    => 'required|exists:classes,id',
            'room_id'     => 'required|exists:rooms,id',
            'subject'     => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        $conflict = $this->checkConflict(
            $config->active_cycle,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
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
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
        ]);

        return back()->with('success', 'Jadwal berhasil diupdate.');
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

        $todaySchedules = Schedule::with(['teacher', 'class', 'room', 'journals'])
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