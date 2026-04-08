<?php

namespace App\Http\Controllers;

use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('profile.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:100'],
            'new_password' => ['nullable', 'string', 'min:6'],
        ]);

        $user = Auth::user();
        $user->name = $data['name'];
        $user->position = $data['position'] ?? null;

        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }

        $user->save();
        Portal::logAudit((int) Auth::id(), 'UPDATE_PROFIL', 'Update profil');

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
