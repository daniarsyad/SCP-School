<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Room;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterDataController extends Controller
{
    // ==================== KELAS ====================
    public function classes(Request $request)
    {
        $query = Classes::withCount(['users', 'schedules']);

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

    public function classStudents($id)
    {
        $class = Classes::findOrFail($id);

        $students = User::where('class_id', $id)
            ->whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->orderBy('fullname')
            ->get();

        return view('waka.master.class-students', compact('class', 'students'));
    }

    public function storeClass(Request $request)
    {
        $request->validate([
            'grade'   => 'required|in:X,XI,XII',
            'major'   => 'required|string|max:20',
            'section' => 'required|string|max:10',
        ]);

        $className = $request->grade . ' ' . $request->major . ' ' . $request->section;

        if (Classes::where('class_name', $className)->exists()) {
            return back()->with('error', 'Kelas sudah ada.');
        }

        Classes::create([
            'class_name' => $className,
            'grade'      => $request->grade,
            'major'      => $request->major,
            'section'    => $request->section,
        ]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'grade'   => 'required|in:X,XI,XII',
            'major'   => 'required|string|max:20',
            'section' => 'required|string|max:10',
        ]);

        $class = Classes::findOrFail($id);
        $className = $request->grade . ' ' . $request->major . ' ' . $request->section;

        if (Classes::where('class_name', $className)->where('id', '!=', $id)->exists()) {
            return back()->with('error', 'Kelas sudah ada.');
        }

        $class->update([
            'class_name' => $className,
            'grade'      => $request->grade,
            'major'      => $request->major,
            'section'    => $request->section,
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
        $query = Room::query();

        if ($request->filled('search')) {
            $query->where('room_name', 'like', '%' . $request->search . '%');
        }

        $rooms = $query->orderBy('room_name')->get();

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
            'room_name' => 'required|string|max:50|unique:rooms,room_name,' . $id,
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
        $query = User::with(['role', 'class']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $users = $query->orderBy('fullname')->get();

        $roles = Role::orderBy('role_name')->get();
        $classes = Classes::orderBy('class_name')->get();

        $stats = [
    'total'      => User::count(),
    'waka'       => User::whereHas('role', fn($q) => $q->where('role_name', 'Waka Kurikulum'))->count(),
    'guru'       => User::whereHas('role', fn($q) => $q->where('role_name', 'Guru'))->count(),
    'walas_bk'   => User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Wali Kelas', 'Guru BK']))->count(),
    'siswa_ortu' => User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Siswa', 'Orang Tua']))
                        ->where('is_alumni', false)
                        ->count(),
        ];

        return view('waka.master.users', compact('users', 'roles', 'classes', 'stats'));
    }

    public function storeUser(Request $request)
    {
        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email'    => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
            'subject'  => 'nullable|string|max:100',
        ];

        $role = Role::find($request->role_id);
        
        if ($role) {
            $roleName = strtolower($role->role_name);
            if ($roleName === 'siswa' || $roleName === 'wali kelas') {
                $rules['class_id'] = 'required|exists:classes,id';
            }
            if ($roleName === 'guru' || $roleName === 'wali kelas') {
                $rules['subject'] = 'required|string|max:100';
            }
        }

        $request->validate($rules);

        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'class_id' => ($role && in_array(strtolower($role->role_name), ['siswa', 'wali kelas'], true)) ? $request->class_id : null,
            'subject'  => ($role && (strtolower($role->role_name) === 'guru' || strtolower($role->role_name) === 'wali kelas')) ? $request->subject : null,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'email'    => 'required|email|max:100|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
            'subject'  => 'nullable|string|max:100',
        ];

        $role = Role::find($request->role_id);
        
        if ($role) {
            $roleName = strtolower($role->role_name);
            if ($roleName === 'siswa' || $roleName === 'wali kelas') {
                $rules['class_id'] = 'required|exists:classes,id';
            }
            if ($roleName === 'guru' || $roleName === 'wali kelas') {
                $rules['subject'] = 'required|string|max:100';
            }
        }

        $request->validate($rules);

        $data = [
            'fullname' => $request->fullname,
            'username' => $request->username,
            'email'    => $request->email,
            'role_id'  => $request->role_id,
            'class_id' => ($role && in_array(strtolower($role->role_name), ['siswa', 'wali kelas'], true)) ? $request->class_id : null,
            'subject'  => ($role && (strtolower($role->role_name) === 'guru' || strtolower($role->role_name) === 'wali kelas')) ? $request->subject : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        User::findOrFail($id)->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    public function downloadUserTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama Lengkap');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Username');
        $sheet->setCellValue('E1', 'Role');
        $sheet->setCellValue('F1', 'Kelas');
        $sheet->setCellValue('G1', 'Mata Pelajaran');

        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'Budi Santoso');
        $sheet->setCellValue('C2', 'budi@email.com');
        $sheet->setCellValue('D2', 'budi.santoso');
        $sheet->setCellValue('E2', 'Guru');
        $sheet->setCellValue('F2', 'X RPL 1');
        $sheet->setCellValue('G2', 'Pemrograman Web');

        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', 'Ahmad Fauzi');
        $sheet->setCellValue('C3', 'ahmad@email.com');
        $sheet->setCellValue('D3', 'ahmad.fauzi');
        $sheet->setCellValue('E3', 'Siswa');
        $sheet->setCellValue('F3', 'X RPL 1');
        $sheet->setCellValue('G3', '');

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_import_user_scp.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $fullname  = trim($row[1] ?? '');
            $email     = trim($row[2] ?? '');
            $username  = trim($row[3] ?? '');
            $roleName  = trim($row[4] ?? '');
            $className = trim($row[5] ?? '');
            $subject   = trim($row[6] ?? '');

            if (!$fullname || !$username || !$roleName) {
                $skipped++;
                continue;
            }

            if (User::where('username', $username)->exists()) {
                $skipped++;
                continue;
            }

            $role = Role::where('role_name', $roleName)->first();
            if (!$role) {
                $skipped++;
                continue;
            }

            $classId = null;
            if ($className) {
                $class = Classes::where('class_name', $className)->first();
                $classId = $class?->id;
            }

            User::create([
                'fullname' => $fullname,
                'username' => $username,
                'email'    => $email ?: null,
                'password' => Hash::make('password123'),
                'role_id'  => $role->id,
                'class_id' => $classId,
                'subject'  => $subject ?: null,
            ]);

            $imported++;
        }

        return back()->with('success', "Import selesai. Berhasil: {$imported}, Dilewati: {$skipped}. Password default: password123");
    }

    // ==================== MATA PELAJARAN ====================
