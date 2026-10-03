@extends('layouts.app', ['title' => 'Laporan Absensi'])

@section('content')
{{-- Form filter laporan berdasarkan periode, tanggal, dan karyawan. --}}
<div class="card mb-4"><div class="card-body">
    <form method="get" action="{{ route('reports.index') }}" class="row g-2">
        <div class="col-md-2"><label class="form-label">Periode</label><select class="form-select" name="period"><option value="hari" @selected($period==='hari')>Per Hari</option><option value="minggu" @selected($period==='minggu')>Per Minggu</option><option value="bulan" @selected($period==='bulan')>Per Bulan</option></select></div>
        <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" id="report-from" name="from" value="{{ $from }}" required></div>
        <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" id="report-to" name="to" value="{{ $to }}" min="{{ $from }}" required></div>
        <div class="col-md-3"><label class="form-label">Karyawan</label><select name="employee_id" class="form-select"><option value="0">Semua</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected($employeeId==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-secondary w-100">Terapkan</button></div>
    </form>
</div></div>
<script>
    (function () {
        const fromInput = document.getElementById('report-from');
        const toInput = document.getElementById('report-to');

        function syncReportDates() {
            toInput.min = fromInput.value;
            if (toInput.value && toInput.value < fromInput.value) {
                toInput.value = fromInput.value;
            }
        }

        fromInput.addEventListener('change', syncReportDates);
        syncReportDates();
    })();
</script>
{{-- Kartu ringkasan hasil perhitungan laporan. --}}
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Hadir</small><h3>{{ $hadir }}</h3></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Izin</small><h3>{{ $izin }}</h3></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Terlambat</small><h3>{{ $terlambat }}</h3></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Absen</small><h3>{{ $absen }}</h3></div></div></div>
</div>
{{-- Tabel detail absensi sesuai filter. --}}
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead>
        {{-- Loop semua baris laporan. --}}
        <tbody>@foreach($rows as $row)<tr><td>{{ $row->attendance_date->format('Y-m-d') }}</td><td>{{ $row->user->name }}</td><td>{{ $row->check_in ?? '-' }}</td><td>{{ $row->check_out ?? '-' }}</td><td>{{ $row->status }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
