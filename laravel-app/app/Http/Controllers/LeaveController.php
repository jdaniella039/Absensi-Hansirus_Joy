<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(): View
    {
        return view('leave.index', [
            'rows' => LeaveRequest::query()->where('user_id', Auth::id())->latest('created_at')->get(),
        ]);
    }

    public function approvalIndex(): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('leave.approval', [
            'rows' => LeaveRequest::query()->with('user')->latest('created_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'type' => ['required', 'in:izin,sakit,cuti'],
            'reason' => ['required', 'string'],
            'evidence_file' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $evidencePath = null;
        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
            $filename = 'leave-' . Auth::id() . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
            $targetDir = Portal::ensureLeaveEvidenceDirectory();
            $file->move($targetDir, $filename);
            $evidencePath = 'uploads/leave-evidence/' . $filename;
        }

        LeaveRequest::query()->create([
            'user_id' => Auth::id(),
            'leave_date' => $data['leave_date'],
            'type' => $data['type'],
            'reason' => $data['reason'],
            'evidence' => $evidencePath,
        ]);

        Portal::logAudit((int) Auth::id(), 'SUBMIT_IZIN', 'Pengajuan izin');

        return back()->with('success', 'Permohonan izin berhasil dikirim.');
    }

    public function process(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        $leaveRequest->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        Portal::logAudit((int) Auth::id(), 'PROSES_IZIN', 'Admin proses izin');

        return back()->with('success', 'Permohonan izin diproses.');
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $leaveId = $leaveRequest->id;
        Portal::deleteLeaveEvidence($leaveRequest->evidence);
        $leaveRequest->delete();

        Portal::logAudit((int) Auth::id(), 'DELETE_IZIN', "Hapus izin {$leaveId}");

        return back()->with('success', 'Data izin berhasil dihapus.');
    }
}
