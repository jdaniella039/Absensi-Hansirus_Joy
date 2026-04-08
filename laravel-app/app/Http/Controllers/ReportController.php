<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $period = $request->string('period', 'bulan')->toString();
        $from = $request->string('from')->toString() ?: ($period === 'hari' ? now()->toDateString() : now()->startOfMonth()->toDateString());
        $to = $request->string('to')->toString() ?: ($period === 'minggu' ? now()->endOfWeek()->toDateString() : now()->toDateString());
        $employeeId = $request->integer('employee_id');

        $rowsQuery = AttendanceLog::query()
            ->with('user')
            ->whereBetween('attendance_date', [$from, $to]);

        if ($employeeId > 0) {
            $rowsQuery->where('user_id', $employeeId);
        }

        $rows = $rowsQuery->orderByDesc('attendance_date')->get();
        $hadirTotal = (clone $rowsQuery)->where('status', 'approved')->count();
        $terlambat = AttendanceLog::query()
            ->join('schedules', function ($join) {
                $join->on('schedules.user_id', '=', 'attendance_logs.user_id')
                    ->on('schedules.work_date', '=', 'attendance_logs.attendance_date');
            })
            ->where('attendance_logs.status', 'approved')
            ->whereBetween('attendance_logs.attendance_date', [$from, $to])
            ->when($employeeId > 0, fn ($q) => $q->where('attendance_logs.user_id', $employeeId))
            ->whereRaw('TIME(attendance_logs.check_in) > schedules.start_time')
            ->count();
        $izin = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereBetween('leave_date', [$from, $to])
            ->when($employeeId > 0, fn ($q) => $q->where('user_id', $employeeId))
            ->count();
        $totalJadwal = Schedule::query()
            ->whereBetween('work_date', [$from, $to])
            ->when($employeeId > 0, fn ($q) => $q->where('user_id', $employeeId))
            ->count();

        return view('reports.index', [
            'rows' => $rows,
            'employees' => User::query()->where('role', 'karyawan')->orderBy('name')->get(),
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'employeeId' => $employeeId,
            'hadir' => max($hadirTotal - $terlambat, 0),
            'izin' => $izin,
            'terlambat' => $terlambat,
            'absen' => max($totalJadwal - $hadirTotal - $izin, 0),
        ]);
    }
}
