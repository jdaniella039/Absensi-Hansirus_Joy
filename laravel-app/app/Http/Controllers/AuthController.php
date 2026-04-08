<?php

namespace App\Http\Controllers;

use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            Portal::logAudit((int) Auth::id(), 'LOGIN', 'Masuk sistem');

            return redirect()->route('dashboard');
        }

        return back()->with('error', 'Username atau password salah.')->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Portal::logAudit((int) Auth::id(), 'LOGOUT', 'Keluar sistem');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
