<?php

// Facade Route dipakai Laravel untuk mendefinisikan alamat/URL aplikasi.
use Illuminate\Support\Facades\Route;

// Perintah: kalau orang membuka URL utama "/", tampilkan view login.blade.php.
Route::get('/', function () {
    return view('login');
});
