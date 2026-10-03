@extends('layouts.app', ['title' => 'Dashboard Admin'])

@section('content')
{{-- Kartu statistik utama untuk admin. --}}
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card bg-success-subtle"><div class="card-body"><small>Total Karyawan</small><h3>{{ $employeeCount }}</h3></div></div></div>
    <div class="col-md-3"><div class="card bg-warning-subtle"><div class="card-body"><small>Absensi Pending</small><h3>{{ (int) ($stats->pending ?? 0) }}</h3></div></div></div>
    <div class="col-md-3"><div class="card bg-info-subtle"><div class="card-body"><small>Izin Pending</small><h3>{{ $pendingLeave }}</h3></div></div></div>
    <div class="col-md-3"><div class="card bg-primary-subtle"><div class="card-body"><small>Feedback Masuk</small><h3>{{ $feedbackCount }}</h3></div></div></div>
</div>

{{-- Shortcut cepat ke modul admin. --}}
<div class="row g-3">
    {{-- Array kecil ini mencegah penulisan card shortcut berulang-ulang. --}}
    @foreach([
        ['route' => 'employees.index', 'label' => 'Data Karyawan'],
        ['route' => 'schedules.index', 'label' => 'Kelola Jadwal'],
        ['route' => 'attendance.verify-index', 'label' => 'Verifikasi Absensi'],
        ['route' => 'leave.approval-index', 'label' => 'Persetujuan Izin'],
        ['route' => 'reports.index', 'label' => 'Laporan'],
        ['route' => 'feedback.admin-index', 'label' => 'Feedback Masuk'],
        ['route' => 'audit.index', 'label' => 'Audit Log'],
    ] as $item)
        <div class="col-md-4 col-lg-3">
            <a href="{{ route($item['route']) }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <div class="fw-semibold">{{ $item['label'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
