<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Controller untuk feedback karyawan dan inbox feedback admin.
class FeedbackController extends Controller
{
    // Menampilkan riwayat feedback user yang sedang login.
    public function index(): View
    {
        return view('feedback.index', [
            'rows' => Feedback::query()->where('user_id', Auth::id())->latest('created_at')->get(),
        ]);
    }

    // Menampilkan semua feedback masuk untuk admin.
    public function adminIndex(): View
    {
        // Hanya admin yang boleh membaca inbox feedback.
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('feedback.admin', [
            'rows' => Feedback::query()->with('user')->latest('created_at')->limit(200)->get(),
        ]);
    }

    // Menyimpan feedback baru dari user.
    public function store(Request $request): RedirectResponse
    {
        // Validasi kategori dan isi feedback.
        $data = $request->validate([
            'category' => ['required', 'in:saran,kritik,masalah'],
            'message' => ['required', 'string'],
        ]);

        // Simpan feedback dengan user_id dari user login.
        Feedback::query()->create([
            'user_id' => Auth::id(),
            'category' => $data['category'],
            'message' => $data['message'],
        ]);

        // Catat aktivitas kirim feedback.
        Portal::logAudit((int) Auth::id(), 'KIRIM_FEEDBACK', 'Kirim feedback');

        return back()->with('success', 'Terima kasih atas feedback Anda.');
    }
}
