<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Seeder untuk membuat akun awal admin dan karyawan.
class UserSeeder extends Seeder
{
    // Method run() dijalankan saat perintah php artisan db:seed.
    public function run(): void
    {
        // Buat atau update akun admin default.
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'position' => 'Admin HRD',
            ]
        );

        // Buat atau update akun karyawan default.
        User::query()->updateOrCreate(
            ['username' => 'karyawan'],
            [
                'name' => 'Karyawan Hansirus',
                'password' => Hash::make('karyawan123'),
                'role' => 'karyawan',
                'position' => 'BHL Lapangan',
            ]
        );
    }
}
