<?php

// Memaksa PHP memakai tipe data yang lebih ketat saat fungsi dipanggil.
declare(strict_types=1);

// Mengaktifkan session agar aplikasi bisa menyimpan data login dan flash message.
session_start();

// Mengatur zona waktu default untuk semua proses tanggal/jam di aplikasi.
date_default_timezone_set('Asia/Jakarta');

// Nama aplikasi yang bisa dipakai di berbagai halaman.
const APP_NAME = 'Portal Absensi PT. Hansirus Agro Andalan';

// Membaca file .env dari root project supaya konfigurasi bisa dipakai aplikasi.
load_env(dirname(__DIR__) . '/.env');

// Mengambil nilai konfigurasi dari $_ENV, $_SERVER, atau getenv().
function env_value(string $name, ?string $default = null): ?string {
    // Prioritas pertama: nilai yang sudah ada di $_ENV.
    if (array_key_exists($name, $_ENV) && $_ENV[$name] !== '') {
        return (string) $_ENV[$name];
    }

    // Prioritas kedua: nilai yang ada di $_SERVER.
    if (array_key_exists($name, $_SERVER) && $_SERVER[$name] !== '') {
        return (string) $_SERVER[$name];
    }

    // Prioritas ketiga: nilai dari environment server melalui getenv().
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return (string) $value;
    }

    // Jika semua sumber kosong, gunakan nilai default.
    return $default;
}

// Membaca isi file .env lalu memasukkan setiap pasangan KEY=VALUE ke environment PHP.
function load_env(string $path): void {
    // Penanda agar file .env hanya dibaca satu kali dalam satu request.
    static $loaded = false;

    // Jika sudah pernah dimuat atau file tidak ada, hentikan fungsi.
    if ($loaded || !is_file($path)) {
        return;
    }

    // Membaca file per baris, sekaligus melewati baris kosong.
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    // Memproses setiap baris konfigurasi di file .env.
    foreach ($lines as $line) {
        $line = trim($line);

        // Lewati baris kosong, komentar, atau baris yang tidak punya tanda "=".
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        // Pisahkan nama konfigurasi dan nilainya.
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Lewati konfigurasi yang nama key-nya kosong.
        if ($name === '') {
            continue;
        }

        // Menghapus UTF-8 BOM kalau karakter itu ikut terbaca di key pertama.
        if (str_starts_with($name, "\xEF\xBB\xBF")) {
            $name = substr($name, 3);
        }

        // Jika value dibungkus tanda kutip, buang tanda kutip luarnya.
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Simpan value ke beberapa tempat agar mudah diakses oleh aplikasi.
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        // putenv() membuat value juga tersedia untuk fungsi getenv().
        if (function_exists('putenv')) {
            @putenv("{$name}={$value}");
        }
    }

    // Tandai bahwa file .env sudah berhasil diproses.
    $loaded = true;
}

