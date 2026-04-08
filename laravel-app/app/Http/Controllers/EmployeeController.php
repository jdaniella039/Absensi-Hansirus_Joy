<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $editingEmployee = $request->filled('edit_employee_id')
            ? User::query()->where('role', 'karyawan')->find($request->integer('edit_employee_id'))
            : null;

        return view('employees.index', [
            'employees' => User::query()->where('role', 'karyawan')->orderBy('name')->get(),
            'editingEmployee' => $editingEmployee,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'position' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'position' => $data['position'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'karyawan',
        ]);

        Portal::logAudit((int) Auth::id(), 'CREATE_KARYAWAN', "Tambah karyawan {$data['username']}");

        return back()->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        abort_unless($employee->role === 'karyawan', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username,' . $employee->id],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        $employee->update($data);
        Portal::logAudit((int) Auth::id(), 'UPDATE_KARYAWAN', "Update karyawan {$employee->username}");

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $employee): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        abort_unless($employee->role === 'karyawan', 404);

        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $employee->update(['password' => Hash::make($data['new_password'])]);
        Portal::logAudit((int) Auth::id(), 'RESET_PASSWORD_KARYAWAN', "Reset password {$employee->username}");

        return back()->with('success', 'Password karyawan berhasil direset.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
        abort_unless($employee->role === 'karyawan', 404);

        $username = $employee->username;
        $employee->delete();

        Portal::logAudit((int) Auth::id(), 'DELETE_KARYAWAN', "Hapus karyawan {$username}");

        return back()->with('success', 'Data karyawan berhasil dihapus.');
    }
}
