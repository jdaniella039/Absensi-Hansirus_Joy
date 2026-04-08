<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(): View
    {
        return view('feedback.index', [
            'rows' => Feedback::query()->where('user_id', Auth::id())->latest('created_at')->get(),
        ]);
    }

    public function adminIndex(): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('feedback.admin', [
            'rows' => Feedback::query()->with('user')->latest('created_at')->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:saran,kritik,masalah'],
            'message' => ['required', 'string'],
        ]);

        Feedback::query()->create([
            'user_id' => Auth::id(),
            'category' => $data['category'],
            'message' => $data['message'],
        ]);

        Portal::logAudit((int) Auth::id(), 'KIRIM_FEEDBACK', 'Kirim feedback');

        return back()->with('success', 'Terima kasih atas feedback Anda.');
    }
}
