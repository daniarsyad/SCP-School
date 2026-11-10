<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\WakaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// ==================== DEPLOYMENT WEBHOOK (NO-SSH SHARED HOSTING) ====================
$deployHandler = function (Request $request, ?string $secret = null) {
    $configuredSecret = config('app.deploy_secret');

    if (empty($configuredSecret)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Akses ditolak: DEPLOY_SECRET belum diatur di file .env server.',
        ], 403);
    }

    $providedSecret = $request->header('X-Deploy-Secret')
        ?? $request->input('secret')
        ?? $secret;

    if (!$providedSecret || !hash_equals((string) $configuredSecret, (string) $providedSecret)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Akses ditolak: token rahasia salah atau tidak cocok.',
        ], 403);
    }

    try {
        // 1. Jalankan migrasi database
        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = trim(Artisan::output());

        // 2. Jalankan seeder hanya jika parameter seed=1 disertakan
        $seedOutput = 'Seeding dilewati.';
        if ($request->query('seed') === '1' || $request->input('seed') === '1') {
            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = trim(Artisan::output());
        }

        // 3. Buat symlink storage jika belum ada
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {}

        // 4. Bersihkan cache optimasi lama
        try {
            Artisan::call('optimize:clear');
        } catch (\Throwable $e) {}

        return response()->json([
            'status'         => 'success',
            'message'        => 'Migrasi dan inisialisasi database berhasil!',
            'migrate_output' => $migrateOutput,
            'seed_output'    => $seedOutput,
        ]);
    } catch (\Throwable $e) {
        Log::error('Deploy Migration Error: ' . $e->getMessage(), [
            'exception' => $e,
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Gagal menjalankan migrasi: ' . $e->getMessage(),
        ], 500);
    }
};

Route::match(['get', 'post'], '/deploy-migrate', $deployHandler)->middleware('throttle:10,1');
Route::match(['get', 'post'], '/deploy-migrate-{secret}', $deployHandler)->middleware('throttle:10,1');

// =========================================================================
// Autentikasi masuk API dan multi peran pengguna
// =========================================================================
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::get('/roles', [AuthController::class, 'roles']);
Route::get('/classes', [SystemController::class, 'classes']);

// ==================== LUPA PASSWORD (OTP EMAIL) ====================

// Meminta OTP melalui email (Rate limited: maks 3x per menit)
Route::post('/forgot-password/request', [
    AuthController::class,
    'requestOtp',
])->middleware('throttle:3,1');

// Verifikasi OTP (Rate limited: maks 5x per menit untuk cegah brute-force)
Route::post('/forgot-password/verify-otp', [
    AuthController::class,
    'verifyOtp',
])->middleware('throttle:5,1');

// Reset password menggunakan OTP (Rate limited: maks 5x per menit)
Route::post('/forgot-password/reset', [
    AuthController::class,
    'resetPassword',
])->middleware('throttle:5,1');

// =========================================================================
// Jalur API yang dilindungi token autentikasi Sanctum
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // Authentication & Profil Pengguna
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/children', [AuthController::class, 'children']);
    Route::get('/dashboard/today', [AttendanceController::class, 'todaySummary']);

    // =========================================================================
    // Pengaturan siklus KBM dan penguncian jadwal
    // =========================================================================
    Route::get('/system/config', [SystemController::class, 'config']);
    Route::put('/system/cycle', [SystemController::class, 'updateCycle']);
    Route::put('/system/schedule-lock', [SystemController::class, 'updateScheduleLock']);
    Route::get('/rooms', [SystemController::class, 'rooms']);

    // ==================== WAKA KURIKULUM (API) ====================
    Route::get('/waka/dashboard-summary', [WakaController::class, 'dashboardSummary']);
    Route::put('/waka/cycle', [SystemController::class, 'updateCycle']);
    Route::put('/waka/schedule-lock', [SystemController::class, 'updateScheduleLock']);

    // Daftar jadwal KBM
    Route::get('/schedules', [ScheduleController::class, 'index']);
    // Jadwal mengajar guru yang sedang masuk
    Route::get('/schedules/my', [ScheduleController::class, 'mySchedules']);
    // Jadwal pelajaran hari ini
    Route::get('/schedules/today', [ScheduleController::class, 'today']);
    // Tambah jadwal mengajar baru
    Route::post('/schedules', [ScheduleController::class, 'store']);

    // Simpan jurnal KBM guru dan presensi massal siswa
    Route::post('/journals', [JournalController::class, 'store']);
    // Riwayat jurnal mengajar guru
    Route::get('/journals/my', [JournalController::class, 'myJournals']);
    // Pemantauan jurnal harian sekolah
    Route::get('/journals/monitor', [JournalController::class, 'monitor']);

    // Pengajuan izin atau sakit oleh siswa dan orang tua
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    // Riwayat pengajuan izin pribadi
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);
    // Daftar pengajuan izin yang menunggu verifikasi
    Route::get('/leave-requests/pending', [LeaveRequestController::class, 'pending']);

    // Tindakan verifikasi izin siswa (setujui atau tolak)
    Route::post('/leave-requests/{id}/verify', [
        LeaveRequestController::class,
        'verify',
    ]);
    // Batalkan pengajuan izin
    Route::post('/leave-requests/{id}/cancel', [
        LeaveRequestController::class,
        'cancel',
    ]);

    // Data presensi siswa berdasarkan jurnal
    Route::get('/attendances/journal/{journalId}', [
        AttendanceController::class,
        'byJournal',
    ]);

    // Rekapitulasi presensi dan perhitungan persentase kehadiran
    Route::get('/attendances/report', [
        AttendanceController::class,
        'report',
    ]);
    Route::get('/attendances/report/filters', [
        AttendanceController::class,
        'reportFilters',
    ]);
    // Ekspor laporan rekapitulasi presensi ke spreadsheet Excel (.xlsx)
    Route::get('/attendances/report/export', [
        AttendanceController::class,
        'exportReport',
    ]);

    // Edit dan ralat status presensi siswa oleh guru
    Route::put('/attendances/{id}', [
        AttendanceController::class,
        'updateStatus',
    ]);

    Route::post('/attendances/{id}/status', [
        AttendanceController::class,
        'updateStatus',
    ]);

    // Riwayat kehadiran pribadi siswa dan orang tua
    Route::get('/attendances/my', [
        AttendanceController::class,
        'myAttendances',
    ]);

});
