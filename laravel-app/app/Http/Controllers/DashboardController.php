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

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $stats = AttendanceLog::query()
                ->selectRaw("SUM(status='pending') as pending, SUM(status='approved') as approved, SUM(status='rejected') as rejected")
                ->whereDate('attendance_date', now()->toDateString())
                ->first();

            return view('dashboard.admin', [
                'stats' => $stats,
                'employeeCount' => User::query()->where('role', 'karyawan')->count(),
                'pendingLeave' => LeaveRequest::query()->where('status', 'pending')->count(),
                'feedbackCount' => Feedback::query()->count(),
            ]);
        }

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
