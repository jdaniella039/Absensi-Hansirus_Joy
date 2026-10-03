@extends('layouts.app', ['title' => 'Audit Log'])

@section('content')
{{-- Form filter audit log berdasarkan rentang tanggal. --}}
<div class="card mb-4"><div class="card-body">
    <form method="get" action="{{ route('audit.index') }}" class="row g-2">
        <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" id="audit-from" name="from" value="{{ $from }}" required></div>
        <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" id="audit-to" name="to" value="{{ $to }}" min="{{ $from }}" required></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-secondary w-100">Terapkan</button></div>
    </form>
</div></div>
<script>
    (function () {
        const fromInput = document.getElementById('audit-from');
        const toInput = document.getElementById('audit-to');

        function syncAuditDates() {
            toInput.min = fromInput.value;
            if (toInput.value && toInput.value < fromInput.value) {
                toInput.value = fromInput.value;
            }
        }

        fromInput.addEventListener('change', syncAuditDates);
        syncAuditDates();
    })();
</script>
{{-- Tabel aktivitas sistem/user. --}}
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
        {{-- Loop semua audit log yang dikirim controller. --}}
        <tbody>@foreach($rows as $row)<tr><td>{{ $row->created_at }}</td><td>{{ $row->user->username ?? 'sistem' }}</td><td>{{ $row->action }}</td><td>{{ $row->description ?? '-' }}</td><td>{{ $row->ip_address ?? '-' }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
