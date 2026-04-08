@extends('layouts.app', ['title' => 'Verifikasi Absensi'])

@section('content')
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Masuk</th><th>Pulang</th><th>Bukti Foto</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr>
            <td>{{ $row->attendance_date->format('Y-m-d') }}</td>
            <td>{{ $row->user->name }}</td>
            <td>{{ $row->check_in ?? '-' }}</td>
            <td>{{ $row->check_out ?? '-' }}</td>
            <td class="small">
                <div>Masuk: @if($row->check_in_photo)<a href="{{ asset($row->check_in_photo) }}" target="_blank">Lihat</a>@else-@endif</div>
                <div>Mulai Istirahat: @if($row->break_start_photo)<a href="{{ asset($row->break_start_photo) }}" target="_blank">Lihat</a>@else-@endif</div>
                <div>Selesai Istirahat: @if($row->break_end_photo)<a href="{{ asset($row->break_end_photo) }}" target="_blank">Lihat</a>@else-@endif</div>
                <div>Pulang: @if($row->check_out_photo)<a href="{{ asset($row->check_out_photo) }}" target="_blank">Lihat</a>@else-@endif</div>
            </td>
            <td>{{ $row->status }}</td>
            <td><form method="post" action="{{ route('attendance.verify', $row) }}" class="d-flex gap-1">@csrf<select name="status" class="form-select form-select-sm"><option value="approved">approved</option><option value="rejected">rejected</option></select><input name="verification_note" class="form-control form-control-sm" placeholder="Catatan"><button class="btn btn-sm btn-primary">Simpan</button></form></td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
