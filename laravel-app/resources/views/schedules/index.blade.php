@extends('layouts.app', ['title' => 'Kelola Jadwal'])

@section('content')
{{-- Form tambah atau edit jadwal kerja. --}}
<div class="card mb-4"><div class="card-body">
    <h5>{{ $editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}</h5>
    <form method="post" action="{{ route('schedules.store') }}" class="row g-2">
        @csrf
        {{-- schedule_id diisi saat mode edit, kosong saat tambah baru. --}}
        <input type="hidden" name="schedule_id" value="{{ $editingSchedule?->id }}">
        <div class="col-md-3">
            <label class="form-label">Karyawan</label>
            <div class="input-group mb-2"><input type="search" id="schedule-employee-search" class="form-control" placeholder="Cari nama atau username" autocomplete="off"><button type="button" id="schedule-employee-search-button" class="btn btn-outline-secondary">Cari</button></div>
            <select name="employee_id" id="schedule-employee-select" class="form-select">@foreach($employees as $employee)<option value="{{ $employee->id }}" data-search="{{ strtolower($employee->name . ' ' . $employee->username) }}" @selected(old('employee_id', $editingSchedule?->user_id) == $employee->id)>{{ $employee->name }} ({{ $employee->username }})</option>@endforeach</select>
        </div>
        <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="work_date" class="form-control" value="{{ old('work_date', optional($editingSchedule?->work_date)->format('Y-m-d')) }}" min="{{ now()->toDateString() }}" required></div>
        <div class="col-md-2"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time', $editingSchedule?->start_time) }}" required></div>
        <div class="col-md-2"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time', $editingSchedule?->end_time) }}" required></div>
        <div class="col-md-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" value="{{ old('location', $editingSchedule?->location) }}"></div>
        <div class="col-md-10"><label class="form-label">Catatan</label><input name="notes" class="form-control" value="{{ old('notes', $editingSchedule?->notes) }}"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">{{ $editingSchedule ? 'Update' : 'Simpan' }}</button></div>
    </form>
</div></div>

<script>
    (function () {
        const searchInput = document.getElementById('schedule-employee-search');
        const searchButton = document.getElementById('schedule-employee-search-button');
        const employeeSelect = document.getElementById('schedule-employee-select');

        function searchEmployee() {
            const keyword = searchInput.value.trim().toLowerCase();
            if (!keyword) {
                searchInput.setCustomValidity('Masukkan nama atau username karyawan.');
                searchInput.reportValidity();
                return;
            }

            const match = Array.from(employeeSelect.options).find((option) => option.dataset.search.includes(keyword));
            if (!match) {
                searchInput.setCustomValidity('Karyawan tidak ditemukan.');
                searchInput.reportValidity();
                return;
            }

            searchInput.setCustomValidity('');
            employeeSelect.value = match.value;
            employeeSelect.focus();
        }

        searchInput.addEventListener('input', () => searchInput.setCustomValidity(''));
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchEmployee();
            }
        });
        searchButton.addEventListener('click', searchEmployee);
    })();
</script>
@if(!$editingSchedule)
<div class="card mb-4"><div class="card-body">
    <h5 class="mb-1">Import Jadwal dari CSV</h5>
    <p class="text-muted mb-2">Tambahkan banyak jadwal sekaligus menggunakan username karyawan.</p>
    <div class="mb-3"><a href="{{ route('schedules.template') }}" class="btn btn-outline-secondary">Download Template CSV</a></div>
    <form method="post" action="{{ route('schedules.import') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-8"><label class="form-label">File CSV Jadwal</label><input type="file" name="schedules_csv" class="form-control" accept=".csv,text/csv" required></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Import CSV</button></div>
    </form>
    <div class="small text-muted mt-2">Kolom wajib: username, tanggal, mulai, selesai. Lokasi dan catatan bersifat opsional. Maksimal 2 MB.</div>
</div></div>
@endif

{{-- Tabel daftar jadwal kerja yang sudah dibuat admin. --}}
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jam</th><th>Lokasi</th><th>Konfirmasi</th><th>Aksi</th></tr></thead>
        {{-- Loop semua jadwal dari controller. --}}
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