public function subjects(Request $request)
{
    $config = \App\Models\SystemConfiguration::current();

    $query = \App\Models\Schedule::with(['teacher', 'class', 'room'])
        ->where('cycle_type', $config->active_cycle);

    if ($request->filled('search')) {
        $query->where('subject', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('teacher_id')) {
        $query->where('teacher_id', $request->teacher_id);
    }

    $schedules = $query->get();

    $subjects = $schedules->groupBy('subject')->map(function ($items, $subject) {
        return [
            'subject' => $subject,
            'teachers' => $items->pluck('teacher.fullname')->filter()->unique()->values(),
            'classes' => $items->pluck('class.class_name')->filter()->unique()->values(),
            'rooms' => $items->pluck('room.room_name')->filter()->unique()->values(),
            'total_sessions' => $items->count(),
            'schedules' => $items,
        ];
    })->values();

    $teachers = User::whereHas('role', fn($q) => $q->where('role_name', 'Guru'))
        ->orderBy('fullname')
        ->get();

    return view('waka.master.subjects', compact('subjects', 'teachers'));
}

    // ==================== KENAIKAN KELAS ====================
    public function promotion(Request $request)
    {
        $classes = Classes::orderBy('class_name')->get();

        $selectedClass = null;
        $students = collect();

        if ($request->filled('class_id')) {
            $selectedClass = Classes::find($request->class_id);
            $students = User::where('class_id', $request->class_id)
                ->whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
                ->where('is_alumni', false)
                ->orderBy('fullname')
                ->get();
        }

        $totalSiswa = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->count();

        $kelasTerisi = Classes::whereHas('users', function ($q) {
            $q->whereHas('role', fn($r) => $r->where('role_name', 'Siswa'))
              ->where('is_alumni', false);
        })->count();

        $totalKelas = $classes->count();

        $stats = [
            'total_siswa'  => $totalSiswa,
            'kelas_kosong' => $totalKelas - $kelasTerisi,
            'kelas_terisi' => $kelasTerisi,
            'total_kelas'  => $totalKelas,
        ];

        return view('waka.promotion', compact('classes', 'selectedClass', 'students', 'stats'));
    }

    public function processPromotion(Request $request)
    {
        $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'graduate'    => 'nullable|boolean',
        ]);

        if (!$request->filled('student_ids')) {
            return back()->with('error', 'Pilih minimal 1 siswa.');
        }

        $sourceClass = Classes::findOrFail($request->class_id);

        if ($request->boolean('graduate') || $sourceClass->grade === 'XII') {
            User::whereIn('id', $request->student_ids)->update([
                'class_id'  => null,
                'is_alumni' => true,
            ]);

            return back()->with('success', 'Siswa berhasil diluluskan menjadi Alumni.');
        }

        $nextGrade = $sourceClass->grade === 'X' ? 'XI' : 'XII';

        $targetClass = Classes::where('grade', $nextGrade)
            ->where('major', $sourceClass->major)
            ->where('section', $sourceClass->section)
            ->first();

        if (!$targetClass) {
            return back()->with('error', "Kelas tujuan {$nextGrade} {$sourceClass->major} {$sourceClass->section} belum ada. Buat dulu di Master Kelas.");
        }

        $targetHasStudents = User::where('class_id', $targetClass->id)
            ->whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->where('is_alumni', false)
            ->exists();

        if ($targetHasStudents) {
            return back()->with('error', "Kelas tujuan {$targetClass->class_name} masih terisi siswa.");
        }

        User::whereIn('id', $request->student_ids)->update([
            'class_id' => $targetClass->id,
        ]);

        return back()->with('success', "Siswa berhasil dinaikkan ke {$targetClass->class_name}.");

        
    }
}