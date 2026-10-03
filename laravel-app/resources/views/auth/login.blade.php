@extends('layouts.app', ['title' => 'Login'])

@section('content')
{{-- Halaman login dibagi dua: panel informasi dan form login. --}}
<div class="row justify-content-center align-items-center g-4" style="min-height: calc(100vh - 160px);">
    {{-- Panel kiri berisi keterangan aplikasi. --}}
    <div class="col-lg-5">
        <div class="card hero">
            <div class="card-body p-4 p-lg-5">
                <div class="small text-uppercase opacity-75">Portal Demo</div>
                <h2 class="mt-2 mb-3">Sistem Absensi Karyawan Berbasis Web</h2>
                <p class="opacity-75 mb-4">Migrasi Laravel untuk pengelolaan absensi, jadwal, izin, laporan, dan monitoring aktivitas.</p>
            </div>
        </div>
    </div>
    {{-- Panel kanan berisi form username dan password. --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="text-center mb-1">Masuk ke Sistem</h3>
                <p class="text-muted text-center">Masukkan username dan password untuk masuk ke portal absensi.</p>
                {{-- Form dikirim ke route login.store dan dilindungi CSRF. --}}
                <form method="post" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input name="username" class="form-control" value="{{ old('username') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
