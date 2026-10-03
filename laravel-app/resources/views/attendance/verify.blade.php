@extends('layouts.app', ['title' => 'Verifikasi Absensi'])

@section('content')
{{-- Halaman admin untuk memeriksa dan memverifikasi absensi. --}}
<div class="card"><div class="card-body">
    <form id="bulk-attendance-form" method="post" action="{{ route('attendance.bulk-verify') }}" class="row g-2 align-items-end mb-3">
        @csrf
        <div class="col-md-2">
            <label class="form-label">Status Massal</label>
            <select name="status" class="form-select" required>
                <option value="" selected disabled>Pilih status</option>
                <option value="approved">approved</option>
                <option value="rejected">rejected</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Catatan Massal</label>
            <input name="verification_note" class="form-control" maxlength="255" placeholder="Opsional; kosongkan untuk catatan validasi sistem">
        </div>
        <div class="col-md-2">
            <div class="small text-muted mb-2"><span id="attendance-selected-count">0</span> dipilih</div>
            <button id="bulk-attendance-submit" class="btn btn-primary w-100" disabled>Verifikasi Terpilih</button>
        </div>
    </form>
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th><input id="attendance-select-all" type="checkbox" class="form-check-input" aria-label="Pilih semua absensi"></th><th>Tanggal</th><th>Karyawan</th><th>Jadwal</th><th>Masuk</th><th>Pulang</th><th>Bukti Foto</th><th>Validasi Sistem</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@foreach($rows as $row)
        @php
            $assessment = $row->system_assessment;
            $suggestedStatus = $row->status === 'pending' ? $assessment['recommendation'] : $row->status;
        @endphp
        <tr>
            <td><input type="checkbox" class="form-check-input attendance-select" form="bulk-attendance-form" name="attendance_ids[]" value="{{ $row->id }}" aria-label="Pilih absensi {{ $row->user->name }} tanggal {{ $row->attendance_date->format('Y-m-d') }}"></td>
            <td>{{ $row->attendance_date->format('Y-m-d') }}</td>
            <td>{{ $row->user->name }}</td>
            <td>{{ $row->schedule_start ? $row->schedule_start . ' - ' . $row->schedule_end : '-' }}</td>
            <td>{{ $row->check_in ?? '-' }}</td>
            <td>{{ $row->check_out ?? '-' }}</td>
            <td class="small">
                <div>Masuk: @if($row->check_in_photo)<a href="{{ asset($row->check_in_photo) }}" target="_blank">Lihat</a>@else-@endif</div>
                <div>Pulang: @if($row->check_out_photo)<a href="{{ asset($row->check_out_photo) }}" target="_blank">Lihat</a>@else-@endif</div>
            </td>
            <td>
                <span class="badge bg-{{ $assessment['is_match'] ? 'success' : ($assessment['is_ongoing'] ? 'info text-dark' : 'warning text-dark') }}">{{ $assessment['label'] }}</span>
                <div class="small text-muted mt-1">{{ $assessment['summary'] }}</div>
            </td>
            <td>{{ $row->status }}</td>
            <td>
                <form method="post" action="{{ route('attendance.verify', $row) }}" class="d-flex gap-1">
                    @csrf
                    <select name="status" class="form-select form-select-sm" required>
                        @if($suggestedStatus === 'pending')<option value="" selected disabled>Pilih keputusan</option>@endif
                        <option value="approved" @selected($suggestedStatus === 'approved')>approved</option>
                        <option value="rejected" @selected($suggestedStatus === 'rejected')>rejected</option>
                    </select>
                    <input name="verification_note" class="form-control form-control-sm" value="{{ $row->verification_note }}" placeholder="Catatan">
                    <button class="btn btn-sm btn-primary">Simpan</button>
                </form>
            </td>
        </tr>
        @endforeach</tbody>
    </table></div>
</div></div>
<script>
    (function () {
        const form = document.getElementById('bulk-attendance-form');
        const selectAll = document.getElementById('attendance-select-all');
        const checkboxes = Array.from(document.querySelectorAll('.attendance-select'));
        const selectedCount = document.getElementById('attendance-selected-count');
        const submitButton = document.getElementById('bulk-attendance-submit');

        function updateSelection() {
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            selectedCount.textContent = String(checkedCount);
            submitButton.disabled = checkedCount === 0;
            selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }

        selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
            updateSelection();
        });
        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Verifikasi semua data absensi yang dipilih?')) {
                event.preventDefault();
            }
        });
        updateSelection();
    })();
</script>
@endsection