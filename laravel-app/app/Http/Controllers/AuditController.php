<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Controller untuk melihat audit log aktivitas sistem.
class AuditController extends Controller
{
    // Menampilkan audit log berdasarkan rentang tanggal.
    public function index(Request $request): View
    {
        // Hanya admin yang boleh membuka audit log.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Rentang tanggal default: awal bulan sampai hari ini.
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        $to = max($to, $from);

        // Ambil audit log terbaru sesuai rentang tanggal.
        return view('audit.index', [
            'rows' => AuditLog::query()
                ->with('user')
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->latest('created_at')
                ->limit(200)
                ->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
