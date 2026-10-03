<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// Seeder utama yang memanggil seeder lain.
class DatabaseSeeder extends Seeder
{
    // Menonaktifkan event model saat seeding agar proses lebih sederhana.
    use WithoutModelEvents;

    /**
     * Menjalankan daftar seeder aplikasi.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
