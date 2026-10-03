<?php

namespace App\Http\Controllers;

use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Controller untuk proses login dan logout user.
class AuthController extends Controller
{
    // Menampilkan halaman form login.
    public function showLogin(): View
    {
        return view('auth.login');
    }

    // Memproses submit form login.
    public function login(Request $request): RedirectResponse
    {
        // Validasi input wajib username dan password.
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt mengecek kecocokan username/password ke tabel users.
        if (Auth::attempt($credentials)) {
            // Regenerate session agar login lebih aman dari session fixation.
            $request->session()->regenerate();

            // Catat aktivitas login ke audit log.
            Portal::logAudit((int) Auth::id(), 'LOGIN', 'Masuk sistem');

            return redirect()->route('dashboard');
        }

        // Jika gagal login, kembali ke form dengan pesan error.
        return back()->with('error', 'Username atau password salah.')->onlyInput('username');
    }

    // Memproses logout user.
    public function logout(Request $request): RedirectResponse
    {
        // Jika user masih terautentikasi, catat aktivitas logout.
        if (Auth::check()) {
            Portal::logAudit((int) Auth::id(), 'LOGOUT', 'Keluar sistem');
        }

        // Hapus status login dan bersihkan session.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
