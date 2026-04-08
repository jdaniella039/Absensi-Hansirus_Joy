<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'position' => 'Admin HRD',
            ]
        );

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
