@extends('layouts.app', ['title' => 'Feedback Masuk'])

@section('content')
{{-- Tabel feedback masuk yang bisa dibaca admin. --}}
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Waktu</th><th>Karyawan</th><th>Username</th><th>Kategori</th><th>Pesan</th></tr></thead>
        {{-- Loop semua feedback terbaru dari karyawan. --}}
        <tbody>@foreach($rows as $row)<tr><td>{{ $row->created_at }}</td><td>{{ $row->user->name }}</td><td>{{ $row->user->username }}</td><td>{{ $row->category }}</td><td>{{ $row->message }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
