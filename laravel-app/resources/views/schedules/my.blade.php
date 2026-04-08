@extends('layouts.app', ['title' => 'Jadwal Kerja Saya'])

@section('content')
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Tanggal</th><th>Mulai</th><th>Selesai</th><th>Lokasi</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@foreach($schedules as $schedule)<tr>
            <td>{{ $schedule->work_date->format('Y-m-d') }}</td>
            <td>{{ $schedule->start_time }}</td>
            <td>{{ $schedule->end_time }}</td>
            <td>{{ $schedule->location ?? '-' }}</td>
            <td>{{ $schedule->notes ?? '-' }}</td>
            <td>{{ $schedule->acknowledged_at ?? 'Belum' }}</td>
            <td>@if(!$schedule->acknowledged_at)<form method="post" action="{{ route('schedules.acknowledge', $schedule) }}">@csrf<button class="btn btn-sm btn-success">Konfirmasi</button></form>@else<span class="text-success">Terkonfirmasi</span>@endif</td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
