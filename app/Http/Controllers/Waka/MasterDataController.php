<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\IdentityNumberValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterDataController extends Controller
{
    // ==================== KELAS ====================
    public function classes(Request $request)
    {
        $query = Classes::withCount([
            'users' => fn ($query) => $query
                ->where('is_alumni', false)
                ->whereHas('role', fn ($roleQuery) => $roleQuery->where('role_name', 'Siswa')),
            'schedules',
        ]);

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }
        if ($request->filled('major')) {
            $query->where('major', $request->major);
        }
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        $classes = $query->orderBy('grade')->orderBy('major')->orderBy('section')->get();

        $siswaRoleId = Role::where('role_name', 'Siswa')->value('id');

        return view('waka.master.classes', compact('classes', 'siswaRoleId'));
    }

    public function classStudents(Request $request, $id)
    {
        $class = Classes::with(['homeroomTeacher', 'counselingTeachers'])->findOrFail($id);

        $students = User::where('class_id', $id)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->orderBy('fullname')
            ->get();

        $studentIds = $students->pluck('id');

        // Rekapitulasi status presensi kumulatif per siswa
        $attendancesGrouped = Attendance::whereIn('student_id', $studentIds)
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

        // Statistik kumulatif keseluruhan kelas
        $classTotalHadir = (int) ($attendancesGrouped->where('status', 'Hadir')->sum('total'));
        $classTotalIzin = (int) ($attendancesGrouped->where('status', 'Izin')->sum('total'));
        $classTotalSakit = (int) ($attendancesGrouped->where('status', 'Sakit')->sum('total'));
        $classTotalAlpha = (int) ($attendancesGrouped->where('status', 'Alpha')->sum('total'));
        $classTotalPresensi = $classTotalHadir + $classTotalIzin + $classTotalSakit + $classTotalAlpha;
        $classAttendanceRate = $classTotalPresensi > 0
            ? round(($classTotalHadir / $classTotalPresensi) * 100, 1)
            : 0;

        // Jadwal KBM mingguan kelas
        $schedules = Schedule::with(['teacher', 'room', 'period'])
            ->where('class_id', $id)
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
            ->orderBy('start_time')
            ->get();

        // Riwayat sesi jurnal mengajar dan daftar presensi per sesi
        $journalsQuery = Journal::with([
            'schedule.teacher',
            'schedule.room',
            'schedule.period',
            'attendances.student',
        ])
            ->whereHas('schedule', fn ($q) => $q->where('class_id', $id));

        if ($request->filled('start_date')) {
            $journalsQuery->whereDate('date', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $journalsQuery->whereDate('date', '<=', $request->query('end_date'));
        }

        $journalHistory = $journalsQuery->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('waka.master.class-students', compact(
            'class',
            'students',
            'studentStats',
            'classTotalHadir',
            'classTotalIzin',
            'classTotalSakit',
            'classTotalAlpha',
            'classTotalPresensi',
            'classAttendanceRate',
            'schedules',
            'journalHistory'
        ));
    }

    // Cetak dan ekspor laporan rekapitulasi presensi siswa per kelas ke format PDF
    public function printClassAttendance(Request $request, $id)
    {
        $class = Classes::with(['homeroomTeacher', 'counselingTeachers'])->findOrFail($id);

        $students = User::where('class_id', $id)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->orderBy('fullname')
            ->get();

        $studentIds = $students->pluck('id');

        $attendancesQuery = Attendance::whereIn('student_id', $studentIds);
        if ($request->filled('start_date')) {
            $attendancesQuery->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $attendancesQuery->whereDate('date', '<=', $request->end_date);
        }

        $attendancesGrouped = $attendancesQuery
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

        $periodText = 'Semua Periode Pembelajaran';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $periodText = \Carbon\Carbon::parse($request->start_date)->translatedFormat('d M Y') . ' – ' . \Carbon\Carbon::parse($request->end_date)->translatedFormat('d M Y');
        }

        return view('waka.print.class-attendance', compact(
            'class',
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

    // Cetak dan ekspor laporan jurnal KBM per kelas ke format PDF
    public function printClassJournals(Request $request, $id)
    {
        $class = Classes::with('homeroomTeacher')->findOrFail($id);

        $journalsQuery = Journal::with([
            'schedule.teacher',
            'schedule.class',
            'schedule.room',
            'schedule.period',
            'attendances',
        ])
            ->whereHas('schedule', fn ($q) => $q->where('class_id', $id));

        $appliedFilters = ['Kelas: ' . $class->class_name];

        if ($request->filled('start_date')) {
            $journalsQuery->whereDate('date', '>=', $request->start_date);
            $appliedFilters[] = 'Dari: ' . \Carbon\Carbon::parse($request->start_date)->format('d/m/Y');
        }
        if ($request->filled('end_date')) {
            $journalsQuery->whereDate('date', '<=', $request->end_date);
            $appliedFilters[] = 'Sampai: ' . \Carbon\Carbon::parse($request->end_date)->format('d/m/Y');
        }

        $journals = $journalsQuery->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        $periodText = 'Rekap Jurnal Kelas ' . $class->class_name;
        $docSubtitle = 'Riwayat Jurnal Mengajar Guru di Kelas ' . $class->class_name;

        return view('waka.print.journals', compact(
            'class',
            'journals',
            'periodText',
            'docSubtitle',
            'appliedFilters'
        ));
    }

    public function storeClass(Request $request)
    {
        $request->validate([
            'grade' => 'required|in:X,XI,XII',
            'major' => 'required|string|max:20',
            'section' => 'required|string|max:10',
        ]);

        $className = $request->grade.' '.$request->major.' '.$request->section;

        if (Classes::where('class_name', $className)->exists()) {
            return back()->with('error', 'Kelas sudah ada.');
        }

        Classes::create([
            'class_name' => $className,
            'grade' => $request->grade,
            'major' => $request->major,
            'section' => $request->section,
        ]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'grade' => 'required|in:X,XI,XII',
            'major' => 'required|string|max:20',
            'section' => 'required|string|max:10',
        ]);

        $class = Classes::findOrFail($id);
        $className = $request->grade.' '.$request->major.' '.$request->section;

        if (Classes::where('class_name', $className)->where('id', '!=', $id)->exists()) {
            return back()->with('error', 'Kelas sudah ada.');
        }

        $class->update([
            'class_name' => $className,
            'grade' => $request->grade,
            'major' => $request->major,
            'section' => $request->section,
        ]);

        return back()->with('success', 'Kelas berhasil diupdate.');
    }

    public function deleteClass($id)
    {
        Classes::findOrFail($id)->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    // ==================== RUANGAN ====================
    public function rooms(Request $request)
    {
        $query = Room::withCount([
            'schedules',
            'schedules as schedules_p1_count' => fn ($q) => $q->where('cycle_type', '1'),
            'schedules as schedules_p2_count' => fn ($q) => $q->where('cycle_type', '2'),
        ]);

        if ($request->filled('search')) {
            $query->where('room_name', 'like', '%'.$request->search.'%');
        }

        $rooms = $query->get()->sortBy(function ($r) {
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

        return view('waka.master.rooms', compact('rooms'));
    }

    public function storeRoom(Request $request)
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);

        Room::create(['room_name' => $request->room_name]);

        return back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function updateRoom(Request $request, $id)
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name,'.$id,
        ]);

        $room = Room::findOrFail($id);
        $room->update(['room_name' => $request->room_name]);

        return back()->with('success', 'Ruangan berhasil diupdate.');
    }

    public function deleteRoom($id)
    {
        Room::findOrFail($id)->delete();

        return back()->with('success', 'Ruangan berhasil dihapus.');
    }

    // ==================== USER ====================
    public function users(Request $request)
    {
        $query = User::with(['role', 'class', 'bkClasses', 'additionalRoles', 'children:id,fullname,nis,class_id,parent_id']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where(function ($roleQuery) use ($request) {
                $roleQuery->where('role_id', $request->role_id)
                    ->orWhereHas('additionalRoles', fn ($additionalRoleQuery) => $additionalRoleQuery->whereKey($request->role_id));
            });
        }

        if ($request->filled('class_id')) {
            $query->where(function ($userQuery) use ($request) {
                $userQuery->where('class_id', $request->class_id)
                    ->orWhereHas('bkClasses', fn ($classQuery) => $classQuery->whereKey($request->class_id));
            });
        }

        $users = $query->orderBy('fullname')->get();

        $roles = Role::orderBy('role_name')->get();
        $classes = Classes::orderBy('class_name')->get();
        $students = User::whereNotNull('nis')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->with('parent:id,fullname,role_id')
            ->orderBy('class_id')
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'nis', 'class_id', 'parent_id']);

        $stats = [
            'total' => User::count(),
            'waka' => User::whereHas('role', fn ($q) => $q->where('role_name', 'Waka Kurikulum'))
                ->orWhereHas('additionalRoles', fn ($q) => $q->where('role_name', 'Waka Kurikulum'))
                ->count(),
            'guru' => User::whereHas('role', fn ($q) => $q->where('role_name', 'Guru'))->count(),
            'walas_bk' => User::whereHas('role', fn ($q) => $q->whereIn('role_name', ['Wali Kelas', 'Guru BK']))->count(),
            'siswa_ortu' => User::whereHas('role', fn ($q) => $q->whereIn('role_name', ['Siswa', 'Orang Tua']))
                ->where('is_alumni', false)
                ->count(),
        ];

        $bulkDeleteCount = User::where('id', '!=', auth()->id())->count();

        return view('waka.master.users', compact('users', 'roles', 'classes', 'students', 'stats', 'bulkDeleteCount'));
    }

    public function teachers(Request $request)
    {
        $teacherRoleNames = ['Guru', 'Wali Kelas', 'Guru BK'];
        $query = User::with(['role', 'class', 'bkClasses', 'additionalRoles'])
            ->withCount('schedules')
            ->where(function ($q) use ($teacherRoleNames) {
                $q->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('role_name', $teacherRoleNames))
                    ->orWhereHas('additionalRoles', fn ($roleQuery) => $roleQuery->whereIn('role_name', $teacherRoleNames));
            });

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($userQuery) use ($search) {
                $userQuery->where('fullname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->role, $teacherRoleNames, true)) {
            $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('role_name', $request->role));
        }

        $teachers = $query->orderBy('fullname')->get();
        $classes = Classes::orderBy('grade')->orderBy('major')->orderBy('section')->orderBy('class_name')->get();
        $classOptions = $classes->map(fn (Classes $class) => [
            'id' => $class->id,
            'name' => $class->class_name,
            'grade' => $class->grade,
            'major' => $class->major,
            'section' => $class->section,
        ])->values();
        $roles = Role::whereIn('role_name', $teacherRoleNames)->orderBy('role_name')->get();
        $stats = [
            'total' => User::whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('role_name', $teacherRoleNames))->count(),
            'guru' => User::whereHas('role', fn ($roleQuery) => $roleQuery->where('role_name', 'Guru'))->count(),
            'wali' => User::whereHas('role', fn ($roleQuery) => $roleQuery->where('role_name', 'Wali Kelas'))->count(),
            'bk' => User::whereHas('role', fn ($roleQuery) => $roleQuery->where('role_name', 'Guru BK'))->count(),
        ];

        return view('waka.teachers.index', compact('teachers', 'classes', 'classOptions', 'roles', 'stats'));
    }

    public function storeTeacher(Request $request)
    {
        $role = Role::whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK'])->find($request->input('role_id'));
        if (! $role) {
            return back()->withErrors(['role_id' => 'Pilih peran guru yang valid.'])->withInput();
        }

        $roleName = $role->role_name;
        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'nullable|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            // [VALIDASI NIP & NIS] NIP opsional, wajib angka maksimal 18 digit
            'nip' => IdentityNumberValidator::nipRules(required: false),
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'class_id' => 'nullable|exists:classes,id',
            'bk_class_ids' => 'array',
            'bk_class_ids.*' => 'required|integer|distinct|exists:classes,id',
        ];
        if ($roleName === 'Wali Kelas') {
            $rules['class_id'] = 'required|exists:classes,id';
        } elseif ($roleName === 'Guru BK') {
            $rules['bk_class_ids'] = 'required|array|min:1';
        } else {
            unset($rules['bk_class_ids'], $rules['bk_class_ids.*']);
        }
        // [VALIDASI NIP & NIS] Sertakan pesan error bahasa Indonesia
        $validated = $request->validate($rules, IdentityNumberValidator::messages());

        DB::transaction(function () use ($validated, $role, $roleName) {
            $teacher = User::create([
                'fullname' => $validated['fullname'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'nip' => $validated['nip'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'class_id' => $roleName === 'Wali Kelas' ? $validated['class_id'] : null,
            ]);

            if ($roleName === 'Guru BK') {
                $teacher->bkClasses()->sync($validated['bk_class_ids']);
            }
        });

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = User::with('role')->findOrFail($id);
        if (! in_array($teacher->role?->role_name, ['Guru', 'Wali Kelas', 'Guru BK'], true)) {
            return back()->with('error', 'Akun ini bukan data guru.');
        }

        $role = Role::whereIn('role_name', ['Guru', 'Wali Kelas', 'Guru BK'])->find($request->input('role_id'));
        if (! $role) {
            return back()->withErrors(['role_id' => 'Pilih peran guru yang valid.'])->withInput();
        }

        $roleName = $role->role_name;
        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,'.$teacher->id,
            'email' => 'nullable|email|max:100|unique:users,email,'.$teacher->id,
            'password' => 'nullable|string|min:6',
            // [VALIDASI NIP & NIS] NIP opsional, wajib angka maksimal 18 digit
            'nip' => IdentityNumberValidator::nipRules(required: false, ignoreUserId: $teacher->id),
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'class_id' => 'nullable|exists:classes,id',
            'bk_class_ids' => 'array|min:1',
            'bk_class_ids.*' => 'required|integer|distinct|exists:classes,id',
        ];
        if ($roleName === 'Wali Kelas') {
            $rules['class_id'] = 'required|exists:classes,id';
        }
        if ($roleName === 'Guru BK') {
            $rules['bk_class_ids'] = 'required|array|min:1';
        }
        // [VALIDASI NIP & NIS] Sertakan pesan error bahasa Indonesia
        $validated = $request->validate($rules, IdentityNumberValidator::messages());

        DB::transaction(function () use ($teacher, $validated, $role, $roleName) {
            $data = [
                'fullname' => $validated['fullname'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'role_id' => $role->id,
                'nip' => $validated['nip'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'class_id' => $roleName === 'Wali Kelas' ? $validated['class_id'] : null,
            ];
            if (! empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }
            $teacher->update($data);
            $teacher->bkClasses()->sync($roleName === 'Guru BK' ? $validated['bk_class_ids'] : []);
        });

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function deleteTeacher($id)
    {
        $teacher = User::findOrFail($id);
        if ((int) $teacher->id === (int) auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        if (! $teacher->isGuru() && ! $teacher->isWaliKelas() && ! $teacher->isGuruBK()) {
            return back()->with('error', 'Akun ini bukan data guru.');
        }

        $teacher->delete();

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil dihapus.');
    }

    public function storeUser(Request $request)
    {
        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'nullable|string|max:50|unique:users,username',
            'email' => 'nullable|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
            'subject' => 'nullable|string|max:100',
            // [VALIDASI NIP & NIS] NIS wajib angka (panjang bebas); NIP wajib angka maks. 18 digit
            'nis' => IdentityNumberValidator::optionalNisFormatRules(),
            'nip' => IdentityNumberValidator::optionalNipFormatRules(),
            'student_id' => 'nullable|integer|exists:users,id',
            'bk_class_ids' => 'sometimes|array',
            'bk_class_ids.*' => 'required|integer|distinct|exists:classes,id',
        ];

        $role = Role::find($request->role_id);
        $roleName = strtolower($role?->role_name ?? '');

        if ($role) {
            if ($roleName === 'siswa' || $roleName === 'wali kelas') {
                $rules['class_id'] = 'required|exists:classes,id';
            }
            if ($roleName === 'guru bk') {
                $rules['bk_class_ids'] = 'required|array|min:1';
            }
            if ($roleName === 'guru' || $roleName === 'wali kelas') {
                $rules['subject'] = 'required|string|max:100';
            }
            if ($roleName === 'siswa') {
                // [VALIDASI NIP & NIS] Khusus Siswa saat tambah: NIS wajib angka (panjang bebas) dan unik
                $rules['nis'] = IdentityNumberValidator::nisRules($role->id);
            }
            if (in_array($roleName, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true)) {
                // [VALIDASI NIP & NIS] Khusus Guru/Staf saat tambah: NIP wajib angka maks. 18 digit dan unik
                $rules['nip'] = IdentityNumberValidator::nipRules(required: true);
            }
            if ($roleName === 'orang tua') {
                $rules['parent_class_id'] = 'required|exists:classes,id';
                $rules['student_id'] = 'required|integer|exists:users,id';
                $rules['username'] = 'nullable|string|max:50';
                $rules['email'] = 'nullable|email|max:100';
            } elseif ($roleName !== '') {
                $rules['username'] = 'required|string|max:50|unique:users,username';
                $rules['email'] = 'required|email|max:100|unique:users,email';
            }
        }

        $request->validate($rules, IdentityNumberValidator::messages());

        if ($roleName === 'orang tua') {
            $student = User::whereKey($request->student_id)
                ->where('class_id', $request->parent_class_id)
                ->whereNotNull('nis')
                ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
                ->first();

            if (! $student) {
                return back()->withErrors([
                    'student_id' => 'Pilih siswa pada kelas tersebut yang sudah memiliki NIS.',
                ])->withInput();
            }
            $existingParent = $student->parent;
            if ($existingParent && ! $existingParent->isOrangTua()) {
                return back()->withErrors([
                    'student_id' => 'Akun yang tertaut ke siswa bukan akun dengan role Orang Tua.',
                ])->withInput();
            }
            if (Hash::check($request->password, $student->password)) {
                return back()->withErrors([
                    'password' => 'Password Orang Tua harus berbeda dari password siswa.',
                ])->withInput();
            }

            $username = $request->input('username') ?: ($existingParent?->username ?: 'ortu.'.$student->nis);
            if (User::where('username', $username)
                ->when($existingParent, fn ($query) => $query->where('id', '!=', $existingParent->id))
                ->exists()) {
                return back()->withErrors([
                    'username' => 'Username tersebut sudah digunakan. Kosongkan agar username otomatis memakai NIS siswa.',
                ])->withInput();
            }
            if ($request->filled('email') && User::where('email', $request->email)
                ->when($existingParent, fn ($query) => $query->where('id', '!=', $existingParent->id))
                ->exists()) {
                return back()->withErrors(['email' => 'Email tersebut sudah digunakan.'])->withInput();
            }

            DB::transaction(function () use ($request, $role, $student, $username, $existingParent) {
                $parentData = [
                    'fullname' => $request->fullname,
                    'username' => $username,
                    'email' => $request->email ?: null,
                    'password' => Hash::make($request->password),
                    'role_id' => $role->id,
                    'class_id' => $student->class_id,
                    'nis' => $student->nis,
                ];
                if ($existingParent) {
                    $existingParent->update($parentData);
                    $parent = $existingParent;
                } else {
                    $parent = User::create($parentData);
                }
                $student->update(['parent_id' => $parent->id]);
            });

            return back()->with('success', $existingParent
                ? 'Akun Orang Tua berhasil diperbarui dan ditautkan ke siswa.'
                : 'Akun Orang Tua berhasil ditambahkan dan ditautkan ke siswa.');
        }

        DB::transaction(function () use ($request, $roleName) {
            $user = User::create([
                'fullname' => $request->fullname,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'class_id' => in_array($roleName, ['siswa', 'wali kelas'], true) ? $request->class_id : null,
                'subject' => in_array($roleName, ['guru', 'wali kelas'], true) ? $request->subject : null,
                'nis' => $roleName === 'siswa' ? $request->nis : null,
                'nip' => in_array($roleName, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true) ? ($request->filled('nip') ? $request->nip : null) : null,
            ]);
            $user->bkClasses()->sync($roleName === 'guru bk' ? $request->input('bk_class_ids', []) : []);
        });

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,'.$id,
            'email' => 'nullable|email|max:100|unique:users,email,'.$id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
            'subject' => 'nullable|string|max:100',
            // [VALIDASI NIP & NIS] NIS wajib angka (panjang bebas); NIP wajib angka maks. 18 digit
            'nis' => IdentityNumberValidator::optionalNisFormatRules(),
            'nip' => IdentityNumberValidator::optionalNipFormatRules(),
            'student_id' => 'nullable|integer|exists:users,id',
            'bk_class_ids' => 'sometimes|array',
            'bk_class_ids.*' => 'required|integer|distinct|exists:classes,id',
        ];

        $role = Role::find($request->role_id);
        $roleName = strtolower($role?->role_name ?? '');

        if ($role) {
            if ($roleName === 'siswa' || $roleName === 'wali kelas') {
                $rules['class_id'] = 'required|exists:classes,id';
            }
            if ($roleName === 'guru bk') {
                $rules['bk_class_ids'] = 'required|array|min:1';
            }
            if ($roleName === 'guru' || $roleName === 'wali kelas') {
                $rules['subject'] = 'required|string|max:100';
            }
            if ($roleName === 'siswa') {
                // [VALIDASI NIP & NIS] Khusus Siswa saat edit: NIS wajib angka (panjang bebas) dan unik
                $rules['nis'] = IdentityNumberValidator::nisRules($role->id, ignoreUserId: $user->id);
            }
            if (in_array($roleName, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true)) {
                // [VALIDASI NIP & NIS] Khusus Guru/Staf saat edit: NIP wajib angka maks. 18 digit dan unik
                $rules['nip'] = IdentityNumberValidator::nipRules(required: false, ignoreUserId: $user->id);
            }
            if ($roleName === 'orang tua') {
                $rules['student_id'] = 'nullable|integer|exists:users,id';
            }
        }

        $request->validate($rules, IdentityNumberValidator::messages());

        $data = [
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email' => $request->filled('email') ? $request->email : null,
            'role_id' => $request->role_id,
            'class_id' => in_array($roleName, ['siswa', 'wali kelas'], true) ? $request->class_id : null,
            'subject' => in_array($roleName, ['guru', 'wali kelas'], true) ? $request->subject : null,
            'nis' => $roleName === 'siswa' ? $request->nis : null,
            'nip' => in_array($roleName, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true) ? ($request->filled('nip') ? $request->nip : null) : null,
        ];

        if ($roleName === 'orang tua' && $request->filled('student_id')) {
            $student = User::whereKey($request->student_id)->whereNotNull('nis')->first();
            if ($student) {
                $data['class_id'] = $student->class_id;
                $data['nis'] = $student->nis;
                User::where('parent_id', $user->id)->where('id', '!=', $student->id)->update(['parent_id' => null]);
                $student->update(['parent_id' => $user->id]);
            }
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->bkClasses()->sync($roleName === 'guru bk'
            ? $request->input('bk_class_ids', [])
            : []);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil diupdate.',
                'data' => $user->load(['role', 'class', 'bkClasses', 'children']),
            ]);
        }

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function setWakaAccess(Request $request, $id)
    {
        abort_unless($request->user()?->isWaka(), 403);

        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user = User::with('role')->findOrFail($id);
        abort_unless(in_array($user->role?->role_name, ['Guru', 'Wali Kelas', 'Guru BK'], true), 422);

        $wakaRole = Role::where('role_name', 'Waka Kurikulum')->firstOrFail();
        if ($request->boolean('enabled')) {
            $user->additionalRoles()->syncWithoutDetaching([$wakaRole->id]);
        } else {
            $user->additionalRoles()->detach($wakaRole->id);
        }

        return back()->with(
            'success',
            $request->boolean('enabled')
                ? "Akses Waka berhasil diberikan kepada {$user->fullname}."
                : "Akses Waka berhasil dicabut dari {$user->fullname}."
        );
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        User::findOrFail($id)->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    public function deleteAllUsers(Request $request)
    {
        $waka = $request->user();
        abort_unless($waka?->isWaka(), 403);

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in(['HAPUS SEMUA USER'])],
        ], [
            'confirmation.in' => 'Ketik HAPUS SEMUA USER dengan tepat untuk melanjutkan.',
        ]);

        $deletedCount = DB::transaction(function () use ($waka) {
            SystemConfiguration::query()->update(['updated_by' => $waka->id]);

            return User::where('id', '!=', $waka->id)->delete();
        });

        return redirect()->route('waka.master.users')->with(
            'success',
            "{$deletedCount} akun berhasil dihapus. Akun Waka yang sedang digunakan dan konfigurasi sistem tetap dipertahankan; data terkait user yang dihapus ikut terhapus."
        );
    }

    public function downloadUserTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama Lengkap');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Username');
        $sheet->setCellValue('E1', 'Role');
        $sheet->setCellValue('F1', 'Kelas');
        $sheet->setCellValue('G1', 'Mata Pelajaran');
        $sheet->setCellValue('H1', 'NIS');
        $sheet->setCellValue('I1', 'NIS Siswa (untuk Orang Tua)');
        $sheet->setCellValue('J1', 'NIP');
        $sheet->setCellValue('K1', 'Password');

        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'Budi Santoso');
        $sheet->setCellValue('C2', 'budi@email.com');
        $sheet->setCellValue('D2', 'budi.santoso');
        $sheet->setCellValue('E2', 'Guru');
        $sheet->setCellValue('F2', 'X RPL 1');
        $sheet->setCellValue('G2', 'Pemrograman Web');
        $sheet->setCellValueExplicit('J2', '1987654321', DataType::TYPE_STRING);
        $sheet->setCellValue('K2', 'password123');

        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', 'Ahmad Fauzi');
        $sheet->setCellValue('C3', 'ahmad@email.com');
        $sheet->setCellValue('D3', 'ahmad.fauzi');
        $sheet->setCellValue('E3', 'Siswa');
        $sheet->setCellValue('F3', 'X RPL 1');
        $sheet->setCellValue('G3', '');
        $sheet->setCellValueExplicit('H3', '20260001', DataType::TYPE_STRING);
        $sheet->setCellValue('K3', 'password123');

        $sheet->setCellValue('A4', 3);
        $sheet->setCellValue('B4', 'Orang Tua Ahmad');
        $sheet->setCellValue('C4', 'ortu.ahmad@email.com');
        $sheet->setCellValue('E4', 'Orang Tua');
        $sheet->setCellValue('F4', 'X RPL 1');
        $sheet->setCellValueExplicit('I4', '20260001', DataType::TYPE_STRING);
        $sheet->setCellValue('K4', 'ortu12345');

        $columnWidths = [
            'A' => 7, 'B' => 22, 'C' => 28, 'D' => 22, 'E' => 16, 'F' => 16,
            'G' => 24, 'H' => 16, 'I' => 25, 'J' => 18, 'K' => 18,
        ];
        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('A2:K4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:K4')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('D1D5DB');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K4');

        $filename = 'template_import_user_scp.xlsx';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'user-template-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Tidak dapat menyiapkan file template pengguna.');
        }

        $path = $temporaryPath.'.xlsx';
        try {
            if (! rename($temporaryPath, $path)) {
                throw new \RuntimeException('Tidak dapat menyiapkan file template pengguna.');
            }
            (new Xlsx($spreadsheet))->save($path);
        } catch (\Throwable $exception) {
            @unlink($temporaryPath);
            @unlink($path);
            throw $exception;
        }

        // TAMBAHAN: Membersihkan output buffer yang bocor sebelum mengirimkan file
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    // Impor data pengguna dari file Excel (.xlsx atau .csv)
    public function importUsers(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $issues = [];
        $rows = [];
        $passwordCache = [];
        $passwordCheckCache = [];
        $passwordMatchesHash = static function (string $password, string $hashedPassword) use (&$passwordCheckCache): bool {
            $cacheKey = hash('sha256', $hashedPassword."\0".$password);

            if (! array_key_exists($cacheKey, $passwordCheckCache)) {
                $passwordCheckCache[$cacheKey] = Hash::check($password, $hashedPassword);
            }

            return $passwordCheckCache[$cacheKey];
        };
        $headers = $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().'1', null, true, false)[0];
        $headerAliases = [
            'nama lengkap' => 'fullname',
            'email' => 'email',
            'username' => 'username',
            'role' => 'role_name',
            'kelas' => 'class_name',
            'mata pelajaran' => 'subject',
            'nis' => 'nis',
            'nis siswa (untuk orang tua)' => 'student_nis',
            'nis siswa (khusus orang tua)' => 'student_nis',
            'nip' => 'nip',
            'password' => 'password',
        ];
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $canonicalHeader = $headerAliases[mb_strtolower(trim((string) $header))] ?? null;
            if ($canonicalHeader !== null) {
                $columnMap[$canonicalHeader] = Coordinate::stringFromColumnIndex($index + 1);
            }
        }
        $legacyColumns = [
            'fullname' => 'B', 'email' => 'C', 'username' => 'D', 'role_name' => 'E',
            'class_name' => 'F', 'subject' => 'G', 'nis' => 'H', 'nip' => 'I',
            'password' => 'J', 'student_nis' => 'K',
        ];
        $columnMap += $legacyColumns;
        $text = static fn (string $field, int $row): string => trim((string) $sheet->getCell($columnMap[$field].$row)->getFormattedValue());

        for ($rowNumber = 2; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
            $row = [
                'row_number' => $rowNumber,
                'fullname' => $text('fullname', $rowNumber),
                'email' => $text('email', $rowNumber),
                'username' => $text('username', $rowNumber),
                'role_name' => $text('role_name', $rowNumber),
                'class_name' => $text('class_name', $rowNumber),
                'subject' => $text('subject', $rowNumber),
                'nis' => $text('nis', $rowNumber),
                'nip' => $text('nip', $rowNumber),
                'password' => $text('password', $rowNumber),
                'student_nis' => $text('student_nis', $rowNumber),
            ];
            if ($row['fullname'] !== '' || $row['username'] !== '' || $row['role_name'] !== '') {
                $rows[] = $row;
            }
        }

        $parentRows = array_filter($rows, fn ($row) => strtolower($row['role_name']) === 'orang tua');
        $otherRows = array_filter($rows, fn ($row) => strtolower($row['role_name']) !== 'orang tua');
        $linkedStudentsInImport = [];
        $assignedUsernames = [];

        foreach (array_merge(array_values($otherRows), array_values($parentRows)) as $row) {
            $rowNumber = $row['row_number'];
            $fullname = $row['fullname'];
            $email = $row['email'];
            $username = $row['username'];
            $role = Role::where('role_name', $row['role_name'])->first();
            $roleKey = strtolower($row['role_name']);
            $class = $this->resolveClass($row['class_name']);
            $isParent = $roleKey === 'orang tua';
            $isStudent = $roleKey === 'siswa';
            $isStaffWithNip = in_array($roleKey, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true);
            $nis = $isParent ? $row['student_nis'] : $row['nis'];
            $passwordInput = $row['password'];
            $password = $passwordInput ?: 'password123';

            // Auto-clean email: jika domain tidak valid (misal 'smkn1bantul.com' tanpa user), jadikan null
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = '';
            }

            // Auto-resolve duplikasi username dalam satu batch import (misal 'raden.rara')
            if ($username !== '') {
                if (isset($assignedUsernames[$username])) {
                    $assignedUsernames[$username]++;
                    $username = $username . '.' . $assignedUsernames[$username];
                } else {
                    $assignedUsernames[$username] = 1;
                }
            }

            $student = $isParent
                ? User::where('nis', $nis)
                    ->where('class_id', $class?->id)
                    ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
                    ->first()
                : null;
            if ($isParent && $username === '' && $nis !== '') {
                $username = 'ortu.'.$nis;
            }
            $existingUser = $username === '' ? null : User::where('username', $username)->first();
            $existingParent = $isParent
                ? ($student?->parent ?: User::where('nis', $nis)->where('role_id', $role?->id)->first())
                : null;

            $issue = null;
            if (! $fullname || ! $role) {
                $issue = 'Nama dan role harus diisi dengan benar.';
            } elseif (mb_strlen($fullname) > 100 || ($username !== '' && mb_strlen($username) > 50)) {
                $issue = 'Nama maksimal 100 karakter dan username maksimal 50 karakter.';
            } elseif (! $isParent && $username === '') {
                $issue = 'Username wajib diisi untuk role selain Orang Tua.';
            } elseif ($isParent && (! $class || $nis === '' || ! $student)) {
                $issue = 'Baris Orang Tua wajib mengisi kelas dan NIS siswa yang terdaftar pada kelas tersebut.';
            } elseif ($isParent && isset($linkedStudentsInImport[$student->id])) {
                $issue = 'Siswa hanya boleh ditautkan ke satu baris akun Orang Tua dalam satu file import.';
            } elseif ($isParent && ! $passwordInput) {
                $issue = 'Password wajib diisi untuk baris Orang Tua.';
            } elseif ($isParent && $student->parent_id !== null &&
                (! $existingParent || (int) $student->parent_id !== (int) $existingParent->id)) {
                $issue = 'Siswa sudah tertaut ke akun Orang Tua lain.';
            } elseif ($isParent && $existingUser &&
                (! $existingParent || (int) $existingUser->id !== (int) $existingParent->id)) {
                $issue = 'Username tersebut sudah digunakan oleh akun lain.';
            } elseif ($existingUser && (int) $existingUser->role_id !== (int) $role->id) {
                $issue = 'Username sudah dipakai akun dengan role berbeda.';
            } elseif ($email !== '' && (! filter_var($email, FILTER_VALIDATE_EMAIL) ||
                User::where('email', $email)->when($existingUser, fn ($query) => $query->where('id', '!=', $existingUser->id))
                    ->when($existingParent, fn ($query) => $query->where('id', '!=', $existingParent->id))->exists())) {
                $issue = 'Email tidak valid atau sudah digunakan.';
            } elseif ($row['class_name'] !== '' && ! $class) {
                $issue = 'Kelas tidak ditemukan.';
            } elseif (($isStudent || $roleKey === 'wali kelas') && ! $class) {
                $issue = 'Siswa dan Wali Kelas wajib memiliki kelas yang valid.';
            } elseif ($roleKey === 'guru bk') {
                // Guru BK tidak wajib memiliki kelas di baris excel (bisa diatur lewat menu Data Guru)
            } elseif ($isStudent && $nis === '') {
                $issue = 'NIS wajib diisi untuk siswa.';
            } elseif ($isStudent && ! IdentityNumberValidator::isValidNis($nis)) {
                // [VALIDASI NIP & NIS] Saat import Excel/CSV: NIS wajib angka (panjang bebas, tanpa huruf)
                $issue = 'NIS wajib berupa angka dan tidak boleh mengandung huruf.';
            } elseif ($isStudent && User::where('nis', $nis)->where('role_id', $role->id)
                ->when($existingUser, fn ($query) => $query->where('id', '!=', $existingUser->id))->exists()) {
                $issue = 'NIS siswa sudah digunakan.';
            } elseif ($isStaffWithNip && $row['nip'] === '') {
                $issue = 'NIP wajib diisi untuk Guru, Guru BK, Wali Kelas, dan Waka Kurikulum.';
            } elseif ($row['nip'] !== '' && ! IdentityNumberValidator::isValidNip($row['nip'])) {
                // [VALIDASI NIP & NIS] Saat import Excel/CSV: NIP wajib angka maksimal 18 digit
                $issue = 'NIP wajib berupa angka dan maksimal 18 digit (tidak boleh mengandung huruf).';
            } elseif ($row['nip'] !== '' && User::where('nip', $row['nip'])
                ->when($existingUser, fn ($query) => $query->where('id', '!=', $existingUser->id))->exists()) {
                $issue = 'NIP sudah digunakan.';
            } elseif ($passwordInput !== '' && mb_strlen($passwordInput) < 6) {
                $issue = 'Password minimal 6 karakter.';
            } elseif ($isParent && $passwordMatchesHash($passwordInput, $student->password)) {
                $issue = 'Password Orang Tua harus berbeda dari password siswa.';
            }

            if ($issue !== null) {
                $skipped++;
                $issues[] = "Baris {$rowNumber}: {$issue}";

                continue;
            }

            $targetUser = $isParent ? ($existingParent ?: $existingUser) : $existingUser;
            if ($isParent && $targetUser && (int) $targetUser->role_id !== (int) $role->id) {
                $skipped++;
                $issues[] = "Baris {$rowNumber}: akun yang akan diperbarui bukan role Orang Tua.";

                continue;
            }

            DB::transaction(function () use ($row, $role, $roleKey, $class, $username, $password, $passwordInput, $targetUser, $student, $email, &$passwordCache) {
                $userData = [
                    'fullname' => $row['fullname'],
                    'username' => $username,
                    'email' => $email ?: null,
                    'role_id' => $role->id,
                    'class_id' => $roleKey === 'guru bk' ? null : $class?->id,
                    'subject' => in_array($roleKey, ['guru', 'wali kelas'], true) ? ($row['subject'] ?: null) : null,
                    'nis' => $roleKey === 'siswa' ? $row['nis'] : ($roleKey === 'orang tua' ? $student->nis : null),
                    'nip' => in_array($roleKey, ['guru', 'wali kelas', 'guru bk', 'waka kurikulum'], true) ? ($row['nip'] ?: null) : null,
                ];
                if (! $targetUser || $passwordInput !== '') {
                    if (! isset($passwordCache[$password])) {
                        $passwordCache[$password] = Hash::make($password);
                    }
                    $userData['password'] = $passwordCache[$password];
                }

                if ($targetUser) {
                    $targetUser->update($userData);
                    $parent = $targetUser;
                } else {
                    $parent = User::create($userData);
                }

                if ($roleKey === 'guru bk' && $class) {
                    $parent->bkClasses()->syncWithoutDetaching([$class->id]);
                }
                if ($roleKey === 'orang tua') {
                    $student->update(['parent_id' => $parent->id]);
                }
            });

            $targetUser ? $updated++ : $created++;
            if ($isParent) {
                $linkedStudentsInImport[$student->id] = true;
            }
        }

        return back()
            ->with('success', "Import selesai. Baris dibuat: {$created}, diperbarui: {$updated}, dilewati: {$skipped}. Password akun baru kosong memakai password123; password Orang Tua wajib diisi.")
            ->with('import_issues', $issues);
    }

    // ==================== MATA PELAJARAN ====================
    public function subjects(Request $request)
    {
        $config = SystemConfiguration::current();
        $cycle = $request->get('cycle', $config->active_cycle);

        $query = Schedule::with(['teacher', 'class', 'room']);
        if (in_array((string) $cycle, ['1', '2'], true)) {
            $query->where('cycle_type', (string) $cycle);
        }

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $schedules = $query->get();

        $subjects = $schedules->groupBy('subject')->map(function ($items, $subject) {
            return [
                'subject' => $subject,
                'teachers' => $items->pluck('teacher.fullname')->filter()->unique()->sort()->values(),
                'classes' => $items->pluck('class.class_name')->filter()->unique()->sort()->values(),
                'rooms' => $items->pluck('room.room_name')->filter()->unique()->sort()->values(),
                'total_sessions' => $items->count(),
                'schedules' => $items->values(),
            ];
        })->sortBy('subject', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $teacherRoleNames = ['Guru', 'Wali Kelas', 'Guru BK'];
        $teachers = User::where(function ($q) use ($teacherRoleNames) {
            $q->whereHas('role', fn ($r) => $r->whereIn('role_name', $teacherRoleNames))
                ->orWhereHas('additionalRoles', fn ($r) => $r->whereIn('role_name', $teacherRoleNames));
        })
            ->orderBy('fullname')
            ->get();

        return view('waka.master.subjects', compact('subjects', 'teachers', 'cycle', 'config'));
    }

    // ==================== KENAIKAN KELAS ====================
    public function promotion(Request $request)
    {
        $classes = Classes::orderBy('class_name')->get();

        $selectedClass = null;
        $students = collect();
        $targetClasses = collect();
        $defaultTargetClass = null;

        if ($request->filled('class_id')) {
            $selectedClass = Classes::find($request->class_id);
            if ($selectedClass) {
                $students = User::where('class_id', $selectedClass->id)
                    ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
                    ->where('is_alumni', false)
                    ->orderBy('fullname')
                    ->get();

                if ($selectedClass->grade !== 'XII') {
                    $nextGrade = $selectedClass->grade === 'X' ? 'XI' : 'XII';

                    // Rombel tujuan pada tingkat berikutnya
                    $targetClasses = Classes::where('grade', $nextGrade)
                        ->where(function ($q) use ($selectedClass) {
                            $q->where('major', $selectedClass->major)
                              ->orWhereNull('major');
                        })
                        ->withCount(['users as active_students_count' => function ($q) {
                            $q->whereHas('role', fn ($r) => $r->where('role_name', 'Siswa'))
                              ->where('is_alumni', false);
                        }])
                        ->orderBy('section')
                        ->orderBy('class_name')
                        ->get();

                    if ($targetClasses->isEmpty()) {
                        $targetClasses = Classes::where('grade', $nextGrade)
                            ->withCount(['users as active_students_count' => function ($q) {
                                $q->whereHas('role', fn ($r) => $r->where('role_name', 'Siswa'))
                                  ->where('is_alumni', false);
                            }])
                            ->orderBy('class_name')
                            ->get();
                    }

                    $defaultTargetClass = $targetClasses->firstWhere('section', $selectedClass->section)
                        ?? $targetClasses->first();
                }
            }
        }

        $totalSiswa = User::whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->count();

        $kelasTerisi = Classes::whereHas('users', function ($q) {
            $q->whereHas('role', fn ($r) => $r->where('role_name', 'Siswa'))
                ->where('is_alumni', false);
        })->count();

        $totalKelas = $classes->count();

        $stats = [
            'total_siswa' => $totalSiswa,
            'kelas_kosong' => $totalKelas - $kelasTerisi,
            'kelas_terisi' => $kelasTerisi,
            'total_kelas' => $totalKelas,
        ];

        return view('waka.promotion', compact('classes', 'selectedClass', 'students', 'stats', 'targetClasses', 'defaultTargetClass'));
    }

    public function processPromotion(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'required|integer|distinct|exists:users,id',
            'graduate' => 'nullable|boolean',
            'target_class_id' => 'nullable|exists:classes,id',
        ]);

        if (! $request->filled('student_ids')) {
            return back()->with('error', 'Pilih minimal 1 siswa.');
        }

        $sourceClass = Classes::findOrFail($request->class_id);
        $studentIds = $request->input('student_ids');
        $students = User::where('class_id', $sourceClass->id)
            ->whereIn('id', $studentIds)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->get();

        if ($students->count() !== count($studentIds)) {
            return back()->with('error', 'Pastikan siswa yang dipilih masih aktif dan berasal dari kelas asal.');
        }

        $studentIds = $students->modelKeys();
        $incomingCount = count($studentIds);

        $parentIds = $students->pluck('parent_id')->filter()->unique()->values()->all();

        if ($request->boolean('graduate') || $sourceClass->grade === 'XII') {
            User::whereIn('id', $studentIds)->update([
                'class_id' => null,
                'is_alumni' => true,
            ]);

            \Laravel\Sanctum\PersonalAccessToken::where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $studentIds)
                ->delete();

            if (! empty($parentIds)) {
                $parents = User::whereIn('id', $parentIds)->get();
                foreach ($parents as $parent) {
                    if (! $parent->getChildrenQuery(false)->exists()) {
                        $parent->update(['class_id' => null]);
                        $parent->tokens()->delete();
                    }
                }
            }

            return back()->with('success', "{$incomingCount} siswa berhasil diluluskan menjadi Alumni dan sesi login dinonaktifkan.");
        }

        $nextGrade = $sourceClass->grade === 'X' ? 'XI' : 'XII';

        if ($request->filled('target_class_id')) {
            $targetClass = Classes::findOrFail($request->target_class_id);
        } else {
            $targetClass = Classes::where('grade', $nextGrade)
                ->where('major', $sourceClass->major)
                ->where('section', $sourceClass->section)
                ->first();
        }

        if (! $targetClass) {
            return back()->with('error', "Kelas tujuan tingkat {$nextGrade} belum tersedia. Buat kelas terlebih dahulu di Master Kelas.");
        }

        if ($targetClass->grade !== $nextGrade) {
            return back()->with('error', "Kelas tujuan ({$targetClass->class_name}) tidak sesuai tingkat. Kelas asal {$sourceClass->grade} harus naik ke tingkat {$nextGrade}.");
        }

        // Aturan Kapasitas Maksimal (36 Siswa per Rombel)
        $maxQuota = 36;
        $currentCount = User::where('class_id', $targetClass->id)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->whereNotIn('id', $studentIds)
            ->count();

        $totalProjected = $currentCount + $incomingCount;

        if ($totalProjected > $maxQuota) {
            $sisaKursi = max(0, $maxQuota - $currentCount);
            return back()->with('error', "Kapasitas kelas {$targetClass->class_name} melebihi batas maksimal ({$maxQuota} siswa). Saat ini terisi {$currentCount} siswa (sisa {$sisaKursi} kursi), namun Anda memilih {$incomingCount} siswa. Kurangi siswa yang dicentang atau pilih rombel lain.");
        }

        User::whereIn('id', $studentIds)->update([
            'class_id' => $targetClass->id,
        ]);

        if (! empty($parentIds)) {
            User::whereIn('id', $parentIds)->update([
                'class_id' => $targetClass->id,
            ]);
        }

        $pesanTinggal = '';
        $sisaSiswaAsal = User::where('class_id', $sourceClass->id)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->count();
        if ($sisaSiswaAsal > 0) {
            $pesanTinggal = " {$sisaSiswaAsal} siswa yang tidak dicentang tetap berada di {$sourceClass->class_name} (tinggal kelas).";
        }

        return back()->with('success', "{$incomingCount} siswa berhasil dinaikkan ke {$targetClass->class_name} (Kapasitas sekarang: {$totalProjected}/{$maxQuota} siswa).{$pesanTinggal}");
    }

    private function resolveClass(?string $rawClassName): ?Classes
    {
        if (!$rawClassName) {
            return null;
        }

        $trimmed = trim($rawClassName);
        if ($trimmed === '') {
            return null;
        }

        // 1. Direct match
        $class = Classes::where('class_name', $trimmed)->first();
        if ($class) {
            return $class;
        }

        // 2. Normalisasi spasi yang hilang antara jurusan dan nomor rombel (contoh: XI TKJ1 -> XI TKJ 1)
        $normalized = preg_replace('/^([X|V|I]+)\s+([A-Za-z]+)(\d+)$/i', '$1 $2 $3', $trimmed);
        $class = Classes::where('class_name', $normalized)->first();
        if ($class) {
            return $class;
        }

        // 3. Mapping alias umum (contoh: AK -> AKL, BD -> BD 1, LPS -> LPS 1)
        $alias = $normalized;
        $alias = preg_replace('/^([X|V|I]+)\s+AK\s+(\d+)$/i', '$1 AKL $2', $alias);
        $alias = preg_replace('/^([X|V|I]+)\s+AK(\d+)$/i', '$1 AKL $2', $alias);
        $alias = preg_replace('/^([X|V|I]+)\s+BD$/i', '$1 BD 1', $alias);
        $alias = preg_replace('/^([X|V|I]+)\s+LPS$/i', '$1 LPS 1', $alias);

        $class = Classes::where('class_name', $alias)->first();
        if ($class) {
            return $class;
        }

        // 4. Fallback case-insensitive
        return Classes::whereRaw('LOWER(class_name) = ?', [strtolower($trimmed)])
            ->orWhereRaw('LOWER(class_name) = ?', [strtolower($normalized)])
            ->orWhereRaw('LOWER(class_name) = ?', [strtolower($alias)])
            ->first();
    }
}
