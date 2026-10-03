@extends('layouts.app', ['title' => 'Absensi'])

@section('content')
{{-- Halaman input absensi harian karyawan. --}}
<div class="card"><div class="card-body">
    @if($todaySchedule)
        {{-- Informasi jadwal hari ini jika sudah dibuat admin. --}}
        <div class="alert alert-info">Jadwal hari ini: {{ $todaySchedule->start_time }} - {{ $todaySchedule->end_time }} di {{ $todaySchedule->location ?? '-' }}</div>
    @endif
    {{-- Form submit absensi beserta foto bukti opsional. --}}
    <form method="post" action="{{ route('attendance.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-4"><label class="form-label">Jenis</label><select name="kind" class="form-select"><option value="masuk">Masuk</option><option value="pulang">Pulang</option></select></div>
        <div class="col-md-4"><label class="form-label">Foto Bukti</label><input type="file" name="evidence_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Submit</button></div>
        <div class="col-12"><div class="form-text mt-1">Opsional. Format JPG/JPEG/PNG, maksimal 10 MB.</div></div>
    </form>
    <hr>
    {{-- Ringkasan status absensi dan link bukti foto hari ini. --}}
    <p class="mb-1">Masuk: {{ $todayAttendance?->check_in ?? '-' }}</p>
    <p class="mb-1">Bukti Masuk: @if($todayAttendance?->check_in_photo)<a href="{{ asset($todayAttendance->check_in_photo) }}" target="_blank">Lihat Foto</a>@else-@endif</p>

    <p class="mb-1">Pulang: {{ $todayAttendance?->check_out ?? '-' }}</p>
    <p class="mb-1">Bukti Pulang: @if($todayAttendance?->check_out_photo)<a href="{{ asset($todayAttendance->check_out_photo) }}" target="_blank">Lihat Foto</a>@else-@endif</p>
    <p class="mb-0">Status: <strong>{{ $todayAttendance?->status ?? 'belum ada data' }}</strong></p>
</div></div>
@endsection
