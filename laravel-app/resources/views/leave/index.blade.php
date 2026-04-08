@extends('layouts.app', ['title' => 'Permohonan Izin'])

@section('content')
<div class="card mb-4"><div class="card-body">
    <form method="post" action="{{ route('leave.store') }}" enctype="multipart/form-data" class="row g-2">
        @csrf
        <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="leave_date" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Jenis</label><select name="type" class="form-select"><option value="izin">Izin</option><option value="sakit">Sakit</option><option value="cuti">Cuti</option></select></div>
        <div class="col-md-3"><label class="form-label">Bukti</label><input type="file" name="evidence_file" class="form-control"></div>
        <div class="col-md-5"><label class="form-label">Alasan</label><input name="reason" class="form-control" required></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Kirim</button></div>
    </form>
</div></div>
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Bukti</th><th>Status</th><th>Catatan</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr>
            <td>{{ $row->leave_date->format('Y-m-d') }}</td><td>{{ $row->type }}</td><td>{{ $row->reason }}</td>
            <td>@if($row->evidence)<a href="{{ asset($row->evidence) }}" target="_blank">Lihat</a>@else-@endif</td>
            <td>{{ $row->status }}</td><td>{{ $row->admin_note ?? '-' }}</td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
