<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = post('action');

try {
    if ($action === 'login') {
        $stmt = db()->prepare('SELECT * FROM users WHERE username=?');
        $stmt->execute([post('username')]);
        $u = $stmt->fetch();

        if ($u && password_verify(post('password'), $u['password_hash'])) {
            $_SESSION['user_id'] = (int) $u['id'];
            log_audit((int) $u['id'], 'LOGIN', 'Masuk sistem');
            header('Location: ?page=dashboard');
            exit;
        }

        flash('Username atau password salah.', 'danger');
        header('Location: ?page=login');
        exit;
    }

    if ($action === 'logout') {
        $u = user();
        if ($u) {
            log_audit((int) $u['id'], 'LOGOUT', 'Keluar sistem');
        }
        session_destroy();
        header('Location: ?page=login');
        exit;
    }

    if ($action === 'attendance_submit') {
        need_auth();
        $u = user();
        $now = date('Y-m-d H:i:s');
        $kind = post('kind');
        $map = [
            'masuk' => 'check_in',
            'mulai_istirahat' => 'break_start',
            'selesai_istirahat' => 'break_end',
            'pulang' => 'check_out',
        ];

        if (!isset($map[$kind])) {
            flash('Jenis absensi tidak valid.', 'danger');
            header('Location: ?page=attendance');
            exit;
        }

        $row = today_attendance((int) $u['id']);
        if (!$row) {
            db()->prepare("INSERT INTO attendance_logs (user_id, attendance_date, status) VALUES (?, CURDATE(), 'pending')")
                ->execute([(int) $u['id']]);
            $row = today_attendance((int) $u['id']);
        }

        $col = $map[$kind];
        if (!empty($row[$col])) {
            flash('Absensi ini sudah pernah direkam hari ini.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }

        if ($kind === 'mulai_istirahat' && empty($row['check_in'])) {
            flash('Absensi masuk harus dilakukan sebelum mulai istirahat.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }

        if ($kind === 'selesai_istirahat' && empty($row['break_start'])) {
            flash('Mulai istirahat harus dilakukan sebelum selesai istirahat.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }

        if ($kind === 'pulang' && empty($row['check_in'])) {
            flash('Absensi masuk harus dilakukan sebelum pulang.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }

        if ($kind === 'pulang' && !empty($row['break_start']) && empty($row['break_end'])) {
            flash('Selesaikan status istirahat sebelum absensi pulang.', 'warning');
            header('Location: ?page=attendance');
            exit;
        }

        db()->prepare("UPDATE attendance_logs SET {$col}=?, status='pending', verification_note=NULL, verified_by=NULL, verified_at=NULL WHERE id=?")
            ->execute([$now, (int) $row['id']]);

        log_audit((int) $u['id'], 'ABSENSI_' . strtoupper($kind), 'Karyawan melakukan absensi');
        flash('Absensi berhasil disimpan. Menunggu verifikasi admin.');
        header('Location: ?page=attendance');
        exit;
    }

    if ($action === 'save_schedule') {
        need_admin();
        $id = (int) post('schedule_id', '0');
        $employeeId = (int) post('employee_id');
        $workDate = post('work_date');
        $startTime = post('start_time');
        $endTime = post('end_time');

        if (!employee_exists($employeeId)) {
            flash('Karyawan yang dipilih tidak valid.', 'danger');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        if ($workDate === '' || $startTime === '' || $endTime === '') {
            flash('Tanggal dan jam kerja wajib diisi.', 'danger');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        if ($startTime >= $endTime) {
            flash('Jam mulai harus lebih kecil dari jam selesai.', 'danger');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        if (schedule_exists_for_date($employeeId, $workDate, $id)) {
            flash('Jadwal untuk karyawan dan tanggal tersebut sudah ada.', 'warning');
            header('Location: ?page=schedules' . ($id > 0 ? '&edit_schedule_id=' . $id : ''));
            exit;
        }

        $data = [
            $employeeId,
            $workDate,
            $startTime,
            $endTime,
            post('location'),
            post('notes'),
            (int) user()['id'],
        ];

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

    if ($action === 'create_employee') {
        need_admin();
        $name = post('name');
        $username = post('username');
        $position = post('position');
        $password = post('password');

        if ($name === '' || $username === '' || $password === '') {
            flash('Nama, username, dan password wajib diisi.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        $check = db()->prepare('SELECT COUNT(*) FROM users WHERE username=?');
        $check->execute([$username]);
        if ((int) $check->fetchColumn() > 0) {
            flash('Username sudah dipakai.', 'warning');
            header('Location: ?page=employees');
            exit;
        }

        db()->prepare("INSERT INTO users (name, username, password_hash, role, position) VALUES (?, ?, ?, 'karyawan', ?)")
            ->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $position]);

        log_audit((int) user()['id'], 'CREATE_KARYAWAN', "Tambah karyawan {$username}");
        flash('Data karyawan berhasil ditambahkan.');
        header('Location: ?page=employees');
        exit;
    }

    if ($action === 'update_employee') {
        need_admin();
        $employeeId = (int) post('employee_id');
        $name = post('name');
        $username = post('username');
        $position = post('position');

        if (!employee_exists($employeeId)) {
            flash('Karyawan tidak ditemukan.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        if ($name === '' || $username === '') {
            flash('Nama dan username wajib diisi.', 'danger');
            header('Location: ?page=employees&edit_employee_id=' . $employeeId);
            exit;
        }

        $check = db()->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>? AND role='karyawan'");
        $check->execute([$username, $employeeId]);
        if ((int) $check->fetchColumn() > 0) {
            flash('Username sudah dipakai karyawan lain.', 'warning');
            header('Location: ?page=employees&edit_employee_id=' . $employeeId);
            exit;
        }

        db()->prepare("UPDATE users SET name=?, username=?, position=? WHERE id=? AND role='karyawan'")
            ->execute([$name, $username, $position, $employeeId]);

        log_audit((int) user()['id'], 'UPDATE_KARYAWAN', "Update karyawan {$username}");
        flash('Data karyawan berhasil diperbarui.');
        header('Location: ?page=employees');
        exit;
    }

    if ($action === 'reset_employee_password') {
        need_admin();
        $employeeId = (int) post('employee_id');
        $newPassword = post('new_password');

        if (!employee_exists($employeeId)) {
            flash('Karyawan tidak ditemukan.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        if ($newPassword === '') {
            flash('Password baru wajib diisi.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $employeeId]);

        $employee = find_user_by_id($employeeId);
        log_audit((int) user()['id'], 'RESET_PASSWORD_KARYAWAN', 'Reset password ' . ($employee['username'] ?? (string) $employeeId));
        flash('Password karyawan berhasil direset.');
        header('Location: ?page=employees');
        exit;
    }

    if ($action === 'delete_employee') {
        need_admin();
        $employeeId = (int) post('employee_id');

        if (!employee_exists($employeeId)) {
            flash('Karyawan tidak ditemukan.', 'danger');
            header('Location: ?page=employees');
            exit;
        }

        $employee = find_user_by_id($employeeId);
        db()->prepare("DELETE FROM users WHERE id=? AND role='karyawan'")->execute([$employeeId]);

        log_audit((int) user()['id'], 'DELETE_KARYAWAN', 'Hapus karyawan ' . ($employee['username'] ?? (string) $employeeId));
        flash('Data karyawan berhasil dihapus.', 'warning');
        header('Location: ?page=employees');
        exit;
    }

    if ($action === 'confirm_schedule') {
        need_auth();
        $u = user();
        $id = (int) post('schedule_id');
        db()->prepare('UPDATE schedules SET acknowledged_at=? WHERE id=? AND user_id=?')
            ->execute([date('Y-m-d H:i:s'), $id, (int) $u['id']]);
        log_audit((int) $u['id'], 'KONFIRMASI_JADWAL', "Konfirmasi jadwal {$id}");
        flash('Jadwal berhasil dikonfirmasi.');
        header('Location: ?page=my_schedule');
        exit;
    }

    if ($action === 'delete_schedule') {
        need_admin();
        $id = (int) post('id');
        if (!find_schedule_by_id($id)) {
            flash('Jadwal tidak ditemukan.', 'danger');
            header('Location: ?page=schedules');
            exit;
        }
        db()->prepare('DELETE FROM schedules WHERE id=?')->execute([$id]);
        log_audit((int) user()['id'], 'DELETE_JADWAL', "Hapus jadwal {$id}");
        flash('Jadwal dihapus.', 'warning');
        header('Location: ?page=schedules');
        exit;
    }

    if ($action === 'verify_attendance') {
        need_admin();
        $status = post('status');
        if (!in_array($status, ['approved', 'rejected'], true)) {
            flash('Status verifikasi tidak valid.', 'danger');
            header('Location: ?page=verify_attendance');
            exit;
        }

        db()->prepare('UPDATE attendance_logs SET status=?, verification_note=?, verified_by=?, verified_at=? WHERE id=?')
            ->execute([$status, post('verification_note'), (int) user()['id'], date('Y-m-d H:i:s'), (int) post('attendance_id')]);

        log_audit((int) user()['id'], 'VERIFIKASI_ABSENSI', 'Admin verifikasi absensi');
        flash('Verifikasi absensi diperbarui.');
        header('Location: ?page=verify_attendance');
        exit;
    }

    if ($action === 'submit_leave') {
        need_auth();
        $u = user();
        $evidencePath = null;

        if (!in_array(post('type'), ['izin', 'sakit', 'cuti'], true)) {
            flash('Jenis izin tidak valid.', 'danger');
            header('Location: ?page=leave');
            exit;
        }

        if (post('leave_date') === '' || post('reason') === '') {
            flash('Tanggal dan alasan izin wajib diisi.', 'danger');
            header('Location: ?page=leave');
            exit;
        }

        if (isset($_FILES['evidence_file']) && (int) ($_FILES['evidence_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['evidence_file'];
            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($error !== UPLOAD_ERR_OK) {
                flash('Upload bukti izin gagal.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
                flash('Ukuran file bukti maksimal 2 MB.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($extension, $allowedExtensions, true)) {
                flash('Format bukti hanya boleh JPG, JPEG, PNG, atau PDF.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            $filename = sprintf(
                'leave-%d-%s.%s',
                (int) $u['id'],
                date('YmdHis'),
                $extension
            );
            $target = leave_evidence_directory() . '/' . $filename;

            if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
                flash('File bukti izin tidak bisa disimpan.', 'danger');
                header('Location: ?page=leave');
                exit;
            }

            $evidencePath = 'storage/leave_evidence/' . $filename;
        }

        db()->prepare('INSERT INTO leave_requests (user_id, leave_date, type, reason, evidence) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int) $u['id'], post('leave_date'), post('type'), post('reason'), $evidencePath]);

        log_audit((int) $u['id'], 'SUBMIT_IZIN', 'Pengajuan izin');
        flash('Permohonan izin berhasil dikirim.');
        header('Location: ?page=leave');
        exit;
    }

    if ($action === 'process_leave') {
        need_admin();
        $status = post('status');
        if (!in_array($status, ['approved', 'rejected'], true)) {
            flash('Status izin tidak valid.', 'danger');
            header('Location: ?page=leave_approval');
            exit;
        }

        db()->prepare('UPDATE leave_requests SET status=?, admin_note=?, processed_by=?, processed_at=? WHERE id=?')
            ->execute([$status, post('admin_note'), (int) user()['id'], date('Y-m-d H:i:s'), (int) post('leave_id')]);

        log_audit((int) user()['id'], 'PROSES_IZIN', 'Admin proses izin');
        flash('Permohonan izin diproses.');
        header('Location: ?page=leave_approval');
        exit;
    }

    if ($action === 'delete_leave') {
        need_admin();
        $leaveId = (int) post('leave_id');
        $leave = find_leave_request_by_id($leaveId);

        if (!$leave) {
            flash('Data izin tidak ditemukan.', 'danger');
            header('Location: ?page=leave_approval');
            exit;
        }

        db()->prepare('DELETE FROM leave_requests WHERE id=?')->execute([$leaveId]);
        delete_leave_evidence((string) ($leave['evidence'] ?? ''));

        log_audit((int) user()['id'], 'DELETE_IZIN', "Hapus izin {$leaveId}");
        flash('Data izin dan file bukti berhasil dihapus.', 'warning');
        header('Location: ?page=leave_approval');
        exit;
    }

    if ($action === 'save_feedback') {
        need_auth();
        $u = user();
        if (post('message') === '') {
            flash('Pesan feedback wajib diisi.', 'danger');
            header('Location: ?page=feedback');
            exit;
        }
        db()->prepare('INSERT INTO feedbacks (user_id, category, message) VALUES (?, ?, ?)')
            ->execute([(int) $u['id'], post('category'), post('message')]);
        log_audit((int) $u['id'], 'KIRIM_FEEDBACK', 'Kirim feedback');
        flash('Terima kasih atas feedback Anda.');
        header('Location: ?page=feedback');
        exit;
    }

    if ($action === 'update_profile') {
        need_auth();
        $u = user();
        $newPassword = post('new_password');

        if ($newPassword !== '') {
            db()->prepare('UPDATE users SET name=?, position=?, password_hash=? WHERE id=?')
                ->execute([post('name'), post('position'), password_hash($newPassword, PASSWORD_DEFAULT), (int) $u['id']]);
        } else {
            db()->prepare('UPDATE users SET name=?, position=? WHERE id=?')
                ->execute([post('name'), post('position'), (int) $u['id']]);
        }

        log_audit((int) $u['id'], 'UPDATE_PROFIL', 'Update profil');
        flash('Profil berhasil diperbarui.');
        header('Location: ?page=profile');
        exit;
    }

    if ($action === 'export_report') {
        need_admin();
        $from = post('from');
        $to = post('to');
        $employeeId = (int) post('employee_id', '0');

        $sql = 'SELECT u.name, u.username, a.attendance_date, a.check_in, a.break_start, a.break_end, a.check_out, a.status
                FROM attendance_logs a JOIN users u ON u.id = a.user_id
                WHERE a.attendance_date BETWEEN ? AND ?';
        $params = [$from, $to];
        if ($employeeId > 0) {
            $sql .= ' AND a.user_id = ?';
            $params[] = $employeeId;
        }
        $sql .= ' ORDER BY a.attendance_date DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        log_audit((int) user()['id'], 'EXPORT_LAPORAN', 'Export laporan absensi');
        export_csv('laporan-absensi.csv', ['Nama', 'Username', 'Tanggal', 'Masuk', 'Istirahat Mulai', 'Istirahat Selesai', 'Pulang', 'Status'], $stmt->fetchAll());
    }

    if ($action === 'export_audit') {
        need_admin();
        $stmt = db()->prepare('SELECT COALESCE(u.username, "sistem") username, a.action, a.description, a.ip_address, a.created_at
                               FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                               WHERE DATE(a.created_at) BETWEEN ? AND ?
                               ORDER BY a.created_at DESC');
        $stmt->execute([post('from'), post('to')]);
        log_audit((int) user()['id'], 'EXPORT_AUDIT', 'Export audit log');
        export_csv('audit-log.csv', ['Username', 'Aksi', 'Deskripsi', 'IP', 'Waktu'], $stmt->fetchAll());
    }
} catch (Throwable $e) {
    flash('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?page=dashboard'));
    exit;
}
