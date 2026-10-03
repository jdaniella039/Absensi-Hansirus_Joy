<?php

namespace App\Http\Controllers;

use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

// Controller untuk halaman profil user yang sedang login.
class ProfileController extends Controller
{
    // Menampilkan form profil.
    public function index(): View
    {
        return view('profile.index');
    }

    // Memperbarui profil user.
    public function update(Request $request): RedirectResponse
    {
        // Validasi nama, jabatan, dan password baru opsional.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:100'],
            'new_password' => ['nullable', 'string', 'min:6'],
        ]);

        // Ambil user login lalu isi field profil.
        $user = Auth::user();
        $user->name = $data['name'];
        $user->position = $data['position'] ?? null;

        // Jika password baru diisi, simpan versi hash-nya.
        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }

        // Simpan perubahan dan catat audit.
        $user->save();
        Portal::logAudit((int) Auth::id(), 'UPDATE_PROFIL', 'Update profil');

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
