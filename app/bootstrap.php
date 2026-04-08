<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('Asia/Jakarta');

const APP_NAME = 'Portal Absensi PT. Hansirus Agro Andalan';

load_env(dirname(__DIR__) . '/.env');

function load_env(string $path): void {
    static $loaded = false;
    if ($loaded || !is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    $loaded = true;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_DATABASE') ?: 'absensi_hansirus'
    );

    $pdo = new PDO(
        $dsn,
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    ensure_schema($pdo);
    ensure_seed($pdo);

    return $pdo;
}

function ensure_schema(PDO $pdo): void {
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

    foreach ($queries as $query) {
        $pdo->exec($query);
    }

    // Backward compatible migration for existing table.
    $hasAcknowledgedAt = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'schedules'
         AND COLUMN_NAME = 'acknowledged_at'"
    )->fetchColumn();
    if ($hasAcknowledgedAt === 0) {
        $pdo->exec("ALTER TABLE schedules ADD COLUMN acknowledged_at DATETIME NULL AFTER notes");
    }
}

function ensure_seed(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, position) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['Administrator', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'Admin HRD']);
    $stmt->execute(['Karyawan Hansirus', 'karyawan', password_hash('karyawan123', PASSWORD_DEFAULT), 'karyawan', 'BHL Lapangan']);
}

function user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function is_admin(): bool {
    $u = user();
    return $u !== null && $u['role'] === 'admin';
}

function need_auth(): void {
    if (!user()) {
        header('Location: ?page=login');
        exit;
    }
}

function need_admin(): void {
    need_auth();
    if (!is_admin()) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function flash(?string $message = null, string $type = 'success'): ?array {
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $data = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $data;
}

function h(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function post(string $key, string $default = ''): string {
    return trim((string) ($_POST[$key] ?? $default));
}

function log_audit(?int $userId, string $action, string $description = ''): void {
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function today_attendance(int $userId): ?array {
    $stmt = db()->prepare('SELECT * FROM attendance_logs WHERE user_id=? AND attendance_date=CURDATE()');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function find_user_by_id(int $userId): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function employee_exists(int $userId): bool {
    $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='karyawan'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn() > 0;
}

function schedule_exists_for_date(int $userId, string $workDate, int $ignoreId = 0): bool {
    $sql = 'SELECT COUNT(*) FROM schedules WHERE user_id=? AND work_date=?';
    $params = [$userId, $workDate];
    if ($ignoreId > 0) {
        $sql .= ' AND id<>?';
        $params[] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

function find_schedule_by_id(int $scheduleId): ?array {
    $stmt = db()->prepare('SELECT * FROM schedules WHERE id=?');
    $stmt->execute([$scheduleId]);
    return $stmt->fetch() ?: null;
}

function ensure_directory(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function leave_evidence_directory(): string {
    $path = dirname(__DIR__) . '/storage/leave_evidence';
    ensure_directory($path);
    return $path;
}

function delete_leave_evidence(?string $relativePath): void {
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $prefix = 'storage/leave_evidence/';
    if (!str_starts_with($relativePath, $prefix)) {
        return;
    }

    $filename = basename($relativePath);
    $fullPath = leave_evidence_directory() . '/' . $filename;
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function find_leave_request_by_id(int $leaveId): ?array {
    $stmt = db()->prepare('SELECT * FROM leave_requests WHERE id=?');
    $stmt->execute([$leaveId]);
    return $stmt->fetch() ?: null;
}

function export_csv(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}");
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
