@extends('layouts.app', ['title' => 'Kelola Jadwal'])

@section('content')
<div class="card mb-4"><div class="card-body">
    <h5>{{ $editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}</h5>
    <form method="post" action="{{ route('schedules.store') }}" class="row g-2">
        @csrf
        <input type="hidden" name="schedule_id" value="{{ $editingSchedule?->id }}">
        <div class="col-md-3"><label class="form-label">Karyawan</label><select name="employee_id" class="form-select">@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id', $editingSchedule?->user_id) == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="work_date" class="form-control" value="{{ old('work_date', optional($editingSchedule?->work_date)->format('Y-m-d')) }}" required></div>
        <div class="col-md-2"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time', $editingSchedule?->start_time) }}" required></div>
        <div class="col-md-2"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time', $editingSchedule?->end_time) }}" required></div>
        <div class="col-md-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" value="{{ old('location', $editingSchedule?->location) }}"></div>
        <div class="col-md-10"><label class="form-label">Catatan</label><input name="notes" class="form-control" value="{{ old('notes', $editingSchedule?->notes) }}"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">{{ $editingSchedule ? 'Update' : 'Simpan' }}</button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jam</th><th>Lokasi</th><th>Konfirmasi</th><th>Aksi</th></tr></thead>
        <tbody>@foreach($schedules as $schedule)<tr>
            <td>{{ $schedule->work_date->format('Y-m-d') }}</td>
            <td>{{ $schedule->user->name }}</td>
            <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
            <td>{{ $schedule->location ?? '-' }}</td>
            <td>{{ $schedule->acknowledged_at ?? 'Belum' }}</td>
            <td><div class="d-flex gap-1"><a href="{{ route('schedules.index', ['edit_schedule_id' => $schedule->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a><form method="post" action="{{ route('schedules.destroy', $schedule) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></div></td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
