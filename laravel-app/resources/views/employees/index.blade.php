@extends('layouts.app', ['title' => 'Data Karyawan'])

@section('content')
<div class="card mb-4"><div class="card-body">
    <h5>{{ $editingEmployee ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h5>
    <form method="post" action="{{ $editingEmployee ? route('employees.update', $editingEmployee) : route('employees.store') }}" class="row g-2">
        @csrf
        @if($editingEmployee) @method('PUT') @endif
        <div class="col-md-3"><label class="form-label">Nama</label><input name="name" class="form-control" value="{{ old('name', $editingEmployee?->name) }}" required></div>
        <div class="col-md-3"><label class="form-label">Username</label><input name="username" class="form-control" value="{{ old('username', $editingEmployee?->username) }}" required></div>
        <div class="col-md-3"><label class="form-label">Jabatan</label><input name="position" class="form-control" value="{{ old('position', $editingEmployee?->position) }}"></div>
        @unless($editingEmployee)
            <div class="col-md-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        @endunless
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">{{ $editingEmployee ? 'Update' : 'Tambah' }}</button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <h5>Daftar Karyawan</h5>
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Nama</th><th>Username</th><th>Jabatan</th><th>Aksi</th></tr></thead>
        <tbody>
        @foreach($employees as $employee)
            <tr>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->username }}</td>
                <td>{{ $employee->position ?? '-' }}</td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        <a href="{{ route('employees.index', ['edit_employee_id' => $employee->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="post" action="{{ route('employees.destroy', $employee) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
                        <form method="post" action="{{ route('employees.reset-password', $employee) }}" class="d-flex gap-1">
                            @csrf
                            <input type="password" name="new_password" class="form-control form-control-sm" placeholder="Password baru" required>
                            <button class="btn btn-sm btn-outline-warning">Reset</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div></div>
@endsection
