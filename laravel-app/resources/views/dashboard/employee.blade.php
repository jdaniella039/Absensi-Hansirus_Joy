@extends('layouts.app', ['title' => 'Dashboard Karyawan'])

@section('content')
{{-- Shortcut fitur utama untuk karyawan. --}}
<div class="row g-3 mb-4">
    <div class="col-md-3"><a href="{{ route('attendance.index') }}" class="card text-decoration-none h-100"><div class="card-body fw-semibold">Absensi</div></a></div>
    <div class="col-md-3"><a href="{{ route('schedules.my') }}" class="card text-decoration-none h-100"><div class="card-body fw-semibold">Jadwal Kerja</div></a></div>
    <div class="col-md-3"><a href="{{ route('leave.index') }}" class="card text-decoration-none h-100"><div class="card-body fw-semibold">Permohonan Izin</div></a></div>
    <div class="col-md-3"><a href="{{ route('feedback.index') }}" class="card text-decoration-none h-100"><div class="card-body fw-semibold">Feedback</div></a></div>
</div>

<div class="row g-3">
    {{-- Ringkasan absensi hari ini milik karyawan. --}}
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Status Absensi Hari Ini</h5>
            <p class="mb-1">Masuk: {{ $todayAttendance?->check_in ?? '-' }}</p>
            <p class="mb-1">Pulang: {{ $todayAttendance?->check_out ?? '-' }}</p>
            <p class="mb-0">Status: <strong>{{ $todayAttendance?->status ?? 'belum ada data' }}</strong></p>
        </div></div>
    </div>
    {{-- Jadwal kerja terdekat setelah hari ini. --}}
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Jadwal Terdekat</h5>
            @if($nextSchedule)
                <p class="mb-1">Tanggal: {{ $nextSchedule->work_date->format('Y-m-d') }}</p>
                <p class="mb-1">Jam: {{ $nextSchedule->start_time }} - {{ $nextSchedule->end_time }}</p>
                <p class="mb-0">Lokasi: {{ $nextSchedule->location ?? '-' }}</p>
            @else
                <p class="mb-0 text-muted">Belum ada jadwal berikutnya.</p>
            @endif
        </div></div>
    </div>
</div>
@endsection
