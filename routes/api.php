<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\AttendanceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // System
    Route::get('/system/config', [SystemController::class, 'config']);

    // Schedule
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/my', [ScheduleController::class, 'mySchedules']);

    Route::get('/classes', [SystemController::class, 'classes']);
    Route::get('/rooms', [SystemController::class, 'rooms']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::post('/journals', [JournalController::class, 'store']);
    Route::get('/journals/my', [JournalController::class, 'myJournals']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('/leave-requests/my', [LeaveRequestController::class, 'myRequests']);
    Route::get('/leave-requests/pending', [LeaveRequestController::class, 'pending']);
    Route::post('/leave-requests/{id}/verify', [LeaveRequestController::class, 'verify']);
    Route::get('/attendances/journal/{journalId}', [AttendanceController::class, 'byJournal']);
    Route::get('/attendances/my', [AttendanceController::class, 'myAttendances']);
});