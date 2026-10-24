
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\AttendanceController;

// ==================== PUBLIK ====================

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/roles', [AuthController::class, 'roles']);
Route::get('/classes', [SystemController::class, 'classes']);

// ==================== LUPA PASSWORD (OTP EMAIL) ====================

// Meminta OTP melalui email
Route::post('/forgot-password/request', [
    AuthController::class,
    'requestOtp'
]);

// Verifikasi OTP
Route::post('/forgot-password/verify-otp', [
    AuthController::class,
    'verifyOtp'
]);

// Reset password menggunakan OTP
Route::post('/forgot-password/reset', [
    AuthController::class,
    'resetPassword'
]);

// ==================== HARUS LOGIN ====================

Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/children', [AuthController::class, 'children']);

    // ==================== SYSTEM ====================

    Route::get('/system/config', [SystemController::class, 'config']);
    Route::get('/rooms', [SystemController::class, 'rooms']);

    // ==================== SCHEDULE ====================

    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/my', [ScheduleController::class, 'mySchedules']);
    Route::get('/schedules/today', [ScheduleController::class, 'today']);
    Route::post('/schedules', [ScheduleController::class, 'store']);

    // ==================== JOURNAL ====================

    Route::post('/journals', [JournalController::class, 'store']);
    Route::get('/journals/my', [JournalController::class, 'myJournals']);

    // ==================== LEAVE REQUEST ====================

    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);
    Route::get('/leave-requests/pending', [LeaveRequestController::class, 'pending']);

    Route::post('/leave-requests/{id}/verify', [
        LeaveRequestController::class,
        'verify'
    ]);

    // ==================== ATTENDANCE ====================

    Route::get('/attendances/journal/{journalId}', [
        AttendanceController::class,
        'byJournal'
    ]);

    Route::get('/attendances/report', [
        AttendanceController::class,
        'report'
    ]);

    Route::put('/attendances/{id}', [
        AttendanceController::class,
        'updateStatus'
    ]);

    Route::post('/attendances/{id}/status', [
        AttendanceController::class,
        'updateStatus'
    ]);

    Route::get('/attendances/my', [
        AttendanceController::class,
        'myAttendances'
    ]);

});
