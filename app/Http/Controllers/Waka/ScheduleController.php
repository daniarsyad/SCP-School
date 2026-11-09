<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Period;
use App\Services\ScheduleSubjectValidator;
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
        $teacherFilter = $request->get('teacher_id');
        $roomFilter = $request->get('room_id');
        $viewMode = $request->get('view', 'matrix');

        $query = Schedule::with(['teacher', 'class', 'room', 'period'])
            ->where('cycle_type', $cycle);

        if ($dayFilter) {
            $query->where('day_of_week', $dayFilter);
        }

        if ($classFilter) {
            $query->where('class_id', $classFilter);
        }

        if ($teacherFilter) {
            $query->where('teacher_id', $teacherFilter);
        }

        if ($roomFilter) {
            $query->where('room_id', $roomFilter);
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

        $all = Period::orderBy('start_time')->get();
        $learn = $all->where('is_break', false)->values();

        $periodsByDay = [
            'Senin'  => $learn->where('day_group', 'senin')->values(),
            'Selasa' => $learn->where('day_group', 'selasa')->values(),
            'Rabu'   => $learn->where('day_group', 'rabu_kamis')->values(),
            'Kamis'  => $learn->where('day_group', 'rabu_kamis')->values(),
            'Jumat'  => $learn->where('day_group', 'jumat')->values(),
        ];

        $schedules = $query->orderBy('start_time')->orderBy('class_id')->get();

        foreach ($schedules as $s) {
            $dayPeriods = $periodsByDay[$s->day_of_week] ?? collect();
            $startP = $dayPeriods->first(fn ($p) => substr((string) $p->start_time, 0, 5) === substr((string) $s->start_time, 0, 5))
                ?? $s->period;
            $endP = $dayPeriods->first(fn ($p) => substr((string) $p->end_time, 0, 5) === substr((string) $s->end_time, 0, 5))
                ?? $startP;

            $spNum = (int) ($startP->period_number ?? 1);
            $epNum = (int) ($endP->period_number ?? $spNum);
            if ($epNum < $spNum) {
                $epNum = $spNum;
            }

            $s->start_period_number = $spNum;
            $s->end_period_number = $epNum;
            $s->end_period_id = $endP->id ?? ($s->period_id ?? 0);
            $s->period_span = max(1, $epNum - $spNum + 1);
        }

        $days = $dayFilter ? [$dayFilter] : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
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

        $singleEntityMode = (bool) ($classFilter || $teacherFilter || $roomFilter);
        $periodMatrix = [];
        $skipCells = [];
        $periodTimeHints = [];

        for ($pNum = 1; $pNum <= 10; $pNum++) {
            $senP = $periodsByDay['Senin']->firstWhere('period_number', $pNum);
            $selP = $periodsByDay['Selasa']->firstWhere('period_number', $pNum);
            $rabP = $periodsByDay['Rabu']->firstWhere('period_number', $pNum);
            $jumP = $periodsByDay['Jumat']->firstWhere('period_number', $pNum);

            $periodTimeHints[$pNum] = [
                'Senin'  => $senP ? substr((string) $senP->start_time, 0, 5) . '–' . substr((string) $senP->end_time, 0, 5) : '-',
                'Selasa' => $selP ? substr((string) $selP->start_time, 0, 5) . '–' . substr((string) $selP->end_time, 0, 5) : '-',
                'Rabu'   => $rabP ? substr((string) $rabP->start_time, 0, 5) . '–' . substr((string) $rabP->end_time, 0, 5) : '-',
                'Jumat'  => $jumP ? substr((string) $jumP->start_time, 0, 5) . '–' . substr((string) $jumP->end_time, 0, 5) : '-',
            ];

            $periodMatrix[$pNum] = [];
            foreach ($days as $day) {
                if ($singleEntityMode) {
                    $items = $schedules
                        ->where('day_of_week', $day)
                        ->where('start_period_number', $pNum)
                        ->values();
                    $periodMatrix[$pNum][$day] = $items;

                    foreach ($items as $item) {
                        for ($sp = $item->start_period_number + 1; $sp <= $item->end_period_number; $sp++) {
                            $skipCells[$sp][$day] = true;
                        }
                    }
                } else {
                    $periodMatrix[$pNum][$day] = $schedules
                        ->where('day_of_week', $day)
                        ->filter(fn ($item) => $item->start_period_number <= $pNum && $item->end_period_number >= $pNum)
                        ->values();
                }
            }
        }

        $teachers = User::with('role')->where(function ($query) {
            $query->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
                ->orWhereHas('additionalRoles', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']));
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
        $classes = $classesQuery->orderByRaw("FIELD(grade, 'X', 'XI', 'XII')")->orderBy('major')->orderBy('section')->get();

        $rooms = Room::all()->sortBy(function ($r) {
            if (preg_match('/^Ruang\s+(\d+)$/i', $r->room_name, $m)) {
                return sprintf('1_%04d', (int) $m[1]);
            }
            if (preg_match('/^Lab\s+(\d+)$/i', $r->room_name, $m)) {
                return sprintf('2_%04d', (int) $m[1]);
            }
            if (stripos($r->room_name, 'Lab') === 0) {
                return '2_9999_' . $r->room_name;
            }
            if (preg_match('/^OR\s+(\d+)$/i', $r->room_name, $m)) {
                return sprintf('3_%04d', (int) $m[1]);
            }
            return '4_' . $r->room_name;
        })->values();

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
            'matrix', 'periodMatrix', 'skipCells', 'periodTimeHints', 'singleEntityMode',
            'days', 'cycle', 'dayFilter', 'classFilter', 'teacherFilter', 'roomFilter', 'viewMode',
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
            ['Isi Pengajar dengan nama lengkap atau username; mata pelajaran harus sama dengan data pengajar. Untuk Guru BK, gunakan BK atau Bimbingan dan Konseling.'],
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

        $teacherUsers = User::with(['role', 'additionalRoles'])
            ->where(function ($query) {
                $query->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
                    ->orWhereHas('additionalRoles', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']));
            })
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
            if ($dataRowCount > 2000) {
                $errors[] = 'Jumlah data melebihi batas 2.000 jadwal per import.';
                break;
            }

            [$cycleRaw, $teacherName, $className, $roomName, $subject, $day, $startTime, $endTime] =
                array_pad($values, 8, '');
            $rowErrors = [];

            // Ekstrak angka putaran (mendukung 'Putaran 1', 'Putaran 2', '1', atau '2')
            $cycle = preg_replace('/[^\d]/', '', $cycleRaw);
            if (!in_array($cycle, ['1', '2'], true)) {
                $rowErrors[] = 'Kolom Putaran: harus diisi 1 atau 2 (contoh: 1 atau Putaran 1).';
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

            $class = $this->resolveClass($className, $classes);
            if ($className === '' || !$class) {
                $rowErrors[] = $className === ''
                    ? 'Kolom Kelas: wajib diisi.'
                    : "Kolom Kelas: \"{$className}\" tidak ditemukan.";
            }

            $room = $this->resolveRoom($roomName, $rooms);
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
                $teacherSubjectError = app(ScheduleSubjectValidator::class)->errorFor($teacher, $subject);
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

            $importConflict = false;
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
                    $importConflict = true;
                    break;
                }
            }

            if ($importConflict) {
                continue;
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

        $teacherSubjectError = app(ScheduleSubjectValidator::class)->errorFor(
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
            'period_id'     => $startPeriod->id,
            'end_period_id' => $endPeriod->id,
            'cycle_type'    => $request->cycle_type,
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

        $teacherSubjectError = app(ScheduleSubjectValidator::class)->errorFor(
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
            'period_id'     => $startPeriod->id,
            'end_period_id' => $endPeriod->id,
            'cycle_type'    => $request->cycle_type,
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

    public function journalRecap(Request $request)
    {
        $config = SystemConfiguration::current();
        $validated = $request->validate([
            'date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'grade' => 'nullable|in:X,XI,XII',
            'major' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:10',
            'teacher_id' => 'nullable|exists:users,id',
        ]);
        $selectedDate = Carbon::parse($validated['date'] ?? Carbon::now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');
        $todayDate = $selectedDate->toDateString();
        $today = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$selectedDate->dayOfWeekIso];
        $now = Carbon::now('Asia/Jakarta');

        $todaySchedules = Schedule::with([
            'teacher',
            'class.homeroomTeacher',
            'room',
            'period',
            'journals' => fn ($query) => $query->whereDate('date', $todayDate)->with(['attendances.student']),
        ])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today)
            ->when(isset($validated['class_id']), fn ($query) => $query->where('class_id', $validated['class_id']))
            ->when(isset($validated['teacher_id']), fn ($query) => $query->where('teacher_id', $validated['teacher_id']))
            ->whereHas('class', fn ($query) => $query
                ->when(isset($validated['grade']), fn ($q) => $q->where('grade', $validated['grade']))
                ->when(isset($validated['major']), fn ($q) => $q->where('major', $validated['major']))
                ->when(isset($validated['section']), fn ($q) => $q->where('section', $validated['section'])))
            ->orderBy('start_time')
            ->get();

        $sudahIsi = $todaySchedules->filter(fn($s) => $s->journals->isNotEmpty());

        $belumIsi = $todaySchedules->filter(fn($s) => $s->journals->isEmpty());

        $terlambat = $todayDate === $now->toDateString()
            ? $belumIsi->filter(function ($s) use ($now) {
            $endTime = Carbon::parse($s->end_time);
            return $now->gt($endTime->copy()->addMinutes(30));
            })
            : collect();

        $classes = Classes::orderBy('grade')->orderBy('major')->orderBy('section')->get();
        $teachers = User::where(function ($query) {
            $query->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']))
                ->orWhereHas('additionalRoles', fn ($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK']));
        })
            ->orderBy('fullname')
            ->get(['id', 'fullname']);

        return view('waka.schedules.journal-recap', compact(
            'config',
            'today',
            'todayDate',
            'todaySchedules',
            'sudahIsi',
            'belumIsi',
            'terlambat',
            'classes',
            'teachers'
        ));
    }

    public function printTodayJournals(Request $request)
    {
        $config = SystemConfiguration::current();
        $validated = $request->validate([
            'date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'grade' => 'nullable|in:X,XI,XII',
            'major' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:10',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $selectedDate = Carbon::parse($validated['date'] ?? Carbon::now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');
        $todayDate = $selectedDate->toDateString();
        $todayDayName = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ][$selectedDate->dayOfWeekIso];

        $appliedFilters = [];
        if (!empty($validated['grade'])) $appliedFilters[] = 'Tingkat ' . $validated['grade'];
        if (!empty($validated['major'])) $appliedFilters[] = 'Jurusan ' . $validated['major'];
        if (!empty($validated['section'])) $appliedFilters[] = 'Rombel ' . $validated['section'];

        $journals = Journal::with(['schedule.teacher', 'schedule.class', 'schedule.room', 'attendances'])
            ->whereDate('date', $todayDate)
            ->whereHas('schedule', function ($query) use ($validated, $config) {
                $query->where('cycle_type', $config->active_cycle)
                    ->when(isset($validated['class_id']), fn ($q) => $q->where('class_id', $validated['class_id']))
                    ->when(isset($validated['teacher_id']), fn ($q) => $q->where('teacher_id', $validated['teacher_id']))
                    ->whereHas('class', fn ($q) => $q
                        ->when(isset($validated['grade']), fn ($cq) => $cq->where('grade', $validated['grade']))
                        ->when(isset($validated['major']), fn ($cq) => $cq->where('major', $validated['major']))
                        ->when(isset($validated['section']), fn ($cq) => $cq->where('section', $validated['section'])));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $periodText = $todayDayName . ', ' . $selectedDate->translatedFormat('d F Y') . ' (Putaran ' . $config->active_cycle . ')';
        $docSubtitle = 'Rekapitulasi Jurnal Mengajar Harian — ' . $periodText;

        return view('waka.print.journals', compact(
            'journals',
            'periodText',
            'docSubtitle',
            'appliedFilters'
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

    private function resolveClass(?string $rawClassName, $classes): ?Classes
    {
        if (!$rawClassName) {
            return null;
        }

        $trimmed = trim($rawClassName);
        if ($trimmed === '') {
            return null;
        }

        // 1. Direct match
        $class = $classes->first(fn ($item) => strcasecmp($item->class_name, $trimmed) === 0);
        if ($class) {
            return $class;
        }

        // 2. Normalisasi spasi yang hilang antara jurusan dan nomor rombel (contoh: XI TKJ1 -> XI TKJ 1)
        $normalized = preg_replace('/^([X|V|I]+)\s+([A-Za-z]+)(\d+)$/i', '$1 $2 $3', $trimmed);
        $class = $classes->first(fn ($item) => strcasecmp($item->class_name, $normalized) === 0);
        if ($class) {
            return $class;
        }

        // 3. Mapping alias umum (contoh: AK -> AKL, BD -> BD 1, LPS -> LPS 1)
        $alias = $normalized;
        $alias = preg_replace('/^([X|V|I]+)\s+AK\s+(\d+)$/i', '$1 AKL $2', $alias);
        $alias = preg_replace('/^([X|V|I]+)\s+AK(\d+)$/i', '$1 AKL $2', $alias);
        $alias = preg_replace('/^([X|V|I]+)\s+BD$/i', '$1 BD 1', $alias);
        $alias = preg_replace('/^([X|V|I]+)\s+LPS$/i', '$1 LPS 1', $alias);

        $class = $classes->first(fn ($item) => strcasecmp($item->class_name, $alias) === 0);
        if ($class) {
            return $class;
        }

        // 4. Fallback cari berdasarkan class_name lowercase
        return $classes->first(fn ($item) =>
            strcasecmp(trim((string)$item->class_name), $trimmed) === 0
            || strcasecmp(trim((string)$item->class_name), $normalized) === 0
            || strcasecmp(trim((string)$item->class_name), $alias) === 0
        );
    }

    private function resolveRoom(?string $roomName, $rooms): ?Room
    {
        if (!$roomName) {
            return null;
        }

        $trimmed = trim($roomName);
        if ($trimmed === '') {
            return null;
        }

        $room = $rooms->first(fn ($item) => strcasecmp($item->room_name, $trimmed) === 0);
        if ($room) {
            return $room;
        }

        $normalized = preg_replace('/\s+/', ' ', $trimmed);
        return $rooms->first(fn ($item) => strcasecmp($item->room_name, $normalized) === 0);
    }
}