// Membuat dan mengembalikan koneksi database PDO.
function db(): PDO {
    // Koneksi disimpan static supaya tidak membuat koneksi baru berkali-kali.
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // DSN adalah alamat koneksi MySQL yang dibentuk dari konfigurasi .env.
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env_value('DB_HOST', '127.0.0.1'),
        env_value('DB_PORT', '3306'),
        env_value('DB_DATABASE', 'absensi_hansirus')
    );

    // Membuat koneksi PDO ke database menggunakan username dan password dari .env.
    $pdo = new PDO(
        $dsn,
        env_value('DB_USERNAME', 'root'),
        env_value('DB_PASSWORD', ''),
        [
            // Jika ada error database, PDO akan melempar exception.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // Hasil SELECT otomatis berupa array associative.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Pastikan struktur tabel yang dibutuhkan aplikasi sudah tersedia.
    ensure_schema($pdo);

    // Pastikan akun default admin dan karyawan ada.
    ensure_seed($pdo);

    return $pdo;
}

// Membuat tabel-tabel utama aplikasi jika belum ada di database.
function ensure_schema(PDO $pdo): void {
    // Daftar query pembuatan tabel untuk modul user, jadwal, absensi, izin, feedback, dan audit.
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','karyawan') NOT NULL,
            position VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            work_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            location VARCHAR(120) NULL,
            notes VARCHAR(255) NULL,
            acknowledged_at DATETIME NULL,
            acknowledged_seen_at DATETIME NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS attendance_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            check_in DATETIME NULL,
            break_start DATETIME NULL,
            break_end DATETIME NULL,
            check_out DATETIME NULL,
            evidence_photo VARCHAR(255) NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            verification_note VARCHAR(255) NULL,
            verified_by INT NULL,
            verified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_attendance (user_id, attendance_date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS leave_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            leave_date DATE NOT NULL,
            type ENUM('izin','sakit','cuti') NOT NULL,
            reason TEXT NOT NULL,
            evidence VARCHAR(255) NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            admin_note VARCHAR(255) NULL,
            processed_by INT NULL,
            processed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS feedbacks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category ENUM('kritik','saran','masalah') DEFAULT 'saran',
            message TEXT NOT NULL,
            admin_seen_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            description VARCHAR(255) NULL,
            ip_address VARCHAR(60) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    // Jalankan semua query CREATE TABLE di atas.
    foreach ($queries as $query) {
        $pdo->exec($query);
    }

    // Migrasi ringan: cek apakah kolom acknowledged_at sudah ada di tabel schedules.
    $hasAcknowledgedAt = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'schedules'
         AND COLUMN_NAME = 'acknowledged_at'"
    )->fetchColumn();

    // Jika kolom belum ada, tambahkan tanpa menghapus data lama.
    if ($hasAcknowledgedAt === 0) {
        $pdo->exec("ALTER TABLE schedules ADD COLUMN acknowledged_at DATETIME NULL AFTER notes");
    }

    // Migrasi ringan: cek kolom acknowledged_seen_at untuk status sudah dilihat admin/user.
    $hasAcknowledgedSeenAt = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'schedules'
         AND COLUMN_NAME = 'acknowledged_seen_at'"
    )->fetchColumn();

    // Jika kolom belum ada, tambahkan ke tabel schedules.
    if ($hasAcknowledgedSeenAt === 0) {
        $pdo->exec("ALTER TABLE schedules ADD COLUMN acknowledged_seen_at DATETIME NULL AFTER acknowledged_at");
    }

    // Migrasi ringan: cek kolom evidence_photo untuk foto bukti absensi.
    $hasAttendanceEvidencePhoto = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'attendance_logs'
         AND COLUMN_NAME = 'evidence_photo'"
    )->fetchColumn();

    // Jika kolom belum ada, tambahkan ke tabel attendance_logs.
    if ($hasAttendanceEvidencePhoto === 0) {
        $pdo->exec("ALTER TABLE attendance_logs ADD COLUMN evidence_photo VARCHAR(255) NULL AFTER check_out");
    }

    // Migrasi ringan: cek kolom admin_seen_at untuk menandai feedback yang sudah dilihat admin.
    $hasFeedbackAdminSeenAt = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'feedbacks'
         AND COLUMN_NAME = 'admin_seen_at'"
    )->fetchColumn();

    // Jika kolom belum ada, tambahkan ke tabel feedbacks.
    if ($hasFeedbackAdminSeenAt === 0) {
        $pdo->exec("ALTER TABLE feedbacks ADD COLUMN admin_seen_at DATETIME NULL AFTER message");
    }
}

// Mengisi akun bawaan aplikasi jika username tersebut belum ada.
function ensure_seed(PDO $pdo): void {
    // Data akun default untuk login pertama kali.
    $defaults = [
        [
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => 'admin123',
            'role' => 'admin',
            'position' => 'Admin HRD',
        ],
        [
            'name' => 'Karyawan Hansirus',
            'username' => 'karyawan',
            'password' => 'karyawan123',
            'role' => 'karyawan',
            'position' => 'BHL Lapangan',
        ],
    ];

    // Query untuk mengecek apakah username sudah ada.
    $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');

    // Query untuk menambah user baru jika belum ada.
    $insertStmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, position) VALUES (?, ?, ?, ?, ?)');

    // Proses semua data default satu per satu.
    foreach ($defaults as $row) {
        $existsStmt->execute([$row['username']]);
        $exists = (int) $existsStmt->fetchColumn() > 0;

        // Kalau user sudah ada, jangan insert ulang.
        if ($exists) {
            continue;
        }

        // Simpan user default dengan password yang sudah di-hash.
        $insertStmt->execute([
            $row['name'],
            $row['username'],
            password_hash($row['password'], PASSWORD_DEFAULT),
            $row['role'],
            $row['position'],
        ]);
    }
}

// Mengambil data user yang sedang login berdasarkan user_id di session.
function user(): ?array {
    // Jika belum ada user_id di session, berarti belum login.
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // Ambil data user dari database.
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);

    // Jika tidak ditemukan, kembalikan null.
    return $stmt->fetch() ?: null;
}

// Mengecek apakah user yang sedang login adalah admin.
function is_admin(): bool {
    $u = user();
    return $u !== null && $u['role'] === 'admin';
}

