<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.index', [
            'todayAttendance' => Portal::todayAttendance((int) Auth::id()),
            'todaySchedule' => Portal::todaySchedule((int) Auth::id()),
        ]);
    }

    public function verifyIndex(): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('attendance.verify', [
            'rows' => AttendanceLog::query()->with('user')->orderByDesc('attendance_date')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'kind' => ['required', 'in:masuk,mulai_istirahat,selesai_istirahat,pulang'],
            'evidence_file' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png'],
        ]);

        $map = [
            'masuk' => 'check_in',
            'mulai_istirahat' => 'break_start',
            'selesai_istirahat' => 'break_end',
            'pulang' => 'check_out',
        ];
        $photoMap = [
            'masuk' => 'check_in_photo',
            'mulai_istirahat' => 'break_start_photo',
            'selesai_istirahat' => 'break_end_photo',
            'pulang' => 'check_out_photo',
        ];

        $attendance = Portal::todayAttendance((int) Auth::id())
            ?? AttendanceLog::query()->create([
                'user_id' => Auth::id(),
                'attendance_date' => now()->toDateString(),
                'status' => 'pending',
            ]);

        $kind = $request->string('kind')->toString();
        $column = $map[$kind];
        $photoColumn = $photoMap[$kind];
        if ($attendance->{$column}) {
            return back()->with('error', 'Absensi ini sudah pernah direkam hari ini.');
        }

        if ($kind === 'mulai_istirahat' && !$attendance->check_in) {
            return back()->with('error', 'Absensi masuk harus dilakukan sebelum mulai istirahat.');
        }

        if ($kind === 'selesai_istirahat' && !$attendance->break_start) {
            return back()->with('error', 'Mulai istirahat harus dilakukan sebelum selesai istirahat.');
        }

        if ($kind === 'pulang' && !$attendance->check_in) {
            return back()->with('error', 'Absensi masuk harus dilakukan sebelum pulang.');
        }

        $payload = [
            $column => now(),
            'status' => 'pending',
            'verification_note' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];

        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
            $filename = 'attendance-' . Auth::id() . '-' . $kind . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
            $targetDir = Portal::ensureAttendanceEvidenceDirectory();
            $file->move($targetDir, $filename);

            if ($attendance->{$photoColumn}) {
                Portal::deleteAttendanceEvidence($attendance->{$photoColumn});
            }

            $payload[$photoColumn] = 'uploads/attendance-evidence/' . $filename;
        }

        $attendance->update($payload);

        Portal::logAudit((int) Auth::id(), 'ABSENSI_' . strtoupper($kind), 'Karyawan melakukan absensi');

        return back()->with('success', 'Absensi berhasil disimpan.');
    }

    public function verify(Request $request, AttendanceLog $attendanceLog): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'verification_note' => ['nullable', 'string', 'max:255'],
        ]);

        $attendanceLog->update([
            'status' => $data['status'],
            'verification_note' => $data['verification_note'] ?? null,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        Portal::logAudit((int) Auth::id(), 'VERIFIKASI_ABSENSI', 'Admin verifikasi absensi');

        return back()->with('success', 'Verifikasi absensi diperbarui.');
    }
}
