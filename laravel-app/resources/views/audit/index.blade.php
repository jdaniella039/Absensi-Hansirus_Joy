@extends('layouts.app', ['title' => 'Audit Log'])

@section('content')
<div class="card mb-4"><div class="card-body">
    <form method="get" action="{{ route('audit.index') }}" class="row g-2">
        <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" name="from" value="{{ $from }}"></div>
        <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" name="to" value="{{ $to }}"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-secondary w-100">Terapkan</button></div>
    </form>
</div></div>
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><td>{{ $row->created_at }}</td><td>{{ $row->user->username ?? 'sistem' }}</td><td>{{ $row->action }}</td><td>{{ $row->description ?? '-' }}</td><td>{{ $row->ip_address ?? '-' }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
