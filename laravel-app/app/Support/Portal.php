<?php

namespace App\Support;

use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\Schedule;

// Kumpulan helper statis yang dipakai lintas controller.
class Portal
{
    // Menyimpan aktivitas user/admin ke tabel audit_logs.
    public static function logAudit(?int $userId, string $action, string $description = ''): void
    {
        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    // Mengambil absensi user pada tanggal hari ini.
    public static function todayAttendance(int $userId): ?AttendanceLog
    {
        return AttendanceLog::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();
    }

    // Mengambil jadwal user pada tanggal hari ini.
    public static function todaySchedule(int $userId): ?Schedule
    {
        return Schedule::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }

    // Memastikan folder upload bukti izin tersedia, lalu mengembalikan path lengkapnya.
    public static function ensureLeaveEvidenceDirectory(): string
    {
        $path = public_path('uploads/leave-evidence');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    // Memastikan folder upload bukti absensi tersedia, lalu mengembalikan path lengkapnya.
    public static function ensureAttendanceEvidenceDirectory(): string
    {
        $path = public_path('uploads/attendance-evidence');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    // Menghapus file bukti izin jika path-nya ada dan file ditemukan.
    public static function deleteLeaveEvidence(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    // Menghapus file bukti absensi jika path-nya ada dan file ditemukan.
    public static function deleteAttendanceEvidence(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}