// Memaksa halaman hanya bisa diakses oleh user yang sudah login.
function need_auth(): void {
    if (!user()) {
        // Jika belum login, arahkan ke halaman login.
        header('Location: ?page=login');
        exit;
    }
}

// Memaksa halaman hanya bisa diakses oleh admin.
function need_admin(): void {
    // Pertama pastikan user sudah login.
    need_auth();

    // Jika user bukan admin, tampilkan status 403 Forbidden.
    if (!is_admin()) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

// Menyimpan atau mengambil pesan flash yang hanya tampil satu kali.
function flash(?string $message = null, string $type = 'success'): ?array {
    // Jika ada message, simpan ke session sebagai flash message.
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    // Jika tidak ada message, ambil flash yang tersimpan.
    $data = $_SESSION['flash'] ?? null;

    // Hapus flash setelah diambil agar tidak muncul berulang.
    unset($_SESSION['flash']);
    return $data;
}

// Mengamankan teks sebelum ditampilkan ke HTML agar mencegah XSS.
function h(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Mengubah status internal database menjadi label bahasa Indonesia untuk tampilan.
function status_label(string $status): string {
    return match ($status) {
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'pending' => 'Perlu Ditinjau',
        default => $status,
    };
}

// Mengambil input POST lalu merapikan spasi di awal/akhir.
function post(string $key, string $default = ''): string {
    return trim((string) ($_POST[$key] ?? $default));
}

// Mencatat aktivitas penting user ke tabel audit_logs.
function log_audit(?int $userId, string $action, string $description = ''): void {
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
}

// Mengambil data absensi user untuk tanggal hari ini.
function today_attendance(int $userId): ?array {
    $stmt = db()->prepare('SELECT * FROM attendance_logs WHERE user_id=? AND attendance_date=CURDATE()');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

// Mencari data user berdasarkan ID.
function find_user_by_id(int $userId): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

// Mengecek apakah user dengan ID tertentu ada dan berperan sebagai karyawan.
function employee_exists(int $userId): bool {
    $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='karyawan'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn() > 0;
}

// Mengecek apakah karyawan sudah punya jadwal di tanggal tertentu.
function schedule_exists_for_date(int $userId, string $workDate, int $ignoreId = 0): bool {
    // Query dasar untuk mengecek kombinasi user dan tanggal kerja.
    $sql = 'SELECT COUNT(*) FROM schedules WHERE user_id=? AND work_date=?';
    $params = [$userId, $workDate];

    // Saat edit jadwal, ID jadwal yang sedang diedit boleh diabaikan.
    if ($ignoreId > 0) {
        $sql .= ' AND id<>?';
        $params[] = $ignoreId;
    }

    // Jalankan query dan ubah hasil hitungan menjadi boolean.
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

// Mencari data jadwal berdasarkan ID.
function find_schedule_by_id(int $scheduleId): ?array {
    $stmt = db()->prepare('SELECT * FROM schedules WHERE id=?');
    $stmt->execute([$scheduleId]);
    return $stmt->fetch() ?: null;
}

// Memastikan folder tertentu sudah ada, jika belum maka dibuat.
function ensure_directory(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

// Mengembalikan path folder penyimpanan bukti izin/cuti/sakit.
function leave_evidence_directory(): string {
    $path = dirname(__DIR__) . '/storage/leave_evidence';
    ensure_directory($path);
    return $path;
}

// Mengembalikan path folder penyimpanan bukti foto absensi.
function attendance_evidence_directory(): string {
    $path = dirname(__DIR__) . '/storage/attendance_evidence';
    ensure_directory($path);
    return $path;
}

// Menghapus file bukti izin dari storage jika path-nya valid.
function delete_leave_evidence(?string $relativePath): void {
    // Jika tidak ada path, tidak ada file yang perlu dihapus.
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    // Batasi penghapusan hanya untuk folder bukti izin.
    $prefix = 'storage/leave_evidence/';
    if (!str_starts_with($relativePath, $prefix)) {
        return;
    }

    // Ambil nama file saja agar path dari luar tidak bisa memaksa hapus file lain.
    $filename = basename($relativePath);
    $fullPath = leave_evidence_directory() . '/' . $filename;

    // Hapus file jika memang ada.
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

// Menghapus file bukti absensi dari storage jika path-nya valid.
function delete_attendance_evidence(?string $relativePath): void {
    // Jika tidak ada path, tidak ada file yang perlu dihapus.
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    // Batasi penghapusan hanya untuk folder bukti absensi.
    $prefix = 'storage/attendance_evidence/';
    if (!str_starts_with($relativePath, $prefix)) {
        return;
    }

    // Ambil nama file saja agar path dari luar tidak bisa memaksa hapus file lain.
    $filename = basename($relativePath);
    $fullPath = attendance_evidence_directory() . '/' . $filename;

    // Hapus file jika memang ada.
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

// Mencari pengajuan izin/cuti/sakit berdasarkan ID.
function find_leave_request_by_id(int $leaveId): ?array {
    $stmt = db()->prepare('SELECT * FROM leave_requests WHERE id=?');
    $stmt->execute([$leaveId]);
    return $stmt->fetch() ?: null;
}

// Menilai kesesuaian absensi dengan jadwal sebagai rekomendasi untuk admin.
function attendance_system_assessment(array $attendance): array {
    $issues = [];
    $attendanceDate = (string) ($attendance['attendance_date'] ?? '');
    $checkIn = (string) ($attendance['check_in'] ?? '');
    $checkOut = (string) ($attendance['check_out'] ?? '');
    $scheduleStart = (string) ($attendance['schedule_start'] ?? '');
    $scheduleEnd = (string) ($attendance['schedule_end'] ?? '');
    $isShiftOngoing = $checkOut === ''
        && $scheduleEnd !== ''
        && date('Y-m-d H:i:s') <= $attendanceDate . ' ' . $scheduleEnd;
    $earliestCheckOut = $scheduleEnd !== ''
        ? date('Y-m-d H:i:s', strtotime($attendanceDate . ' ' . $scheduleEnd . ' -5 minutes'))
        : '';

    if ($scheduleStart === '' || $scheduleEnd === '') {
        $issues[] = 'Tidak ada jadwal kerja';
    }
    if ($checkIn === '') {
        $issues[] = 'Waktu masuk belum ada';
    }
    if ($checkOut === '' && !$isShiftOngoing) {
        $issues[] = 'Waktu pulang belum ada';
    }
    if (empty($attendance['evidence_photo'])) {
        $issues[] = 'Foto bukti belum ada';
    }
    if ($checkIn !== '' && substr($checkIn, 0, 10) !== $attendanceDate) {
        $issues[] = 'Tanggal masuk tidak sesuai';
    }
    if ($checkOut !== '' && substr($checkOut, 0, 10) !== $attendanceDate) {
        $issues[] = 'Tanggal pulang tidak sesuai';
    }
    if ($checkIn !== '' && $checkOut !== '' && $checkOut <= $checkIn) {
        $issues[] = 'Waktu pulang tidak valid';
    }
    if ($checkIn !== '' && $scheduleStart !== '' && substr($checkIn, 11, 8) > $scheduleStart) {
        $issues[] = 'Masuk terlambat';
    }
    if ($checkOut !== '' && $earliestCheckOut !== '' && $checkOut < $earliestCheckOut) {
        $issues[] = 'Pulang lebih awal';
    }

    $isMatch = $issues === [] && !$isShiftOngoing;
    return [
        'is_match' => $isMatch,
        'is_ongoing' => $isShiftOngoing && $issues === [],
        'label' => $isShiftOngoing && $issues === [] ? 'Sedang Berlangsung' : ($isMatch ? 'Sesuai' : 'Perlu Ditinjau'),
        'recommendation' => $isMatch ? 'approved' : 'pending',
        'summary' => $isShiftOngoing && $issues === [] ? 'Shift masih berlangsung; waktu pulang belum diperlukan' : ($isMatch ? 'Data lengkap dan sesuai jadwal' : implode('; ', $issues)),
    ];
}
// Mengirim data array sebagai file CSV yang langsung diunduh browser.
function export_csv(string $filename, array $headers, array $rows): void {
    // Header HTTP agar browser mengenali response sebagai file CSV.
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}");

    // Buka output stream PHP supaya CSV langsung dikirim ke browser.
    $out = fopen('php://output', 'w');

    // Tambahkan BOM agar Excel lebih mudah membaca karakter UTF-8.
    fwrite($out, "\xEF\xBB\xBF");

    // Memberi tahu Excel bahwa pemisah kolom adalah titik koma.
    fwrite($out, "sep=;\r\n");

    // Tulis header kolom CSV.
    fputcsv($out, $headers, ';', '"', '');

    // Tulis semua baris data CSV.
    foreach ($rows as $row) {
        fputcsv($out, $row, ';', '"', '');
    }

    // Tutup output dan hentikan script setelah file dikirim.
    fclose($out);
    exit;
}
