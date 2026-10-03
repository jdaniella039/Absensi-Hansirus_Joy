<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\User;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Controller untuk pengelolaan jadwal kerja.
class ScheduleController extends Controller
{
    // Menampilkan halaman admin untuk daftar, tambah, dan edit jadwal.
    public function index(Request $request): View
    {
        // Hanya admin yang boleh mengelola jadwal.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Kirim data karyawan, jadwal edit, dan daftar jadwal ke view.
        return view('schedules.index', [
            'employees' => User::query()->where('role', 'karyawan')->orderBy('name')->get(),
            'editingSchedule' => $request->filled('edit_schedule_id') ? Schedule::find($request->integer('edit_schedule_id')) : null,
            'schedules' => Schedule::query()->with('user')->orderByDesc('work_date')->limit(100)->get(),
        ]);
    }

    // Menampilkan jadwal milik karyawan yang sedang login.
    public function mySchedule(): View
    {
        return view('schedules.my', [
            'schedules' => Schedule::query()->where('user_id', Auth::id())->orderByDesc('work_date')->get(),
        ]);
    }

    // Menyimpan jadwal baru atau memperbarui jadwal lama.
    public function store(Request $request): RedirectResponse
    {
        // Hanya admin yang boleh menyimpan jadwal.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // schedule_id dipakai untuk membedakan mode tambah dan edit.
        $scheduleId = $request->integer('schedule_id');

        // Validasi field jadwal kerja.
        $data = $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'work_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Cek apakah karyawan sudah punya jadwal di tanggal yang sama.
        $duplicateQuery = Schedule::query()
            ->where('user_id', $data['employee_id'])
            ->whereDate('work_date', $data['work_date']);

        // Saat edit, jadwal yang sedang diedit tidak dihitung sebagai duplikat.
        if ($scheduleId > 0) {
            $duplicateQuery->where('id', '!=', $scheduleId);
        }

        // Jika duplikat ditemukan, batalkan simpan.
        if ($duplicateQuery->exists()) {
            return back()->with('error', 'Jadwal untuk karyawan dan tanggal tersebut sudah ada.');
        }

        // Susun data yang akan disimpan ke tabel schedules.
        $payload = [
            'user_id' => $data['employee_id'],
            'work_date' => $data['work_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ];

        // Mode edit jika schedule_id ada, selain itu buat jadwal baru.
        if ($scheduleId > 0) {
            $schedule = Schedule::findOrFail($scheduleId);
            $schedule->update($payload);
            Portal::logAudit((int) Auth::id(), 'UPDATE_JADWAL', "Update jadwal {$schedule->id}");
        } else {
            $schedule = Schedule::query()->create($payload);
            Portal::logAudit((int) Auth::id(), 'CREATE_JADWAL', "Tambah jadwal {$schedule->id}");
        }

        return redirect()->route('schedules.index')->with('success', 'Jadwal berhasil disimpan.');
    }

    // Mengunduh template CSV untuk import jadwal.
    public function downloadTemplate(): StreamedResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fwrite($output, "sep=;\r\n");
            fputcsv($output, ['username', 'tanggal', 'mulai', 'selesai', 'lokasi', 'catatan'], ';', '"', '');
            fputcsv($output, ['contoh_karyawan', now()->addDay()->toDateString(), '08:00', '17:00', 'Kantor', 'Shift pagi'], ';', '"', '');
            fclose($output);
        }, 'schedule-import-template.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    // Mengimpor banyak jadwal dari file CSV secara atomik.
    public function import(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $request->validate([
            'schedules_csv' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ]);

        $handle = fopen((string) $request->file('schedules_csv')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'File CSV jadwal tidak bisa dibaca.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new \InvalidArgumentException('File CSV jadwal kosong.');
            }

            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
            $trimmedFirstLine = trim($firstLine);
            if (strtolower($trimmedFirstLine) === 'sep=;') {
                $delimiter = ';';
                $header = fgetcsv($handle, 0, $delimiter, '"', '');
            } elseif (strtolower($trimmedFirstLine) === 'sep=,') {
                $delimiter = ',';
                $header = fgetcsv($handle, 0, $delimiter, '"', '');
            } else {
                $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
                rewind($handle);
                $header = fgetcsv($handle, 0, $delimiter, '"', '');
            }

            if (!is_array($header) || $header === []) {
                throw new \InvalidArgumentException('Header CSV jadwal tidak valid.');
            }

            $headerMap = [];
            foreach ($header as $index => $columnName) {
                $normalized = preg_replace('/[^a-z]/', '', strtolower(trim((string) $columnName))) ?? '';
                if ($normalized !== '') {
                    $headerMap[$normalized] = $index;
                }
            }

            $columnAliases = [
                'username' => ['username', 'user'],
                'tanggal' => ['tanggal', 'workdate', 'date'],
                'mulai' => ['mulai', 'starttime', 'start'],
                'selesai' => ['selesai', 'endtime', 'end'],
                'lokasi' => ['lokasi', 'location'],
                'catatan' => ['catatan', 'notes', 'note'],
            ];
            $columns = [];
            foreach ($columnAliases as $field => $aliases) {
                foreach ($aliases as $alias) {
                    if (array_key_exists($alias, $headerMap)) {
                        $columns[$field] = $headerMap[$alias];
                        break;
                    }
                }
            }

            if (!isset($columns['username'], $columns['tanggal'], $columns['mulai'], $columns['selesai'])) {
                throw new \InvalidArgumentException('Header CSV wajib memuat kolom username, tanggal, mulai, dan selesai.');
            }

            $employeeMap = User::query()
                ->where('role', 'karyawan')
                ->get(['id', 'username'])
                ->keyBy(fn (User $employee): string => strtolower($employee->username));
            $rowsToInsert = [];
            $scheduleKeys = [];
            $lineNumber = 1;

            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNumber++;
                $cells = array_map(static fn ($value): string => trim((string) $value), $row);
                if ($cells === [] || count(array_filter($cells, static fn ($value): bool => $value !== '')) === 0) {
                    continue;
                }

                $username = $cells[$columns['username']] ?? '';
                $workDate = $cells[$columns['tanggal']] ?? '';
                $startTime = $cells[$columns['mulai']] ?? '';
                $endTime = $cells[$columns['selesai']] ?? '';
                $location = isset($columns['lokasi']) ? ($cells[$columns['lokasi']] ?? '') : '';
                $notes = isset($columns['catatan']) ? ($cells[$columns['catatan']] ?? '') : '';

                if ($username === '' || $workDate === '' || $startTime === '' || $endTime === '') {
                    throw new \InvalidArgumentException("Baris {$lineNumber} tidak lengkap. Username, tanggal, mulai, dan selesai wajib diisi.");
                }

                $employee = $employeeMap->get(strtolower($username));
                if (!$employee) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: username {$username} tidak ditemukan sebagai karyawan.");
                }

                $dateObject = \DateTime::createFromFormat('!Y-m-d', $workDate);
                if (!$dateObject || $dateObject->format('Y-m-d') !== $workDate) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: tanggal harus berformat YYYY-MM-DD.");
                }
                if ($workDate < now()->toDateString()) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: tanggal jadwal tidak boleh lebih awal dari hari ini.");
                }

                $timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';
                if (!preg_match($timePattern, $startTime) || !preg_match($timePattern, $endTime)) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: jam mulai dan selesai harus berformat HH:MM.");
                }
                $startTime = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
                $endTime = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;
                if ($startTime >= $endTime) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: jam mulai harus lebih kecil dari jam selesai.");
                }
                if (strlen($location) > 120 || strlen($notes) > 255) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: lokasi maksimal 120 karakter dan catatan maksimal 255 karakter.");
                }

                $scheduleKey = $employee->id . '|' . $workDate;
                if (isset($scheduleKeys[$scheduleKey])) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: jadwal {$username} pada {$workDate} duplikat di file CSV.");
                }
                if (Schedule::query()->where('user_id', $employee->id)->whereDate('work_date', $workDate)->exists()) {
                    throw new \InvalidArgumentException("Baris {$lineNumber}: jadwal {$username} pada {$workDate} sudah ada.");
                }

                $scheduleKeys[$scheduleKey] = true;
                $rowsToInsert[] = [
                    'user_id' => $employee->id,
                    'work_date' => $workDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'location' => $location ?: null,
                    'notes' => $notes ?: null,
                    'created_by' => Auth::id(),
                ];
            }
        } catch (\InvalidArgumentException $exception) {
            fclose($handle);
            return back()->with('error', $exception->getMessage());
        }
        fclose($handle);

        if ($rowsToInsert === []) {
            return back()->with('error', 'Tidak ada data jadwal yang bisa diimpor dari CSV.');
        }

        DB::transaction(function () use ($rowsToInsert): void {
            foreach ($rowsToInsert as $scheduleRow) {
                Schedule::query()->create($scheduleRow);
            }
        });

        Portal::logAudit((int) Auth::id(), 'IMPORT_JADWAL_CSV', 'Import CSV ' . count($rowsToInsert) . ' jadwal');

        return redirect()->route('schedules.index')->with('success', count($rowsToInsert) . ' jadwal berhasil diimpor dari CSV.');
    }
    // Karyawan mengonfirmasi bahwa jadwalnya sudah dilihat.
    public function acknowledge(Schedule $schedule): RedirectResponse
    {
        // User hanya boleh mengonfirmasi jadwal miliknya sendiri.
        abort_unless($schedule->user_id === Auth::id(), 403);

        $schedule->update(['acknowledged_at' => now()]);
        Portal::logAudit((int) Auth::id(), 'KONFIRMASI_JADWAL', "Konfirmasi jadwal {$schedule->id}");

        return back()->with('success', 'Jadwal berhasil dikonfirmasi.');
    }

    // Menghapus jadwal oleh admin.
    public function destroy(Schedule $schedule): RedirectResponse
    {
        // Hanya admin yang boleh menghapus jadwal.
        abort_unless(Auth::user()?->isAdmin(), 403);

        // Simpan ID untuk audit sebelum record dihapus.
        $scheduleId = $schedule->id;
        $schedule->delete();
        Portal::logAudit((int) Auth::id(), 'DELETE_JADWAL', "Hapus jadwal {$scheduleId}");

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
