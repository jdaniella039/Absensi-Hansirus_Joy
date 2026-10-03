<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Contoh command Artisan bawaan Laravel.
Artisan::command('inspire', function () {
    // Menampilkan quote inspiratif di terminal.
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
