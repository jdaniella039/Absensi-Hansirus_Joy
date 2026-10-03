<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Controller untuk CRUD data karyawan oleh admin.
class EmployeeController extends Controller
{
    // Menampilkan daftar karyawan dan mode edit jika ada query edit_employee_id.
    public function index(Request $request): View
    {
        // Hanya admin yang boleh mengakses master data karyawan.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Jika sedang edit, ambil data karyawan yang sesuai.
        $editingEmployee = $request->filled('edit_employee_id')
            ? User::query()->where('role', 'karyawan')->find($request->integer('edit_employee_id'))
            : null;

        // Kirim daftar karyawan dan data edit ke view.
        return view('employees.index', [
            'employees' => User::query()->where('role', 'karyawan')->orderBy('name')->get(),
            'editingEmployee' => $editingEmployee,
        ]);
    }

    // Menyimpan karyawan baru.
    public function store(Request $request): RedirectResponse
    {
        // Hanya admin yang boleh membuat data karyawan.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Validasi data form tambah karyawan.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'position' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        // Buat user baru dengan role karyawan.
        User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'position' => $data['position'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'karyawan',
        ]);

        // Catat aktivitas admin.
        Portal::logAudit((int) Auth::id(), 'CREATE_KARYAWAN', "Tambah karyawan {$data['username']}");

        return back()->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    // Memperbarui data karyawan.
    public function update(Request $request, User $employee): RedirectResponse
    {
        // Pastikan user yang mengakses adalah admin dan targetnya karyawan.
        abort_unless(Auth::user()?->isAdmin(), 403);
        abort_unless($employee->role === 'karyawan', 404);

        // Validasi data edit; username harus unik selain milik karyawan ini.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username,' . $employee->id],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        // Simpan perubahan dan catat audit.
        $employee->update($data);
        Portal::logAudit((int) Auth::id(), 'UPDATE_KARYAWAN', "Update karyawan {$employee->username}");

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    // Mereset password karyawan.
    public function resetPassword(Request $request, User $employee): RedirectResponse
    {
        // Hanya admin dan hanya target karyawan yang boleh diproses.
        abort_unless(Auth::user()?->isAdmin(), 403);
        abort_unless($employee->role === 'karyawan', 404);

        // Validasi password baru.
        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        // Hash password baru sebelum disimpan.
        $employee->update(['password' => Hash::make($data['new_password'])]);
        Portal::logAudit((int) Auth::id(), 'RESET_PASSWORD_KARYAWAN', "Reset password {$employee->username}");

        return back()->with('success', 'Password karyawan berhasil direset.');
    }

    // Menghapus data karyawan.
    public function destroy(User $employee): RedirectResponse
    {
        // Hanya admin dan hanya target karyawan yang boleh dihapus.
        abort_unless(Auth::user()?->isAdmin(), 403);
        abort_unless($employee->role === 'karyawan', 404);

        // Simpan username untuk pesan audit sebelum record dihapus.
        $username = $employee->username;
        $employee->delete();

        Portal::logAudit((int) Auth::id(), 'DELETE_KARYAWAN', "Hapus karyawan {$username}");

        return back()->with('success', 'Data karyawan berhasil dihapus.');
    }
}
