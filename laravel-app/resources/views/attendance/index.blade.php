@extends('layouts.app', ['title' => 'Absensi'])

@section('content')
<div class="card"><div class="card-body">
    @if($todaySchedule)
        <div class="alert alert-info">Jadwal hari ini: {{ $todaySchedule->start_time }} - {{ $todaySchedule->end_time }} di {{ $todaySchedule->location ?? '-' }}</div>
    @endif
    <form method="post" action="{{ route('attendance.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-4"><label class="form-label">Jenis</label><select name="kind" class="form-select"><option value="masuk">Masuk</option><option value="mulai_istirahat">Mulai Istirahat</option><option value="selesai_istirahat">Selesai Istirahat</option><option value="pulang">Pulang</option></select></div>
        <div class="col-md-4"><label class="form-label">Foto Bukti</label><input type="file" name="evidence_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Submit</button></div>
        <div class="col-12"><div class="form-text mt-1">Opsional. Format JPG/JPEG/PNG, maksimal 2 MB.</div></div>
    </form>
    <hr>
    <p class="mb-1">Masuk: {{ $todayAttendance?->check_in ?? '-' }}</p>
    <p class="mb-1">Bukti Masuk: @if($todayAttendance?->check_in_photo)<a href="{{ asset($todayAttendance->check_in_photo) }}" target="_blank">Lihat Foto</a>@else-@endif</p>
    <p class="mb-1">Istirahat Mulai: {{ $todayAttendance?->break_start ?? '-' }}</p>
    <p class="mb-1">Bukti Mulai Istirahat: @if($todayAttendance?->break_start_photo)<a href="{{ asset($todayAttendance->break_start_photo) }}" target="_blank">Lihat Foto</a>@else-@endif</p>
    <p class="mb-1">Istirahat Selesai: {{ $todayAttendance?->break_end ?? '-' }}</p>
    <p class="mb-1">Bukti Selesai Istirahat: @if($todayAttendance?->break_end_photo)<a href="{{ asset($todayAttendance->break_end_photo) }}" target="_blank">Lihat Foto</a>@else-@endif</p>
    <p class="mb-1">Pulang: {{ $todayAttendance?->check_out ?? '-' }}</p>
    <p class="mb-1">Bukti Pulang: @if($todayAttendance?->check_out_photo)<a href="{{ asset($todayAttendance->check_out_photo) }}" target="_blank">Lihat Foto</a>@else-@endif</p>
    <p class="mb-0">Status: <strong>{{ $todayAttendance?->status ?? 'belum ada data' }}</strong></p>
</div></div>
@endsection
