<!doctype html>
<html lang="id">
<head>
    {{-- Meta dasar agar halaman tampil benar di browser dan mobile. --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Judul halaman memakai variabel $title jika dikirim dari view/controller. --}}
    <title>{{ $title ?? 'Absensi Hansirus' }}</title>

    {{-- Bootstrap dipakai untuk komponen UI dan grid responsif. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- CSS global sederhana untuk tampilan Laravel app. --}}
    <style>
        body { background: linear-gradient(180deg, #f7f4ee 0%, #eef4ef 100%); }
        .topbar { background: #18352f; }
        .hero { background: linear-gradient(135deg, #16302b, #37735f); color: #fff; border-radius: 20px; }
        .card { border: 0; border-radius: 18px; box-shadow: 0 12px 30px rgba(27, 52, 45, .08); }
        .table-responsive { border-radius: 16px; }
        .form-control, .form-select, .btn { border-radius: 12px; }
    </style>
</head>
<body>
{{-- Ambil user login sekali agar mudah dipakai di navbar dan hero. --}}
@php($user = auth()->user())

{{-- Navbar utama aplikasi. --}}
<nav class="navbar navbar-expand-lg navbar-dark topbar mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Absensi Hansirus</a>
        @if($user)
            {{-- Tombol hamburger untuk tampilan mobile. --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto ms-lg-4">
                    @if($user->isAdmin())
                        {{-- Menu khusus admin. --}}
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">Karyawan</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('schedules.index') ? 'active' : '' }}" href="{{ route('schedules.index') }}">Jadwal</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('attendance.verify-*') ? 'active' : '' }}" href="{{ route('attendance.verify-index') }}">Verifikasi</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('leave.approval-index') ? 'active' : '' }}" href="{{ route('leave.approval-index') }}">Izin</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Laporan</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('feedback.admin-*') ? 'active' : '' }}" href="{{ route('feedback.admin-index') }}">Feedback</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">Audit</a></li>
                    @else
                        {{-- Menu khusus karyawan. --}}
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">Absensi</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('schedules.my') ? 'active' : '' }}" href="{{ route('schedules.my') }}">Jadwal</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('leave.*') && !request()->routeIs('leave.approval-index') ? 'active' : '' }}" href="{{ route('leave.index') }}">Izin</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('feedback.index') ? 'active' : '' }}" href="{{ route('feedback.index') }}">Feedback</a></li>
                    @endif
                </ul>
                {{-- Area kanan navbar: profil, identitas user, dan logout. --}}
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-sm btn-outline-light" href="{{ route('profile.index') }}">Profil</a>
                    <span class="text-white small">{{ $user->name }} ({{ $user->role }})</span>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-warning">Logout</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</nav>

<div class="container pb-5">
    @if($user)
        {{-- Hero panel yang tampil setelah user login. --}}
        <div class="card hero mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="text-uppercase small opacity-75">Laravel Version</div>
                        <h1 class="h3 mb-1">{{ $title ?? 'Dashboard' }}</h1>
                        <div class="opacity-75">Sistem absensi PT. Hansirus Agro Andalan</div>
                    </div>
                    <div class="text-end">
                        <div>{{ now()->format('d M Y') }}</div>
                        <div class="small opacity-75">{{ now()->format('H:i') }} WIB</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Flash message dari controller. --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    {{-- Konten halaman anak akan dimasukkan di sini. --}}
    @yield('content')
</div>

{{-- Script Bootstrap untuk komponen interaktif seperti navbar collapse. --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
