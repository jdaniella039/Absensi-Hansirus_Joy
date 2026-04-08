<?php
use Illuminate\Support\Facades\Route;

// Perintah: "Kalau orang buka alamat web ini, kasih lihat halaman login"
Route::get('/', function () {
    return view('login');
});