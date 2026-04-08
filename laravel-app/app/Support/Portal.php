<?php

namespace App\Support;

use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\Schedule;

class Portal
{
    public static function logAudit(?int $userId, string $action, string $description = ''): void
    {
        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function todayAttendance(int $userId): ?AttendanceLog
    {
        return AttendanceLog::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();
    }

    public static function todaySchedule(int $userId): ?Schedule
    {
        return Schedule::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }

    public static function ensureLeaveEvidenceDirectory(): string
    {
        $path = public_path('uploads/leave-evidence');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    public static function ensureAttendanceEvidenceDirectory(): string
    {
        $path = public_path('uploads/attendance-evidence');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

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
