<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Support\Portal;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

// Controller dashboard memakai __invoke karena hanya punya satu aksi utama.
class DashboardController extends Controller
{
    // Menampilkan dashboard sesuai role user yang login.
    public function __invoke(): View
    {
        $user = Auth::user();

        // Admin melihat statistik global dan shortcut modul pengelolaan.
        if ($user->isAdmin()) {
            // Hitung ringkasan status absensi hari ini.
            $stats = AttendanceLog::query()
                ->selectRaw("SUM(status='pending') as pending, SUM(status='approved') as approved, SUM(status='rejected') as rejected")
                ->whereDate('attendance_date', now()->toDateString())
                ->first();

            // Kirim semua data statistik ke view dashboard admin.
            return view('dashboard.admin', [
                'stats' => $stats,
                'employeeCount' => User::query()->where('role', 'karyawan')->count(),
                'pendingLeave' => LeaveRequest::query()->where('status', 'pending')->count(),
                'feedbackCount' => Feedback::query()->count(),
            ]);
        }

        // Karyawan melihat absensi hari ini dan jadwal kerja terdekat.
        return view('dashboard.employee', [
            'todayAttendance' => Portal::todayAttendance((int) $user->id),
            'nextSchedule' => Schedule::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', '>=', now()->toDateString())
                ->orderBy('work_date')
                ->first(),
        ]);
    }
}
