<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();

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
