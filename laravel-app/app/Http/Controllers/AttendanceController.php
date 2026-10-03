<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// Controller untuk absensi karyawan dan verifikasi absensi oleh admin.
class AttendanceController extends Controller
{
    // Menampilkan halaman absensi milik user yang sedang login.
    public function index(): View
    {
        return view('attendance.index', [
            'todayAttendance' => Portal::todayAttendance((int) Auth::id()),
            'todaySchedule' => Portal::todaySchedule((int) Auth::id()),
        ]);
    }

    // Menampilkan halaman admin untuk melihat dan memverifikasi absensi.
    public function verifyIndex(): View
    {
        // Hanya admin yang boleh membuka halaman verifikasi.
        abort_unless(Auth::user()?->isAdmin(), 403);

        $rows = AttendanceLog::query()
            ->select('attendance_logs.*')
            ->addSelect([
                'schedules.start_time as schedule_start',
                'schedules.end_time as schedule_end',
            ])
            ->leftJoin('schedules', function ($join) {
                $join->on('schedules.user_id', '=', 'attendance_logs.user_id')
                    ->on('schedules.work_date', '=', 'attendance_logs.attendance_date');
            })
            ->with('user')
            ->orderByDesc('attendance_logs.attendance_date')
            ->limit(100)
            ->get();

        $rows->each(function (AttendanceLog $attendance): void {
            $attendance->setAttribute('system_assessment', $this->assessAttendance($attendance));
        });

        return view('attendance.verify', ['rows' => $rows]);
    }

    // Menyimpan absensi masuk atau pulang.
    public function store(Request $request): RedirectResponse
    {
        // Validasi jenis absensi dan foto bukti jika diupload.
        $request->validate([
            'kind' => ['required', 'in:masuk,pulang'],
            'evidence_file' => ['nullable', 'image', 'max:10240', 'mimes:jpg,jpeg,png'],
        ]);

        // Mapping jenis absensi ke kolom waktu di tabel attendance_logs.
        $map = [
            'masuk' => 'check_in',
            'pulang' => 'check_out',
        ];

        // Mapping jenis absensi ke kolom foto bukti masing-masing tahap.
        $photoMap = [
            'masuk' => 'check_in_photo',
            'pulang' => 'check_out_photo',
        ];

        // Ambil absensi hari ini; jika belum ada, buat record awal.
        $attendance = Portal::todayAttendance((int) Auth::id())
            ?? AttendanceLog::query()->create([
                'user_id' => Auth::id(),
                'attendance_date' => now()->toDateString(),
                'status' => 'pending',
            ]);

        // Tentukan kolom yang harus diisi berdasarkan jenis absensi.
        $kind = $request->string('kind')->toString();
        $column = $map[$kind];
        $photoColumn = $photoMap[$kind];

        // Cegah tahap absensi yang sama dikirim dua kali.
        if ($attendance->{$column}) {
            return back()->with('error', 'Absensi ini sudah pernah direkam hari ini.');
        }


        // Pulang hanya boleh setelah check in.
        if ($kind === 'pulang' && !$attendance->check_in) {
            return back()->with('error', 'Absensi masuk harus dilakukan sebelum pulang.');
        }

        // Payload update waktu absensi sekaligus reset status verifikasi.
        $payload = [
            $column => now(),
            'status' => 'pending',
            'verification_note' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];

        // Jika ada foto bukti, simpan file dan isi kolom foto sesuai jenis absensi.
        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
            $filename = 'attendance-' . Auth::id() . '-' . $kind . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
            $targetDir = Portal::ensureAttendanceEvidenceDirectory();
            $file->move($targetDir, $filename);

            // Hapus foto lama untuk kolom yang sama agar storage tidak menumpuk.
            if ($attendance->{$photoColumn}) {
                Portal::deleteAttendanceEvidence($attendance->{$photoColumn});
            }

            $payload[$photoColumn] = 'uploads/attendance-evidence/' . $filename;
        }

        // Simpan perubahan absensi ke database.
        $attendance->update($payload);

        // Catat aktivitas absensi.
        Portal::logAudit((int) Auth::id(), 'ABSENSI_' . strtoupper($kind), 'Karyawan melakukan absensi');

