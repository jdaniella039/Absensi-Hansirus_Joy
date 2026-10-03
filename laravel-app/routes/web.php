<?php

// Route Laravel untuk menghubungkan URL ke controller aplikasi.
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

// Route untuk tamu: hanya user yang belum login yang boleh membuka login.
Route::middleware('guest')->group(function () {
    // URL utama diarahkan ke halaman login.
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin']);

    // Submit form login diproses oleh AuthController@login.
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

// Route untuk user yang sudah login.
Route::middleware('auth')->group(function () {
    // Logout dan dashboard utama.
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Profil user yang sedang login.
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Absensi karyawan dan jadwal milik karyawan.
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/my-schedule', [ScheduleController::class, 'mySchedule'])->name('schedules.my');
    Route::post('/schedules/{schedule}/acknowledge', [ScheduleController::class, 'acknowledge'])->name('schedules.acknowledge');

    // Pengajuan izin dan feedback oleh karyawan.
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Master data karyawan untuk admin.
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    // Kelola jadwal kerja untuk admin.
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/template', [ScheduleController::class, 'downloadTemplate'])->name('schedules.template');
    Route::post('/schedules/import', [ScheduleController::class, 'import'])->name('schedules.import');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Verifikasi absensi oleh admin.
    Route::get('/verify-attendance', [AttendanceController::class, 'verifyIndex'])->name('attendance.verify-index');
    Route::post('/verify-attendance/bulk', [AttendanceController::class, 'bulkVerify'])->name('attendance.bulk-verify');
    Route::post('/verify-attendance/{attendanceLog}', [AttendanceController::class, 'verify'])->name('attendance.verify');

    // Persetujuan izin oleh admin.
    Route::get('/leave-approval', [LeaveController::class, 'approvalIndex'])->name('leave.approval-index');
    Route::post('/leave-approval/{leaveRequest}', [LeaveController::class, 'process'])->name('leave.process');
    Route::delete('/leave-approval/{leaveRequest}', [LeaveController::class, 'destroy'])->name('leave.destroy');

    // Inbox feedback, laporan, dan audit log untuk admin.
    Route::get('/feedback-inbox', [FeedbackController::class, 'adminIndex'])->name('feedback.admin-index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
});
