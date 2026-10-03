@extends('layouts.app', ['title' => 'Persetujuan Izin'])

@section('content')
{{-- Halaman admin untuk memproses pengajuan izin. --}}
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Karyawan</th><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr></thead>
        {{-- Loop semua pengajuan izin terbaru. --}}
        <tbody>@foreach($rows as $row)<tr>
            <td>{{ $row->user->name }}</td><td>{{ $row->leave_date->format('Y-m-d') }}</td><td>{{ $row->type }}</td><td>{{ $row->reason }}</td>
            <td>@if($row->evidence)<a href="{{ asset($row->evidence) }}" target="_blank">Lihat</a>@else-@endif</td>
            <td>{{ $row->status }}</td>
            <td>
                {{-- Form persetujuan/penolakan izin. --}}
                <form method="post" action="{{ route('leave.process', $row) }}" class="d-flex gap-1 mb-1">@csrf<select name="status" class="form-select form-select-sm"><option value="approved">approved</option><option value="rejected">rejected</option></select><input name="admin_note" class="form-control form-control-sm" placeholder="Catatan"><button class="btn btn-sm btn-primary">Simpan</button></form>
                {{-- Form hapus data izin. --}}
                <form method="post" action="{{ route('leave.destroy', $row) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
            </td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