        return back()->with('success', 'Absensi berhasil disimpan.');
    }

    // Memproses keputusan admin untuk beberapa absensi sekaligus.
    public function bulkVerify(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['required', 'integer', 'distinct', 'exists:attendance_logs,id'],
            'status' => ['required', 'in:approved,rejected'],
            'verification_note' => ['nullable', 'string', 'max:255'],
        ]);

        $attendanceIds = array_map('intval', $data['attendance_ids']);
        $bulkNote = trim((string) ($data['verification_note'] ?? ''));

        DB::transaction(function () use ($attendanceIds, $data, $bulkNote): void {
            $attendances = AttendanceLog::query()
                ->select('attendance_logs.*')
                ->addSelect([
                    'schedules.start_time as schedule_start',
                    'schedules.end_time as schedule_end',
                ])
                ->leftJoin('schedules', function ($join) {
                    $join->on('schedules.user_id', '=', 'attendance_logs.user_id')
                        ->on('schedules.work_date', '=', 'attendance_logs.attendance_date');
                })
                ->whereIn('attendance_logs.id', $attendanceIds)
                ->get();

            foreach ($attendances as $attendance) {
                $assessment = $this->assessAttendance($attendance);
                $note = $bulkNote !== '' ? $bulkNote : 'Validasi sistem: ' . $assessment['summary'];
                AttendanceLog::query()->whereKey($attendance->id)->update([
                    'status' => $data['status'],
                    'verification_note' => $note,
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]);
            }
        });

        Portal::logAudit((int) Auth::id(), 'VERIFIKASI_ABSENSI_MASSAL', 'Admin memilih ' . $data['status'] . ' untuk ' . count($attendanceIds) . ' absensi');

        return back()->with('success', count($attendanceIds) . ' absensi berhasil diverifikasi.');
    }
    // Memproses keputusan admin untuk absensi tertentu.
    public function verify(Request $request, AttendanceLog $attendanceLog): RedirectResponse
    {
        // Hanya admin yang boleh memverifikasi absensi.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Status hanya boleh approved atau rejected.
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'verification_note' => ['nullable', 'string', 'max:255'],
        ]);

        $attendanceForAssessment = AttendanceLog::query()
            ->select('attendance_logs.*')
            ->addSelect([
                'schedules.start_time as schedule_start',
                'schedules.end_time as schedule_end',
            ])
            ->leftJoin('schedules', function ($join) {
                $join->on('schedules.user_id', '=', 'attendance_logs.user_id')
                    ->on('schedules.work_date', '=', 'attendance_logs.attendance_date');
            })
            ->findOrFail($attendanceLog->id);
        $assessment = $this->assessAttendance($attendanceForAssessment);
        $verificationNote = $data['verification_note'] ?? '';
        if ($verificationNote === '') {
            $verificationNote = 'Validasi sistem: ' . $assessment['summary'];
        }

        // Simpan hasil verifikasi beserta admin dan waktu proses.
        $attendanceLog->update([
            'status' => $data['status'],
            'verification_note' => $verificationNote,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Catat aktivitas admin di audit log.
        Portal::logAudit((int) Auth::id(), 'VERIFIKASI_ABSENSI', "Admin memilih {$data['status']}; rekomendasi sistem {$assessment['recommendation']}");

        return back()->with('success', 'Verifikasi absensi diperbarui.');
    }

    // Menilai kesesuaian absensi dengan jadwal sebagai rekomendasi untuk admin.
    private function assessAttendance(AttendanceLog $attendance): array
    {
        $issues = [];
        $scheduleStart = (string) ($attendance->schedule_start ?? '');
        $scheduleEnd = (string) ($attendance->schedule_end ?? '');
        $isShiftOngoing = !$attendance->check_out
            && $scheduleEnd !== ''
            && now()->format('Y-m-d H:i:s') <= $attendance->attendance_date->format('Y-m-d') . ' ' . $scheduleEnd;
        $earliestCheckOut = $scheduleEnd !== ''
            ? $attendance->attendance_date->copy()->setTimeFromTimeString($scheduleEnd)->subMinutes(5)
            : null;

        if ($scheduleStart === '' || $scheduleEnd === '') {
            $issues[] = 'Tidak ada jadwal kerja';
        }
        if (!$attendance->check_in) {
            $issues[] = 'Waktu masuk belum ada';
        }
        if (!$attendance->check_out && !$isShiftOngoing) {
            $issues[] = 'Waktu pulang belum ada';
        }
        if (!$attendance->check_in_photo) {
            $issues[] = 'Foto masuk belum ada';
        }
        if (!$attendance->check_out_photo && !$isShiftOngoing) {
            $issues[] = 'Foto pulang belum ada';
        }
        if ($attendance->check_in && $attendance->check_in->toDateString() !== $attendance->attendance_date->toDateString()) {
            $issues[] = 'Tanggal masuk tidak sesuai';
        }
        if ($attendance->check_out && $attendance->check_out->toDateString() !== $attendance->attendance_date->toDateString()) {
            $issues[] = 'Tanggal pulang tidak sesuai';
        }
        if ($attendance->check_in && $attendance->check_out && $attendance->check_out->lessThanOrEqualTo($attendance->check_in)) {
            $issues[] = 'Waktu pulang tidak valid';
        }
        if ($attendance->check_in && $scheduleStart !== '' && $attendance->check_in->format('H:i:s') > $scheduleStart) {
            $issues[] = 'Masuk terlambat';
        }
        if ($attendance->check_out && $earliestCheckOut && $attendance->check_out->lessThan($earliestCheckOut)) {
            $issues[] = 'Pulang lebih awal';
        }

        $isMatch = $issues === [] && !$isShiftOngoing;
        return [
            'is_match' => $isMatch,
            'is_ongoing' => $isShiftOngoing && $issues === [],
            'label' => $isShiftOngoing && $issues === [] ? 'Sedang Berlangsung' : ($isMatch ? 'Sesuai' : 'Perlu Ditinjau'),
            'recommendation' => $isShiftOngoing && $issues === [] ? 'pending' : ($isMatch ? 'approved' : 'rejected'),
            'summary' => $isShiftOngoing && $issues === [] ? 'Shift masih berlangsung; waktu pulang belum diperlukan' : ($isMatch ? 'Data lengkap dan sesuai jadwal' : implode('; ', $issues)),
        ];
    }
}
