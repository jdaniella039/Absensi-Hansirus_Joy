<?php

// Mengaktifkan mode tipe ketat untuk handler action.
declare(strict_types=1);

// Memuat helper utama seperti db(), post(), flash(), need_auth(), dan need_admin().
require_once __DIR__ . '/bootstrap.php';

// File ini hanya memproses request POST; request GET langsung dikembalikan ke router halaman.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

// Semua form mengirim input hidden "action" untuk menentukan proses yang dijalankan.
$action = post('action');

// try/catch dipakai agar error bisa ditampilkan sebagai flash message, bukan blank page.
try {
    // Proses login user berdasarkan username dan password.
    if ($action === 'login') {
        // Cari user dari database berdasarkan username.
        $stmt = db()->prepare('SELECT * FROM users WHERE username=?');
        $stmt->execute([post('username')]);
        $u = $stmt->fetch();

        // Jika user ada dan password cocok dengan hash, simpan user_id ke session.
        if ($u && password_verify(post('password'), $u['password_hash'])) {
            $_SESSION['user_id'] = (int) $u['id'];
            log_audit((int) $u['id'], 'LOGIN', 'Masuk sistem');
            header('Location: ?page=dashboard');
            exit;
        }

        // Jika gagal login, tampilkan pesan lalu kembali ke halaman login.
        flash('Username atau password salah.', 'danger');
        header('Location: ?page=login');
        exit;
    }

    // Proses logout user yang sedang login.
    if ($action === 'logout') {
        // Catat logout jika session user masih valid.
        $u = user();
        if ($u) {
            log_audit((int) $u['id'], 'LOGOUT', 'Keluar sistem');
        }

        // Hapus session lalu arahkan kembali ke login.
        session_destroy();
        header('Location: ?page=login');
        exit;
    }

    // Proses absensi karyawan: masuk, mulai istirahat, selesai istirahat, atau pulang.
    if ($action === 'attendance_submit') {
        need_auth();
        $u = user();
        $now = date('Y-m-d H:i:s');
        $kind = post('kind');
        $evidencePath = null;

        // Mapping jenis absensi dari form ke kolom database attendance_logs.
        $map = [
            'masuk' => 'check_in',
            'pulang' => 'check_out',
        ];

        // Validasi agar hanya jenis absensi yang dikenal yang boleh diproses.
        if (!isset($map[$kind])) {
            flash('Jenis absensi tidak valid.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }

        // Ambil file bukti dari input kamera atau galeri.
        $cameraFile = $_FILES['attendance_evidence_camera'] ?? null;
        $galleryFile = $_FILES['attendance_evidence_gallery'] ?? null;
        $cameraError = (int) ($cameraFile['error'] ?? UPLOAD_ERR_NO_FILE);
        $galleryError = (int) ($galleryFile['error'] ?? UPLOAD_ERR_NO_FILE);

        // Foto bukti wajib ada minimal dari salah satu input.
        if ($cameraError === UPLOAD_ERR_NO_FILE && $galleryError === UPLOAD_ERR_NO_FILE) {
            flash('Foto bukti absensi wajib diunggah dari kamera atau galeri.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }

        // Prioritaskan file kamera jika ada; kalau tidak, pakai file galeri.
        $file = $cameraError !== UPLOAD_ERR_NO_FILE ? $cameraFile : $galleryFile;
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        // Pastikan upload sukses dari sisi PHP.
        if ($error !== UPLOAD_ERR_OK) {
            flash('Upload foto bukti absensi gagal.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }

        // Batasi ukuran file agar storage tidak cepat penuh.
        if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
            flash('Ukuran foto bukti absensi maksimal 10 MB.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }

        // Batasi ekstensi file hanya gambar umum.
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($extension, $allowedExtensions, true)) {
            flash('Format foto bukti absensi hanya boleh JPG, JPEG, atau PNG.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }

        // Ambil data absensi hari ini; jika belum ada, buat row awal terlebih dahulu.
        $row = today_attendance((int) $u['id']);
        if (!$row) {
            db()->prepare("INSERT INTO attendance_logs (user_id, attendance_date, status) VALUES (?, CURDATE(), 'pending')")
                ->execute([(int) $u['id']]);
            $row = today_attendance((int) $u['id']);
        }

        // Cegah user mengisi jenis absensi yang sama dua kali dalam satu hari.
        $col = $map[$kind];
        if (!empty($row[$col])) {
            flash('Absensi ini sudah pernah direkam hari ini.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }

        // Buat nama file unik berdasarkan user, jenis absensi, dan waktu.
        $filename = sprintf(
            'attendance-%d-%s-%s.%s',
            (int) $u['id'],
            $kind,
            date('YmdHis'),
            $extension
        );
        $target = attendance_evidence_directory() . '/' . $filename;

        // Pindahkan file upload dari temporary folder ke folder storage aplikasi.
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            flash('Foto bukti absensi tidak bisa disimpan.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }
        $evidencePath = 'storage/attendance_evidence/' . $filename;


        // Validasi urutan absensi: pulang harus setelah check in.
        if ($kind === 'pulang' && empty($row['check_in'])) {
            delete_attendance_evidence($evidencePath);
            flash('Absensi masuk harus dilakukan sebelum pulang.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }


        // Hapus bukti lama agar hanya bukti terbaru yang tersimpan untuk record hari ini.
        delete_attendance_evidence((string) ($row['evidence_photo'] ?? ''));

        // Simpan waktu absensi dan reset status verifikasi menjadi pending.
        db()->prepare("UPDATE attendance_logs SET {$col}=?, evidence_photo=?, status='pending', verification_note=NULL, verified_by=NULL, verified_at=NULL WHERE id=?")
            ->execute([$now, $evidencePath, (int) $row['id']]);

        // Catat audit dan kembali ke halaman absensi.
        log_audit((int) $u['id'], 'ABSENSI_' . strtoupper($kind), 'Karyawan melakukan absensi');
        flash('Absensi berhasil disimpan. Menunggu verifikasi admin.');
        header('Location: ?page=attendance');
        exit;
    }

    // Proses tambah atau edit jadwal kerja karyawan.
    if ($action === 'save_schedule') {
        need_admin();
        $id = (int) post('schedule_id', '0');
        $employeeId = (int) post('employee_id');
        $workDate = post('work_date');
        $startTime = post('start_time');
        $endTime = post('end_time');

        // Pastikan target jadwal adalah user karyawan yang valid.
        if (!employee_exists($employeeId)) {
            flash('Karyawan yang dipilih tidak valid.', 'danger');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        // Tanggal dan jam kerja wajib diisi.
        if ($workDate === '' || $startTime === '' || $endTime === '') {
            flash('Tanggal dan jam kerja wajib diisi.', 'danger');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        // Tanggal jadwal tidak boleh mundur dari tanggal sistem hari ini.
        if ($workDate < date('Y-m-d')) {
            flash('Tanggal jadwal tidak boleh lebih awal dari hari ini.', 'warning');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        // Jam mulai harus sebelum jam selesai.
        if ($startTime >= $endTime) {
            flash('Jam mulai harus lebih kecil dari jam selesai.', 'danger');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        // Satu karyawan tidak boleh punya dua jadwal di tanggal yang sama.
        if (schedule_exists_for_date($employeeId, $workDate, $id)) {
            flash('Jadwal untuk karyawan dan tanggal tersebut sudah ada.', 'warning');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        // Data yang sama dipakai untuk insert dan update jadwal.
        $data = [
            $employeeId,
            $workDate,
            $startTime,
            $endTime,
            post('location'),
            post('notes'),
            (int) user()['id'],
        ];

        // Jika schedule_id ada, berarti update; jika tidak, buat jadwal baru.
        if ($id > 0) {
            db()->prepare('UPDATE schedules SET user_id=?, work_date=?, start_time=?, end_time=?, location=?, notes=?, created_by=? WHERE id=?')
                ->execute([...$data, $id]);
            log_audit((int) user()['id'], 'UPDATE_JADWAL', "Update jadwal {$id}");
        } else {
            db()->prepare('INSERT INTO schedules (user_id, work_date, start_time, end_time, location, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute($data);
            log_audit((int) user()['id'], 'CREATE_JADWAL', 'Tambah jadwal');
        }

        flash('Jadwal berhasil disimpan.');
        header('Location: ?page=schedules');
        exit;
    }

    // Proses import banyak jadwal dari file CSV.
    if ($action === 'import_schedules_csv') {
        need_admin();

        if (!isset($_FILES['schedules_csv']) || (int) ($_FILES['schedules_csv']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash('File CSV jadwal wajib dipilih.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }

        $file = $_FILES['schedules_csv'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            flash('Upload file CSV jadwal gagal.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }

        if (strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'csv') {
            flash('File import jadwal harus berformat CSV.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }

        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            flash('Ukuran file CSV jadwal maksimal 2 MB.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }

        $handle = fopen((string) $file['tmp_name'], 'r');
        if ($handle === false) {
            flash('File CSV jadwal tidak bisa dibaca.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }

        $failImport = static function (string $message) use (&$handle): void {
            if (is_resource($handle)) {
                fclose($handle);
            }
            flash($message, 'danger');
            header('Location: ?page=schedules');
            exit;
        };

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            $failImport('File CSV jadwal kosong.');
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
            $failImport('Header CSV jadwal tidak valid.');
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
            $failImport('Header CSV wajib memuat kolom username, tanggal, mulai, dan selesai.');
        }

        $employeeRows = db()->query("SELECT id, username FROM users WHERE role='karyawan'")->fetchAll();
        $employeeMap = [];
        foreach ($employeeRows as $employee) {
            $employeeMap[strtolower((string) $employee['username'])] = (int) $employee['id'];
        }

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
                $failImport("Baris {$lineNumber} tidak lengkap. Username, tanggal, mulai, dan selesai wajib diisi.");
            }

            $employeeId = $employeeMap[strtolower($username)] ?? 0;
            if ($employeeId === 0) {
                $failImport("Baris {$lineNumber}: username {$username} tidak ditemukan sebagai karyawan.");
            }

            $dateObject = DateTime::createFromFormat('!Y-m-d', $workDate);
            if (!$dateObject || $dateObject->format('Y-m-d') !== $workDate) {
                $failImport("Baris {$lineNumber}: tanggal harus berformat YYYY-MM-DD.");
            }
            if ($workDate < date('Y-m-d')) {
                $failImport("Baris {$lineNumber}: tanggal jadwal tidak boleh lebih awal dari hari ini.");
            }

            $timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';
            if (!preg_match($timePattern, $startTime) || !preg_match($timePattern, $endTime)) {
                $failImport("Baris {$lineNumber}: jam mulai dan selesai harus berformat HH:MM.");
            }
            $startTime = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
            $endTime = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;
            if ($startTime >= $endTime) {
                $failImport("Baris {$lineNumber}: jam mulai harus lebih kecil dari jam selesai.");
            }

            if (strlen($location) > 120 || strlen($notes) > 255) {
                $failImport("Baris {$lineNumber}: lokasi maksimal 120 karakter dan catatan maksimal 255 karakter.");
            }

            $scheduleKey = $employeeId . '|' . $workDate;
            if (isset($scheduleKeys[$scheduleKey])) {
                $failImport("Baris {$lineNumber}: jadwal {$username} pada {$workDate} duplikat di file CSV.");
            }
            if (schedule_exists_for_date($employeeId, $workDate)) {
                $failImport("Baris {$lineNumber}: jadwal {$username} pada {$workDate} sudah ada.");
            }

            $scheduleKeys[$scheduleKey] = true;
            $rowsToInsert[] = [
                'user_id' => $employeeId,
                'work_date' => $workDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'location' => $location,
                'notes' => $notes,
            ];
        }
        fclose($handle);

        if ($rowsToInsert === []) {
            flash('Tidak ada data jadwal yang bisa diimpor dari CSV.', 'warning');
            header('Location: ?page=schedules');
            exit;
        }

        $insertStmt = db()->prepare('INSERT INTO schedules (user_id, work_date, start_time, end_time, location, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        db()->beginTransaction();
        try {
            foreach ($rowsToInsert as $scheduleRow) {
                $insertStmt->execute([
                    $scheduleRow['user_id'],
                    $scheduleRow['work_date'],
                    $scheduleRow['start_time'],
                    $scheduleRow['end_time'],
                    $scheduleRow['location'],
                    $scheduleRow['notes'],
                    (int) user()['id'],
                ]);
            }
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }

        log_audit((int) user()['id'], 'IMPORT_JADWAL_CSV', 'Import CSV ' . count($rowsToInsert) . ' jadwal');
        flash(count($rowsToInsert) . ' jadwal berhasil diimpor dari CSV.');
        header('Location: ?page=schedules');
        exit;
    }
    // Proses tambah satu data karyawan secara manual.
    if ($action === 'create_employee') {
        need_admin();
        $name = post('name');
        $username = post('username');
        $position = post('position');
        $password = post('password');

        // Field utama wajib terisi.
        if ($name === '' || $username === '' || $password === '') {
            flash('Nama, username, dan password wajib diisi.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Username harus unik.
        $check = db()->prepare('SELECT COUNT(*) FROM users WHERE username=?');
        $check->execute([$username]);
        if ((int) $check->fetchColumn() > 0) {
            flash('Username sudah dipakai.', 'warning');
            header('Location: ?page=employees');
            exit;
        }

        // Simpan karyawan baru dengan role karyawan dan password yang sudah di-hash.
        db()->prepare("INSERT INTO users (name, username, password_hash, role, position) VALUES (?, ?, ?, 'karyawan', ?)")
            ->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $position]);

        log_audit((int) user()['id'], 'CREATE_KARYAWAN', "Tambah karyawan {$username}");
        flash('Data karyawan berhasil ditambahkan.');
        header('Location: ?page=employees');
        exit;
    }

    // Proses import banyak karyawan dari file CSV.
    if ($action === 'import_employees_csv') {
        need_admin();

        // Pastikan file CSV benar-benar dipilih.
        if (!isset($_FILES['employees_csv']) || (int) ($_FILES['employees_csv']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash('File CSV karyawan wajib dipilih.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Ambil data file yang diupload.
        $file = $_FILES['employees_csv'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        // Pastikan proses upload sukses.
        if ($error !== UPLOAD_ERR_OK) {
            flash('Upload file CSV karyawan gagal.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // File import harus berekstensi CSV.
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            flash('File import harus berformat CSV.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Batasi ukuran CSV agar proses import tetap ringan.
        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            flash('Ukuran file CSV maksimal 2 MB.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Buka file sementara hasil upload untuk dibaca baris per baris.
        $handle = fopen((string) $file['tmp_name'], 'r');
        if ($handle === false) {
            flash('File CSV tidak bisa dibaca.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Delimiter default titik koma karena template CSV memakai format Excel Indonesia.
        $delimiter = ';';
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            flash('File CSV kosong.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Hapus BOM jika file CSV berasal dari Excel/UTF-8 with BOM.
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
        $trimmedFirstLine = trim($firstLine);

        // Dukung format "sep=;" atau "sep=," dari Excel.
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

        // Header wajib bisa dibaca agar kolom data bisa dipetakan.
        if (!is_array($header) || $header === []) {
            fclose($handle);
            flash('Header CSV tidak valid.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Normalisasi nama header agar variasi penulisan tetap terbaca.
        $normalizeHeader = static function ($value): string {
            $value = strtolower(trim((string) $value));
            return preg_replace('/[^a-z]/', '', $value) ?? '';
        };

        // Buat map nama header ke index kolomnya.
        $headerMap = [];
        foreach ($header as $index => $columnName) {
            $normalized = $normalizeHeader($columnName);
            if ($normalized !== '') {
                $headerMap[$normalized] = $index;
            }
        }

        // Daftar alias nama kolom yang diterima.
        $requiredMap = [
            'nama' => ['nama', 'name'],
            'username' => ['username', 'user'],
            'jabatan' => ['jabatan', 'position', 'posisi'],
            'password' => ['password', 'passwordawal', 'pass'],
        ];

        // Cari index kolom yang cocok dengan alias di atas.
        $resolvedIndexes = [];
        foreach ($requiredMap as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $headerMap)) {
                    $resolvedIndexes[$field] = $headerMap[$alias];
                    break;
                }
            }
        }

        // Kolom nama, username, dan password wajib ada; jabatan opsional.
        if (!isset($resolvedIndexes['nama'], $resolvedIndexes['username'], $resolvedIndexes['password'])) {
            fclose($handle);
            flash('Header CSV wajib memuat kolom nama, username, dan password. Kolom jabatan opsional.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        $rowsToInsert = [];
        $usernamesInFile = [];
        $lineNumber = 1;

        // Baca setiap baris CSV lalu kumpulkan data valid ke array.
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $lineNumber++;

            // Rapikan isi sel dan lewati baris kosong.
            $cells = array_map(static fn ($value): string => trim((string) $value), $row);
            if ($cells === [] || count(array_filter($cells, static fn ($value): bool => $value !== '')) === 0) {
                continue;
            }

            // Ambil nilai berdasarkan index header yang sudah ditemukan.
            $name = $cells[$resolvedIndexes['nama']] ?? '';
            $username = $cells[$resolvedIndexes['username']] ?? '';
            $position = isset($resolvedIndexes['jabatan']) ? ($cells[$resolvedIndexes['jabatan']] ?? '') : '';
            $password = $cells[$resolvedIndexes['password']] ?? '';

            // Validasi data wajib per baris.
            if ($name === '' || $username === '' || $password === '') {
                fclose($handle);
                flash("Baris {$lineNumber} tidak lengkap. Nama, username, dan password wajib diisi.", 'danger');
                header('Location: ?page=employees');
                exit;
            }

            // Cegah username duplikat di dalam file yang sama.
            $usernameKey = strtolower($username);
            if (isset($usernamesInFile[$usernameKey])) {
                fclose($handle);
                flash("Username {$username} duplikat di file CSV.", 'danger');
                header('Location: ?page=employees');
                exit;
            }

            $usernamesInFile[$usernameKey] = true;
            $rowsToInsert[] = [
                'name' => $name,
                'username' => $username,
                'position' => $position,
                'password' => $password,
            ];
        }

        // Tutup file setelah selesai dibaca.
        fclose($handle);

        // Jika tidak ada baris valid, import dibatalkan.
        if ($rowsToInsert === []) {
            flash('Tidak ada data karyawan yang bisa diimpor dari CSV.', 'warning');
            header('Location: ?page=employees');
            exit;
        }

        // Cek username yang sudah ada di database sebelum insert massal.
        $placeholders = implode(',', array_fill(0, count($rowsToInsert), '?'));
        $existingStmt = db()->prepare("SELECT username FROM users WHERE LOWER(username) IN ({$placeholders})");
        $existingStmt->execute(array_map(static fn (array $row): string => strtolower($row['username']), $rowsToInsert));
        $existingUsernames = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
        if ($existingUsernames !== []) {
            flash('Import dibatalkan. Username sudah dipakai: ' . implode(', ', $existingUsernames), 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Insert semua karyawan di dalam transaction agar kalau satu gagal, semuanya dibatalkan.
        $insertStmt = db()->prepare("INSERT INTO users (name, username, password_hash, role, position) VALUES (?, ?, ?, 'karyawan', ?)");
        db()->beginTransaction();
        try {
            foreach ($rowsToInsert as $row) {
                $insertStmt->execute([
                    $row['name'],
                    $row['username'],
                    password_hash($row['password'], PASSWORD_DEFAULT),
                    $row['position'],
                ]);
            }
            db()->commit();
        } catch (Throwable $e) {
            // Rollback jika terjadi error saat proses insert massal.
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }

        log_audit((int) user()['id'], 'IMPORT_KARYAWAN_CSV', 'Import CSV ' . count($rowsToInsert) . ' karyawan');
        flash(count($rowsToInsert) . ' data karyawan berhasil diimpor dari CSV.');
        header('Location: ?page=employees');
        exit;
    }

    // Proses edit data nama, username, dan jabatan karyawan.
    if ($action === 'update_employee') {
        need_admin();
        $employeeId = (int) post('employee_id');
        $name = post('name');
        $username = post('username');
        $position = post('position');

        // Pastikan data karyawan yang diedit masih ada.
        if (!employee_exists($employeeId)) {
            flash('Karyawan tidak ditemukan.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Nama dan username tidak boleh kosong.
        if ($name === '' || $username === '') {
            flash('Nama dan username wajib diisi.', 'danger');
            header('Location: ?page=employees&edit_employee_id=' . $employeeId);
            exit;
        }

        // Username tidak boleh sama dengan karyawan lain.
        $check = db()->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>? AND role='karyawan'");
        $check->execute([$username, $employeeId]);
        if ((int) $check->fetchColumn() > 0) {
            flash('Username sudah dipakai karyawan lain.', 'warning');
            header('Location: ?page=employees&edit_employee_id=' . $employeeId);
            exit;
        }

        // Update data karyawan.
        db()->prepare("UPDATE users SET name=?, username=?, position=? WHERE id=? AND role='karyawan'")
            ->execute([$name, $username, $position, $employeeId]);

        log_audit((int) user()['id'], 'UPDATE_KARYAWAN', "Update karyawan {$username}");
        flash('Data karyawan berhasil diperbarui.');
        header('Location: ?page=employees');
        exit;
    }

    // Proses reset password karyawan oleh admin.
    if ($action === 'reset_employee_password') {
        need_admin();
        $employeeId = (int) post('employee_id');
        $newPassword = post('new_password');

        // Pastikan target reset adalah karyawan yang valid.
        if (!employee_exists($employeeId)) {
            flash('Karyawan tidak ditemukan.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Password baru wajib diisi.
        if ($newPassword === '') {
            flash('Password baru wajib diisi.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Simpan hash password baru.
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $employeeId]);

        $employee = find_user_by_id($employeeId);
        log_audit((int) user()['id'], 'RESET_PASSWORD_KARYAWAN', 'Reset password ' . ($employee['username'] ?? (string) $employeeId));
        flash('Password karyawan berhasil direset.');
        header('Location: ?page=employees');
        exit;
    }

    // Proses hapus data karyawan.
    if ($action === 'delete_employee') {
        need_admin();
        $employeeId = (int) post('employee_id');

        // Pastikan karyawan ada sebelum dihapus.
        if (!employee_exists($employeeId)) {
            flash('Karyawan tidak ditemukan.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        // Simpan info karyawan untuk audit, lalu hapus data user.
        $employee = find_user_by_id($employeeId);
        db()->prepare("DELETE FROM users WHERE id=? AND role='karyawan'")->execute([$employeeId]);

        log_audit((int) user()['id'], 'DELETE_KARYAWAN', 'Hapus karyawan ' . ($employee['username'] ?? (string) $employeeId));
        flash('Data karyawan berhasil dihapus.', 'warning');
        header('Location: ?page=employees');
        exit;
    }

    // Proses konfirmasi jadwal oleh karyawan.
    if ($action === 'confirm_schedule') {
        need_auth();
        $u = user();
        $id = (int) post('schedule_id');

        // Tandai jadwal sudah dikonfirmasi dan reset status dilihat admin.
        db()->prepare('UPDATE schedules SET acknowledged_at=?, acknowledged_seen_at=NULL WHERE id=? AND user_id=?')
            ->execute([date('Y-m-d H:i:s'), $id, (int) $u['id']]);
        log_audit((int) $u['id'], 'KONFIRMASI_JADWAL', "Konfirmasi jadwal {$id}");
        flash('Jadwal berhasil dikonfirmasi.');
        header('Location: ?page=my_schedule');
        exit;
    }

    // Proses hapus jadwal oleh admin.
    if ($action === 'delete_schedule') {
        need_admin();
        $id = (int) post('id');

        // Pastikan jadwal ada sebelum dihapus.
        if (!find_schedule_by_id($id)) {
            flash('Jadwal tidak ditemukan.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }

        // Hapus jadwal dari database.
        db()->prepare('DELETE FROM schedules WHERE id=?')->execute([$id]);
        log_audit((int) user()['id'], 'DELETE_JADWAL', "Hapus jadwal {$id}");
        flash('Jadwal dihapus.', 'warning');
        header('Location: ?page=schedules');
        exit;
    }

    // Proses verifikasi beberapa absensi sekaligus oleh admin.
    if ($action === 'bulk_verify_attendance') {
        need_admin();
        $status = post('status');
        $attendanceIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['attendance_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));
        $bulkNote = trim((string) ($_POST['verification_note'] ?? ''));

        if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
            flash('Status verifikasi massal tidak valid.', 'danger');
            header('Location: ?page=verify_attendance');
            exit;
        }
        if ($attendanceIds === []) {
            flash('Pilih minimal satu data absensi.', 'warning');
            header('Location: ?page=verify_attendance');
            exit;
        }
        if (strlen($bulkNote) > 255) {
            flash('Catatan verifikasi maksimal 255 karakter.', 'danger');
            header('Location: ?page=verify_attendance');
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($attendanceIds), '?'));
        $stmt = db()->prepare("SELECT a.*, s.start_time schedule_start, s.end_time schedule_end FROM attendance_logs a LEFT JOIN schedules s ON s.user_id=a.user_id AND s.work_date=a.attendance_date WHERE a.id IN ({$placeholders})");
        $stmt->execute($attendanceIds);
        $attendances = $stmt->fetchAll();
        if (count($attendances) !== count($attendanceIds)) {
            flash('Sebagian data absensi tidak ditemukan.', 'danger');
            header('Location: ?page=verify_attendance');
            exit;
        }

        $updateStmt = db()->prepare('UPDATE attendance_logs SET status=?, verification_note=?, verified_by=?, verified_at=? WHERE id=?');
        db()->beginTransaction();
        try {
            foreach ($attendances as $attendance) {
                $assessment = attendance_system_assessment($attendance);
                $note = $bulkNote !== '' ? $bulkNote : 'Validasi sistem: ' . $assessment['summary'];
                $updateStmt->execute([$status, $note, (int) user()['id'], date('Y-m-d H:i:s'), (int) $attendance['id']]);
            }
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }

        log_audit((int) user()['id'], 'VERIFIKASI_ABSENSI_MASSAL', 'Admin memilih ' . $status . ' untuk ' . count($attendanceIds) . ' absensi');
        flash(count($attendanceIds) . ' absensi berhasil diverifikasi.');
        header('Location: ?page=verify_attendance');
        exit;
    }
    // Proses verifikasi absensi oleh admin.
    if ($action === 'verify_attendance') {
        need_admin();
        $status = post('status');

        // Admin hanya boleh memilih approved, rejected, atau pending.
        if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
            flash('Status verifikasi tidak valid.', 'danger');
            header('Location: ?page=verify_attendance');
            exit;
        }

        $attendanceId = (int) post('attendance_id');
        $assessmentStmt = db()->prepare('SELECT a.*, s.start_time schedule_start, s.end_time schedule_end FROM attendance_logs a LEFT JOIN schedules s ON s.user_id=a.user_id AND s.work_date=a.attendance_date WHERE a.id=?');
        $assessmentStmt->execute([$attendanceId]);
        $attendance = $assessmentStmt->fetch();
        if (!$attendance) {
            flash('Data absensi tidak ditemukan.', 'danger');
            header('Location: ?page=verify_attendance');
            exit;
        }

        $assessment = attendance_system_assessment($attendance);
        $verificationNote = post('verification_note');
        if ($verificationNote === '') {
            $verificationNote = 'Validasi sistem: ' . $assessment['summary'];
        }

        // Update status absensi beserta catatan dan identitas admin pemroses.
        db()->prepare('UPDATE attendance_logs SET status=?, verification_note=?, verified_by=?, verified_at=? WHERE id=?')
            ->execute([$status, $verificationNote, (int) user()['id'], date('Y-m-d H:i:s'), $attendanceId]);

        log_audit((int) user()['id'], 'VERIFIKASI_ABSENSI', "Admin memilih {$status}; rekomendasi sistem {$assessment['recommendation']}");
        flash('Verifikasi absensi diperbarui.');
        header('Location: ?page=verify_attendance');
        exit;
    }

    // Proses pengajuan izin/sakit/cuti oleh karyawan.
    if ($action === 'submit_leave') {
        need_auth();
        $u = user();
        $evidencePath = null;

        // Validasi jenis pengajuan.
        if (!in_array(post('type'), ['izin', 'sakit', 'cuti'], true)) {
            flash('Jenis izin tidak valid.', 'danger');
            header('Location: ?page=leave');
            exit;
        }

        // Tanggal dan alasan wajib diisi.
        if (post('leave_date') === '' || post('reason') === '') {
            flash('Tanggal dan alasan izin wajib diisi.', 'danger');
            header('Location: ?page=leave');
            exit;
        }

        // Tanggal izin hanya boleh hari ini atau setelahnya.
        if (post('leave_date') < date('Y-m-d')) {
            flash('Tanggal izin tidak boleh lebih awal dari hari ini.', 'warning');
            header('Location: ?page=leave');
            exit;
        }

        // Jika user mengunggah bukti, lakukan validasi dan simpan file.
        if (isset($_FILES['evidence_file']) && (int) ($_FILES['evidence_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['evidence_file'];
            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

            // Pastikan upload bukti sukses.
            if ($error !== UPLOAD_ERR_OK) {
                flash('Upload bukti izin gagal.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            // Batasi ukuran file bukti.
            if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
                flash('Ukuran file bukti maksimal 10 MB.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            // Bukti izin boleh gambar atau PDF.
            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($extension, $allowedExtensions, true)) {
                flash('Format bukti hanya boleh JPG, JPEG, PNG, atau PDF.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            // Buat nama file bukti izin yang unik.
            $filename = sprintf(
                'leave-%d-%s.%s',
                (int) $u['id'],
                date('YmdHis'),
                $extension
            );
            $target = leave_evidence_directory() . '/' . $filename;

            // Pindahkan file bukti ke folder storage.
            if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
                flash('File bukti izin tidak bisa disimpan.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            $evidencePath = 'storage/leave_evidence/' . $filename;
        }

        // Simpan pengajuan izin dengan status default pending.
        db()->prepare('INSERT INTO leave_requests (user_id, leave_date, type, reason, evidence) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int) $u['id'], post('leave_date'), post('type'), post('reason'), $evidencePath]);

        log_audit((int) $u['id'], 'SUBMIT_IZIN', 'Pengajuan izin');
        flash('Permohonan izin berhasil dikirim.');
        header('Location: ?page=leave');
        exit;
    }

    // Proses persetujuan atau penolakan izin oleh admin.
    if ($action === 'process_leave') {
        need_admin();
        $status = post('status');

        // Status hanya boleh approved, rejected, atau pending.
        if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
            flash('Status izin tidak valid.', 'danger');
            header('Location: ?page=leave_approval');
            exit;
        }

        // Update status izin beserta catatan dan identitas admin.
        db()->prepare('UPDATE leave_requests SET status=?, admin_note=?, processed_by=?, processed_at=? WHERE id=?')
            ->execute([$status, post('admin_note'), (int) user()['id'], date('Y-m-d H:i:s'), (int) post('leave_id')]);

        log_audit((int) user()['id'], 'PROSES_IZIN', 'Admin proses izin');
        flash('Permohonan izin diproses.');
        header('Location: ?page=leave_approval');
        exit;
    }

    // Proses hapus data izin beserta file buktinya.
    if ($action === 'delete_leave') {
        need_admin();
        $leaveId = (int) post('leave_id');
        $leave = find_leave_request_by_id($leaveId);

        // Pastikan data izin ditemukan.
        if (!$leave) {
            flash('Data izin tidak ditemukan.', 'danger');
            header('Location: ?page=leave_approval');
            exit;
        }

        // Hapus record izin dan file bukti yang terkait.
        db()->prepare('DELETE FROM leave_requests WHERE id=?')->execute([$leaveId]);
        delete_leave_evidence((string) ($leave['evidence'] ?? ''));

        log_audit((int) user()['id'], 'DELETE_IZIN', "Hapus izin {$leaveId}");
        flash('Data izin dan file bukti berhasil dihapus.', 'warning');
        header('Location: ?page=leave_approval');
        exit;
    }

    // Proses simpan feedback dari karyawan/user.
    if ($action === 'save_feedback') {
        need_auth();
        $u = user();

        // Pesan feedback wajib diisi.
        if (post('message') === '') {
            flash('Pesan feedback wajib diisi.', 'danger');
            header('Location: ?page=feedback');
            exit;
        }

        // Simpan kategori dan isi feedback.
        db()->prepare('INSERT INTO feedbacks (user_id, category, message) VALUES (?, ?, ?)')
            ->execute([(int) $u['id'], post('category'), post('message')]);
        log_audit((int) $u['id'], 'KIRIM_FEEDBACK', 'Kirim feedback');
        flash('Terima kasih atas feedback Anda.');
        header('Location: ?page=feedback');
        exit;
    }

    // Proses update profil user yang sedang login.
    if ($action === 'update_profile') {
        need_auth();
        $u = user();
        $newPassword = post('new_password');

        // Jika password baru diisi, update data profil sekaligus password.
        if ($newPassword !== '') {
            db()->prepare('UPDATE users SET name=?, position=?, password_hash=? WHERE id=?')
                ->execute([post('name'), post('position'), password_hash($newPassword, PASSWORD_DEFAULT), (int) $u['id']]);
        } else {
            // Jika password kosong, update nama dan jabatan saja.
            db()->prepare('UPDATE users SET name=?, position=? WHERE id=?')
                ->execute([post('name'), post('position'), (int) $u['id']]);
        }

        log_audit((int) $u['id'], 'UPDATE_PROFIL', 'Update profil');
        flash('Profil berhasil diperbarui.');
        header('Location: ?page=profile');
        exit;
    }

    // Proses export laporan absensi ke CSV.
    if ($action === 'export_report') {
        need_admin();
        $from = post('from');
        $to = post('to');
        $employeeId = (int) post('employee_id', '0');

        // Jaga rentang export tetap valid meski request dibuat di luar form.
        if ($to < $from) {
            $to = $from;
        }

        // Query dasar mengambil data absensi berdasarkan rentang tanggal.
        $sql = 'SELECT u.name, u.username, a.attendance_date, a.check_in, a.break_start, a.break_end, a.check_out, a.status
                FROM attendance_logs a JOIN users u ON u.id = a.user_id
                WHERE a.attendance_date BETWEEN ? AND ?';
        $params = [$from, $to];

        // Jika admin memilih karyawan tertentu, tambahkan filter user_id.
        if ($employeeId > 0) {
            $sql .= ' AND a.user_id = ?';
            $params[] = $employeeId;
        }
        $sql .= ' ORDER BY a.attendance_date DESC';

        // Jalankan query lalu kirim hasilnya sebagai file CSV.
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $reportRows = array_map(static function (array $row): array {
            $row['status'] = status_label((string) $row['status']);
            return $row;
        }, $stmt->fetchAll());
        log_audit((int) user()['id'], 'EXPORT_LAPORAN', 'Export laporan absensi');
        export_csv('laporan-absensi.csv', ['Nama', 'Username', 'Tanggal', 'Masuk', 'Istirahat Mulai', 'Istirahat Selesai', 'Pulang', 'Status'], $reportRows);
    }

    // Proses export audit log ke CSV.
    if ($action === 'export_audit') {
        need_admin();

        $from = post('from');
        $to = post('to');
        if ($to < $from) {
            $to = $from;
        }

        // Ambil audit log berdasarkan rentang tanggal yang dipilih admin.
        $stmt = db()->prepare('SELECT COALESCE(u.username, "sistem") username, a.action, a.description, a.ip_address, a.created_at
                               FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                               WHERE DATE(a.created_at) BETWEEN ? AND ?
                               ORDER BY a.created_at DESC');
        $stmt->execute([$from, $to]);
        log_audit((int) user()['id'], 'EXPORT_AUDIT', 'Export audit log');
        export_csv('audit-log.csv', ['Username', 'Aksi', 'Deskripsi', 'IP', 'Waktu'], $stmt->fetchAll());
    }
} catch (Throwable $e) {
    // Jika ada error tak terduga, tampilkan pesan dan kembalikan user ke halaman sebelumnya.
    flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?page=dashboard'));
    exit;
}
