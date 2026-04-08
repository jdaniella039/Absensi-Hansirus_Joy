@extends('layouts.app', ['title' => 'Feedback'])

@section('content')
<div class="card mb-4"><div class="card-body">
    <form method="post" action="{{ route('feedback.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><label class="form-label">Kategori</label><select name="category" class="form-select"><option value="saran">Saran</option><option value="kritik">Kritik</option><option value="masalah">Masalah</option></select></div>
        <div class="col-md-9"><label class="form-label">Pesan</label><input name="message" class="form-control" required></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Kirim</button></div>
    </form>
</div></div>
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Waktu</th><th>Kategori</th><th>Pesan</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><td>{{ $row->created_at }}</td><td>{{ $row->category }}</td><td>{{ $row->message }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
