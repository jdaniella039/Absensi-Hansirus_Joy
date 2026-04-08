<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/my-schedule', [ScheduleController::class, 'mySchedule'])->name('schedules.my');
    Route::post('/schedules/{schedule}/acknowledge', [ScheduleController::class, 'acknowledge'])->name('schedules.acknowledge');

    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    Route::get('/verify-attendance', [AttendanceController::class, 'verifyIndex'])->name('attendance.verify-index');
    Route::post('/verify-attendance/{attendanceLog}', [AttendanceController::class, 'verify'])->name('attendance.verify');

    Route::get('/leave-approval', [LeaveController::class, 'approvalIndex'])->name('leave.approval-index');
    Route::post('/leave-approval/{leaveRequest}', [LeaveController::class, 'process'])->name('leave.process');
    Route::delete('/leave-approval/{leaveRequest}', [LeaveController::class, 'destroy'])->name('leave.destroy');

    Route::get('/feedback-inbox', [FeedbackController::class, 'adminIndex'])->name('feedback.admin-index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
});
