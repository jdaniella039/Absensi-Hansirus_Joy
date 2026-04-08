@extends('layouts.app', ['title' => 'Profil Saya'])

@section('content')
<div class="card"><div class="card-body">
    <form method="post" action="{{ route('profile.update') }}" class="row g-2">
        @csrf
        <div class="col-md-6"><label class="form-label">Nama</label><input name="name" class="form-control" value="{{ auth()->user()->name }}" required></div>
        <div class="col-md-6"><label class="form-label">Jabatan</label><input name="position" class="form-control" value="{{ auth()->user()->position }}"></div>
        <div class="col-md-6"><label class="form-label">Password Baru</label><input type="password" name="new_password" class="form-control"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Simpan</button></div>
    </form>
</div></div>
@endsection
