<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\User;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('schedules.index', [
            'employees' => User::query()->where('role', 'karyawan')->orderBy('name')->get(),
            'editingSchedule' => $request->filled('edit_schedule_id') ? Schedule::find($request->integer('edit_schedule_id')) : null,
            'schedules' => Schedule::query()->with('user')->orderByDesc('work_date')->limit(100)->get(),
        ]);
    }

    public function mySchedule(): View
    {
        return view('schedules.my', [
            'schedules' => Schedule::query()->where('user_id', Auth::id())->orderByDesc('work_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $scheduleId = $request->integer('schedule_id');
        $data = $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'work_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $duplicateQuery = Schedule::query()
            ->where('user_id', $data['employee_id'])
            ->whereDate('work_date', $data['work_date']);

        if ($scheduleId > 0) {
            $duplicateQuery->where('id', '!=', $scheduleId);
        }

        if ($duplicateQuery->exists()) {
            return back()->with('error', 'Jadwal untuk karyawan dan tanggal tersebut sudah ada.');
        }

        $payload = [
            'user_id' => $data['employee_id'],
            'work_date' => $data['work_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ];

        if ($scheduleId > 0) {
            $schedule = Schedule::findOrFail($scheduleId);
            $schedule->update($payload);
            Portal::logAudit((int) Auth::id(), 'UPDATE_JADWAL', "Update jadwal {$schedule->id}");
        } else {
            $schedule = Schedule::query()->create($payload);
            Portal::logAudit((int) Auth::id(), 'CREATE_JADWAL', "Tambah jadwal {$schedule->id}");
        }

        return redirect()->route('schedules.index')->with('success', 'Jadwal berhasil disimpan.');
    }

    public function acknowledge(Schedule $schedule): RedirectResponse
    {
        abort_unless($schedule->user_id === Auth::id(), 403);

        $schedule->update(['acknowledged_at' => now()]);
        Portal::logAudit((int) Auth::id(), 'KONFIRMASI_JADWAL', "Konfirmasi jadwal {$schedule->id}");

        return back()->with('success', 'Jadwal berhasil dikonfirmasi.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $scheduleId = $schedule->id;
        $schedule->delete();
        Portal::logAudit((int) Auth::id(), 'DELETE_JADWAL', "Hapus jadwal {$scheduleId}");

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
