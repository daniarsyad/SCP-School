
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

// ==================== PUBLIK ====================

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

// ==================== HARUS LOGIN ====================

Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/children', [AuthController::class, 'children']);
    Route::get('/dashboard/today', [AttendanceController::class, 'todaySummary']);

    // ==================== SYSTEM ====================

    Route::get('/system/config', [SystemController::class, 'config']);
    Route::put('/system/cycle', [SystemController::class, 'updateCycle']);
    Route::put('/system/schedule-lock', [SystemController::class, 'updateScheduleLock']);
    Route::get('/rooms', [SystemController::class, 'rooms']);

    // ==================== WAKA KURIKULUM ====================

    Route::get('/waka/dashboard-summary', [WakaController::class, 'dashboardSummary']);
    Route::put('/waka/cycle', [SystemController::class, 'updateCycle']);
    Route::put('/waka/schedule-lock', [SystemController::class, 'updateScheduleLock']);

    // ==================== SCHEDULE ====================

    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/my', [ScheduleController::class, 'mySchedules']);
    Route::get('/schedules/today', [ScheduleController::class, 'today']);
    Route::post('/schedules', [ScheduleController::class, 'store']);

    // ==================== JOURNAL ====================

    Route::post('/journals', [JournalController::class, 'store']);
    Route::get('/journals/my', [JournalController::class, 'myJournals']);
    Route::get('/journals/monitor', [JournalController::class, 'monitor']);

    // ==================== LEAVE REQUEST ====================

    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);
    Route::get('/leave-requests/pending', [LeaveRequestController::class, 'pending']);

    Route::post('/leave-requests/{id}/verify', [
        LeaveRequestController::class,
        'verify',
    ]);
    Route::post('/leave-requests/{id}/cancel', [
        LeaveRequestController::class,
        'cancel',
    ]);

    // ==================== ATTENDANCE ====================

    Route::get('/attendances/journal/{journalId}', [
        AttendanceController::class,
        'byJournal',
    ]);

    Route::get('/attendances/report', [
        AttendanceController::class,
        'report',
    ]);
    Route::get('/attendances/report/filters', [
        AttendanceController::class,
        'reportFilters',
    ]);
    Route::get('/attendances/report/export', [
        AttendanceController::class,
        'exportReport',
    ]);

    Route::put('/attendances/{id}', [
        AttendanceController::class,
        'updateStatus',
    ]);

    Route::post('/attendances/{id}/status', [
        AttendanceController::class,
        'updateStatus',
    ]);

    Route::get('/attendances/my', [
        AttendanceController::class,
        'myAttendances',
    ]);

});
