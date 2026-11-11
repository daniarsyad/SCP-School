<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Waka\DashboardController;
use App\Http\Controllers\Waka\ProfileController;
use App\Http\Controllers\Waka\SystemConfigController;
use App\Http\Controllers\Waka\MasterDataController;
use App\Http\Controllers\Waka\ScheduleController;


// HALAMAN PUBLIK & DOWNLOAD APK
Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/unduh-apk', function () {
    $path = public_path('download/scp.apk');
    if (file_exists($path)) {
        return response()->download($path, 'SCP.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
    return redirect()->route('landing')->with('error', 'File aplikasi sedang dipersiapkan.');
})->name('unduh.apk');


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');

// Route Lupa Password (3 Langkah) dengan Rate Limiting
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password/request', [AuthController::class, 'requestOtp'])->name('password.otp')->middleware('throttle:3,1');
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp'])->name('password.verify-otp')->middleware('throttle:5,1');
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->name('password.reset')->middleware('throttle:5,1');



Route::middleware(['auth', 'waka'])->prefix('waka')->name('waka.')->group(function () {
    // Profil & Akun Waka
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Dashboard Waka
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Pergantian siklus KBM (Putaran 1 dan Putaran 2)
    Route::post('/toggle-cycle', [SystemConfigController::class, 'toggleCycle'])->name('toggle.cycle');
    // Penguncian jadwal KBM (kunci atau buka jadwal)
    Route::post('/toggle-lock', [SystemConfigController::class, 'toggleLock'])->name('toggle.lock');
    
    // Master Data - Kelas
    Route::get('/master/classes', [MasterDataController::class, 'classes'])->name('master.classes');
    Route::post('/master/classes', [MasterDataController::class, 'storeClass'])->name('master.classes.store');
    Route::put('/master/classes/{id}', [MasterDataController::class, 'updateClass'])->name('master.classes.update');
    Route::delete('/master/classes/{id}', [MasterDataController::class, 'deleteClass'])->name('master.classes.delete');
    
    // Master Data - Ruangan
    Route::get('/master/rooms', [MasterDataController::class, 'rooms'])->name('master.rooms');
    Route::post('/master/rooms', [MasterDataController::class, 'storeRoom'])->name('master.rooms.store');
    Route::put('/master/rooms/{id}', [MasterDataController::class, 'updateRoom'])->name('master.rooms.update');
    Route::delete('/master/rooms/{id}', [MasterDataController::class, 'deleteRoom'])->name('master.rooms.delete');
    
    // Master Data - User (Termasuk Import Excel Data Pengguna)
    Route::get('/master/users', [MasterDataController::class, 'users'])->name('master.users');
    Route::post('/master/users', [MasterDataController::class, 'storeUser'])->name('master.users.store');
    Route::put('/master/users/{id}', [MasterDataController::class, 'updateUser'])->name('master.users.update');
    Route::put('/master/users/{id}/waka-access', [MasterDataController::class, 'setWakaAccess'])->name('master.users.waka-access');
    Route::delete('/master/users/{id}', [MasterDataController::class, 'deleteUser'])->name('master.users.delete');
    Route::delete('/master/users', [MasterDataController::class, 'deleteAllUsers'])->name('master.users.delete-all');
    Route::get('/master/users/template', [MasterDataController::class, 'downloadUserTemplate'])->name('master.users.template');
    Route::post('/master/users/import', [MasterDataController::class, 'importUsers'])->name('master.users.import');

    // Data Guru
    Route::get('/teachers', [MasterDataController::class, 'teachers'])->name('teachers.index');
    Route::post('/teachers', [MasterDataController::class, 'storeTeacher'])->name('teachers.store');
    Route::put('/teachers/{id}', [MasterDataController::class, 'updateTeacher'])->name('teachers.update');
    Route::delete('/teachers/{id}', [MasterDataController::class, 'deleteTeacher'])->name('teachers.delete');
    
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    // Unduh template Excel untuk impor jadwal KBM
    Route::get('/schedules/import/template', [ScheduleController::class, 'downloadImportTemplate'])->name('schedules.import.template');
    // Impor jadwal KBM dari file Excel (.xlsx atau .csv)
    Route::post('/schedules/import', [ScheduleController::class, 'import'])->name('schedules.import');
    // Tambah jadwal mengajar baru (Role Waka Kurikulum)
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    // Ubah data jadwal mengajar
    Route::put('/schedules/{id}', [ScheduleController::class, 'update'])->name('schedules.update');
    // Hapus data jadwal mengajar
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    
    // Rekapitulasi jurnal mengajar harian sekolah
    Route::get('/journal-recap', [ScheduleController::class, 'journalRecap'])->name('journal.recap');
    
    // System & History Siklus
    Route::get('/cycle-history', [SystemConfigController::class, 'history'])->name('cycle.history');
    Route::get('/cycle-history/{id}', [SystemConfigController::class, 'historyDetail'])->name('cycle.history.detail');
    Route::get('/cycle-history/{id}/kelas/{classId}', [SystemConfigController::class, 'historyByClass'])->name('cycle.history.class');
    Route::get('/system-config', [SystemConfigController::class, 'index'])->name('system.config');
    
    // Kenaikan Kelas
    Route::get('/kenaikan-kelas', [MasterDataController::class, 'promotion'])->name('promotion');
    Route::post('/kenaikan-kelas/proses', [MasterDataController::class, 'processPromotion'])->name('promotion.process');

    Route::get('/master/subjects', [MasterDataController::class, 'subjects'])->name('master.subjects');

    // Daftar siswa per kelas + Rekapitulasi Presensi
    Route::get('/master/classes/{id}/students', [MasterDataController::class, 'classStudents'])->name('master.classes.students');

    // Cetak dan ekspor rekapitulasi presensi siswa per kelas ke format PDF
    Route::get('/master/classes/{id}/print-attendance', [MasterDataController::class, 'printClassAttendance'])->name('master.classes.print-attendance');
    // Cetak dan ekspor laporan jurnal KBM per kelas ke format PDF
    Route::get('/master/classes/{id}/print-journals', [MasterDataController::class, 'printClassJournals'])->name('master.classes.print-journals');
    // Cetak dan ekspor rekapitulasi presensi per siklus ke format PDF
    Route::get('/cycle-history/{id}/print-attendance', [SystemConfigController::class, 'printCycleAttendance'])->name('cycle.history.print-attendance');
    // Cetak dan ekspor rekapitulasi presensi kelas pada siklus tertentu ke format PDF
    Route::get('/cycle-history/{id}/kelas/{classId}/print', [SystemConfigController::class, 'printClassCycleAttendance'])->name('cycle.history.class.print');
    // Cetak dan ekspor laporan jurnal KBM pada siklus tertentu ke format PDF
    Route::get('/cycle-history/{id}/print-journals', [SystemConfigController::class, 'printCycleJournals'])->name('cycle.history.print-journals');
    // Cetak dan ekspor rekapitulasi jurnal harian seluruh guru hari ini ke format PDF
    Route::get('/journal-recap/print', [ScheduleController::class, 'printTodayJournals'])->name('journal.recap.print');
});