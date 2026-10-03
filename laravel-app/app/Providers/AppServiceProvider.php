<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Service provider utama aplikasi untuk registrasi service dan bootstrapping.
class AppServiceProvider extends ServiceProvider
{
    /**
     * Tempat mendaftarkan binding/service ke container Laravel.
     */
    public function register(): void
    {
        //
    }

    /**
     * Tempat menjalankan konfigurasi setelah semua service terdaftar.
     */
    public function boot(): void
    {
        //
    }
}
