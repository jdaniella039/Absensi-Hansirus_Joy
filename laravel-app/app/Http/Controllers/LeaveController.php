<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Controller untuk pengajuan izin karyawan dan persetujuan izin oleh admin.
class LeaveController extends Controller
{
    // Menampilkan halaman pengajuan izin milik user yang login.
    public function index(): View
    {
        return view('leave.index', [
            'rows' => LeaveRequest::query()->where('user_id', Auth::id())->latest('created_at')->get(),
        ]);
    }

    // Menampilkan daftar pengajuan izin untuk admin.
    public function approvalIndex(): View
    {
        // Hanya admin yang boleh membuka halaman approval.
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('leave.approval', [
            'rows' => LeaveRequest::query()->with('user')->latest('created_at')->limit(100)->get(),
        ]);
    }

    // Menyimpan pengajuan izin/sakit/cuti dari karyawan.
    public function store(Request $request): RedirectResponse
    {
        // Validasi form pengajuan dan file bukti opsional.
        $data = $request->validate([
            'leave_date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', 'in:izin,sakit,cuti'],
            'reason' => ['required', 'string'],
            'evidence_file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        // Path bukti diisi jika user mengupload file.
        $evidencePath = null;
        if ($request->hasFile('evidence_file')) {
            // Simpan file bukti ke public/uploads/leave-evidence.
            $file = $request->file('evidence_file');
            $filename = 'leave-' . Auth::id() . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
            $targetDir = Portal::ensureLeaveEvidenceDirectory();
            $file->move($targetDir, $filename);
            $evidencePath = 'uploads/leave-evidence/' . $filename;
        }

        // Simpan pengajuan izin dengan status default pending.
        LeaveRequest::query()->create([
            'user_id' => Auth::id(),
            'leave_date' => $data['leave_date'],
            'type' => $data['type'],
            'reason' => $data['reason'],
            'evidence' => $evidencePath,
        ]);

        // Catat aktivitas pengajuan izin.
        Portal::logAudit((int) Auth::id(), 'SUBMIT_IZIN', 'Pengajuan izin');

        return back()->with('success', 'Permohonan izin berhasil dikirim.');
    }

    // Admin memproses pengajuan izin.
    public function process(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        // Hanya admin yang boleh memproses izin.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Status hanya boleh approved atau rejected.
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        // Simpan hasil proses beserta admin dan waktu proses.
        $leaveRequest->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        // Catat aktivitas admin.
        Portal::logAudit((int) Auth::id(), 'PROSES_IZIN', 'Admin proses izin');

        return back()->with('success', 'Permohonan izin diproses.');
    }

    // Admin menghapus pengajuan izin beserta file buktinya.
    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        // Hanya admin yang boleh menghapus izin.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Simpan ID untuk audit, hapus file bukti, lalu hapus record.
        $leaveId = $leaveRequest->id;
        Portal::deleteLeaveEvidence($leaveRequest->evidence);
        $leaveRequest->delete();

        Portal::logAudit((int) Auth::id(), 'DELETE_IZIN', "Hapus izin {$leaveId}");

        return back()->with('success', 'Data izin berhasil dihapus.');
    }
}
