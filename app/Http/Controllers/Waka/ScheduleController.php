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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function downloadImportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal');

        $headers = [
            'Putaran',
            'Pengajar',
            'Kelas',
            'Ruangan',
            'Mata Pelajaran',
            'Hari',
            'Jam Mulai (Jam ke-x)',
            'Jam Selesai (Jam ke-x)',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
            $sheet->getColumnDimensionByColumn($index + 1)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H1');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Petunjuk');
        $instructions->fromArray([
            ['Petunjuk Import Jadwal'],
            ['Isi satu jadwal pada setiap baris di sheet Jadwal.'],
            ['Pengajar wajib sudah terdaftar di menu Pengguna dan memiliki role Guru, Wali Kelas, atau Guru BK.'],
            ['Isi Pengajar dengan nama lengkap atau username; mata pelajaran harus sama dengan mata pelajaran yang terdaftar untuk pengajar tersebut.'],
            ['Nama kelas dan ruangan harus sesuai dengan data yang terdaftar.'],
            ['Putaran diisi 1 atau 2; hari diisi Senin, Selasa, Rabu, Kamis, atau Jumat.'],
            ['Jam mulai dan selesai diisi urutan jam pelajaran / Jam ke- (contoh: 2 atau Jam ke-2).'],
            ['Import dibatalkan seluruhnya jika ditemukan baris tidak valid atau jadwal bentrok.'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setAutoSize(true);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'schedule-template-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Tidak dapat menyiapkan file template jadwal.');
        }

        $path = $temporaryPath . '.xlsx';
        try {
            if (!rename($temporaryPath, $path)) {
                throw new \RuntimeException('Tidak dapat menyiapkan file template jadwal.');
            }
            (new Xlsx($spreadsheet))->save($path);
        } catch (\Throwable $exception) {
            @unlink($temporaryPath);
            @unlink($path);
            throw $exception;
        }

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download(
            $path,
            'template_import_jadwal.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $config = SystemConfiguration::current();
        if ($config->is_schedule_locked) {
            return back()->with('error', 'Jadwal sedang terkunci. Tidak bisa mengimpor jadwal.');
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Kolom File jadwal: wajib diunggah.',
            'file.file' => 'Kolom File jadwal: file yang diunggah tidak valid.',
            'file.mimes' => 'Kolom File jadwal: gunakan format .xlsx, .xls, atau .csv.',
            'file.max' => 'Kolom File jadwal: ukuran file maksimal 5 MB.',
        ]);
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        try {
            $sheet = IOFactory::load($request->file('file')->getPathname())->getActiveSheet();
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $exception) {
            return back()->with('error', 'File jadwal tidak dapat dibaca. Pastikan format file sesuai template.');
        }

        $rows = $sheet->toArray(null, true, true, false);
        if (count($rows) < 2) {
            return back()->with('error', 'File import belum berisi data jadwal.');
        }

        $expectedHeaders = [
            ['Putaran', ['putaran']],
            ['Pengajar', ['pengajar', 'pengajar (nama lengkap/username)']],
            ['Kelas', ['kelas']],
            ['Ruangan', ['ruangan']],
            ['Mata Pelajaran', ['mata pelajaran']],
            ['Hari', ['hari']],
            ['Jam Mulai', ['jam mulai', 'jam mulai (jam ke-x)', 'jam mulai (hh:mm)']],
            ['Jam Selesai', ['jam selesai', 'jam selesai (jam ke-x)', 'jam selesai (hh:mm)']],
        ];
        foreach ($expectedHeaders as $index => [$columnName, $allowedHeaders]) {
            $header = mb_strtolower(trim((string) ($rows[0][$index] ?? '')));
            if (!in_array($header, $allowedHeaders, true)) {
                return back()->with(
                    'error',
                    "Import dibatalkan. Kolom {$columnName}: judul kolom tidak sesuai. " .
                    'Unduh dan gunakan template jadwal terbaru.'
                );
            }
        }

        $teacherUsers = User::with('role')
            ->whereHas('role', fn ($query) => $query->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
            ->get();
        $classes = Classes::all();
        $rooms = Room::all();
        $dayGroups = [
            'Senin' => 'senin',
            'Selasa' => 'selasa',
            'Rabu' => 'rabu_kamis',
            'Kamis' => 'rabu_kamis',
            'Jumat' => 'jumat',
        ];
        $periods = Period::where('is_break', false)->get();
        $prepared = [];
        $errors = [];
        $dataRowCount = 0;

        foreach (array_slice($rows, 1, null, true) as $index => $row) {
            $values = array_map(fn ($value) => trim((string) $value), $row);
            if (count(array_filter($values, fn ($value) => $value !== '')) === 0) {
                continue;
            }

            $rowNumber = $index + 1;
            $dataRowCount++;
            if ($dataRowCount > 500) {
                $errors[] = 'Jumlah data melebihi batas 500 jadwal per import.';
                break;
            }

            [$cycle, $teacherName, $className, $roomName, $subject, $day, $startTime, $endTime] =
                array_pad($values, 8, '');
            $rowErrors = [];

            if (!in_array($cycle, ['1', '2'], true)) {
                $rowErrors[] = 'Kolom Putaran: harus diisi 1 atau 2.';
            }

            $teacherMatches = $teacherUsers->filter(fn ($teacher) =>
                strcasecmp($teacher->fullname, $teacherName) === 0
                || strcasecmp($teacher->username, $teacherName) === 0
            );
            $teacher = $teacherMatches->count() === 1 ? $teacherMatches->first() : null;
            if ($teacherName === '' || !$teacher) {
                $rowErrors[] = $teacherName === ''
                    ? 'Kolom Pengajar: wajib diisi.'
                    : ($teacherMatches->isEmpty()
                        ? "Kolom Pengajar: \"{$teacherName}\" tidak ada dalam daftar pengguna."
                        : "Kolom Pengajar: \"{$teacherName}\" tidak unik; gunakan username.");
            }

            $class = $classes->first(fn ($item) => strcasecmp($item->class_name, $className) === 0);
            if ($className === '' || !$class) {
                $rowErrors[] = $className === ''
                    ? 'Kolom Kelas: wajib diisi.'
                    : "Kolom Kelas: \"{$className}\" tidak ditemukan.";
            }

            $room = $rooms->first(fn ($item) => strcasecmp($item->room_name, $roomName) === 0);
            if ($roomName === '' || !$room) {
                $rowErrors[] = $roomName === ''
                    ? 'Kolom Ruangan: wajib diisi.'
                    : "Kolom Ruangan: \"{$roomName}\" tidak ditemukan.";
            }

            if ($subject === '' || mb_strlen($subject) > 100) {
                $rowErrors[] = $subject === ''
                    ? 'Kolom Mata Pelajaran: wajib diisi.'
                    : 'Kolom Mata Pelajaran: maksimal 100 karakter.';
            } elseif ($teacher) {
                $teacherSubjectError = $this->teacherSubjectError($teacher, $subject);
                if ($teacherSubjectError) {
                    $rowErrors[] = $teacherSubjectError;
                }
            }
            if (!array_key_exists($day, $dayGroups)) {
                $rowErrors[] = 'Kolom Hari: harus Senin sampai Jumat.';
            }

            $dayPeriods = isset($dayGroups[$day])
                ? $periods->where('day_group', $dayGroups[$day])
                : collect();

            $startPeriod = $startTime !== '' ? $this->findPeriodFromInput($startTime, $dayPeriods) : null;
            $endPeriod   = $endTime !== '' ? $this->findPeriodFromInput($endTime, $dayPeriods) : null;

            if ($startTime === '') {
                $rowErrors[] = 'Kolom Jam Mulai: wajib diisi.';
            } elseif (!$startPeriod) {
                $rowErrors[] = "Kolom Jam Mulai: \"{$startTime}\" bukan jam pelajaran / jam ke- yang valid untuk {$day}.";
            }

            if ($endTime === '') {
                $rowErrors[] = 'Kolom Jam Selesai: wajib diisi.';
            } elseif (!$endPeriod) {
                $rowErrors[] = "Kolom Jam Selesai: \"{$endTime}\" bukan jam pelajaran / jam ke- yang valid untuk {$day}.";
            }

            if ($startPeriod && $endPeriod && $endPeriod->start_time < $startPeriod->start_time) {
                $rowErrors[] = 'Kolom Jam Selesai: harus berada sama atau setelah jam mulai.';
            }

            if ($rowErrors) {
                foreach ($rowErrors as $message) {
                    $errors[] = "Baris {$rowNumber}: {$message}";
                }
                continue;
            }

            $scheduleData = [
                'teacher_id' => $teacher->id,
                'class_id'   => $class->id,
                'room_id'    => $room->id,
                'subject'    => $subject,
                'day_of_week'=> $day,
                'start_time' => substr((string) $startPeriod->start_time, 0, 5),
                'end_time'   => substr((string) $endPeriod->end_time, 0, 5),
                'period_id'  => $startPeriod->id,
                'cycle_type' => $cycle,
                'row_number' => $rowNumber,
            ];

            $conflict = $this->checkConflict(
                $cycle,
                $day,
                $scheduleData['start_time'],
                $scheduleData['end_time'],
                $teacher->id,
                $class->id,
                $room->id
            );
            if ($conflict) {
                $errors[] = "Baris {$rowNumber} (Putaran {$cycle}): {$conflict}";
                continue;
            }

            foreach ($prepared as $previous) {
                $overlaps = $previous['cycle_type'] === $cycle
                    && $previous['day_of_week'] === $day
                    && $previous['start_time'] < $scheduleData['end_time']
                    && $previous['end_time'] > $scheduleData['start_time'];
                $sameResource = $previous['teacher_id'] === $teacher->id
                    || $previous['class_id'] === $class->id
                    || $previous['room_id'] === $room->id;

                if ($overlaps && $sameResource) {
                    $errors[] = "Baris {$rowNumber} (Putaran {$cycle}): bentrok dengan jadwal pada baris {$previous['row_number']} (Putaran {$previous['cycle_type']}) di file import.";
                    break;
                }
            }

            $prepared[] = $scheduleData;
        }

        if ($dataRowCount === 0) {
            return back()->with('error', 'File import belum berisi data jadwal.');
        }
        if ($errors) {
            $visibleErrors = array_slice($errors, 0, 10);
            $remaining = count($errors) - count($visibleErrors);
            $message = implode(' ', $visibleErrors);
            if ($remaining > 0) {
                $message .= " Dan {$remaining} kesalahan lainnya.";
            }

            return back()->with('error', "Import dibatalkan. {$message}");
        }

        DB::transaction(function () use ($prepared) {
            foreach ($prepared as $scheduleData) {
                unset($scheduleData['row_number']);
                Schedule::create($scheduleData);
            }
        });

        return back()->with('success', count($prepared) . ' jadwal berhasil diimpor.');
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

        $teacherSubjectError = $this->teacherSubjectError(
            User::findOrFail($request->teacher_id),
            $request->subject
        );
        if ($teacherSubjectError) {
            return back()->with('error', $teacherSubjectError)->withInput();
        }

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

        $teacherSubjectError = $this->teacherSubjectError(
            User::findOrFail($request->teacher_id),
            $request->subject
        );
        if ($teacherSubjectError) {
            return back()->with('error', $teacherSubjectError)->withInput();
        }

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

        $todaySchedules = Schedule::with([
            'teacher',
            'class',
            'room',
            'period',
            'journals' => fn($query) => $query->whereDate('date', $todayDate),
        ])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        $sudahIsi = $todaySchedules->filter(fn($s) => $s->journals->isNotEmpty());

        $belumIsi = $todaySchedules->filter(fn($s) => $s->journals->isEmpty());

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

    private function findPeriodFromInput(string $value, $dayPeriods)
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // 1. Ekstrak angka dari input (contoh input Excel: "2", "6", "Jam ke-2", "Jam ke 2", "Jam 2")
        if (preg_match('/(?:jam\s*(?:ke-?)?\s*)?(\d+)/i', $value, $matches)) {
            $periodNum = (int) $matches[1];
            $period = $dayPeriods->first(fn ($p) => (int) $p->period_number === $periodNum);
            if ($period) {
                return $period;
            }
        }

        // 2. Pencocokan berdasarkan label (contoh: "Jam ke-2")
        $period = $dayPeriods->first(fn ($p) => strcasecmp(trim((string) $p->label), $value) === 0);
        if ($period) {
            return $period;
        }

        // 3. Fallback: pencocokan berdasarkan format HH:MM (contoh jika diisi "08:35")
        $timeStr = $this->normalizeImportTime($value);
        if ($timeStr) {
            return $dayPeriods->first(fn ($p) =>
                substr((string) $p->start_time, 0, 5) === $timeStr ||
                substr((string) $p->end_time, 0, 5) === $timeStr
            );
        }

        return null;
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

        return 'bentrok dengan jadwal Putaran ' . $conflict->cycle_type .
            ' (' . $conflict->subject . '): ' . implode(', ', $reasons) .
            ' pada ' . $conflict->day_of_week .
            ' ' . substr($conflict->start_time, 0, 5) . '-' . substr($conflict->end_time, 0, 5);
    }

    private function normalizeImportTime(string $value): ?string
    {
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value;
        }

        if (is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
            $seconds = (int) round((float) $value * 86400);
            return gmdate('H:i', $seconds);
        }

        return null;
    }

    private function teacherSubjectError(User $teacher, string $subject): ?string
    {
        $teacherSubject = trim((string) $teacher->subject);
        $subject = trim($subject);

        if ($teacherSubject === '') {
            return "Kolom Pengajar / Mata Pelajaran: {$teacher->fullname} belum memiliki mata pelajaran pada data pengguna.";
        }

        if (strcasecmp($teacherSubject, $subject) !== 0) {
            return "Kolom Pengajar / Mata Pelajaran: {$teacher->fullname} mengajar \"{$teacherSubject}\", bukan \"{$subject}\".";
        }

        return null;
    }
}