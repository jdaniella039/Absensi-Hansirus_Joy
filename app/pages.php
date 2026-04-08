<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

function badge_class(string $status): string {
    return match ($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning text-dark',
        default => 'secondary',
    };
}

function render_evidence_preview(?string $path): void {
    if ($path === null || $path === '') {
        echo '-';
        return;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        ?>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= h($path) ?>" alt="Bukti izin" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;">
            </a>
            <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">Lihat</a>
        </div>
        <?php
        return;
    }

    if ($extension === 'pdf') {
        ?>
        <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">Buka PDF</a>
        <?php
        return;
    }

    ?>
    <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer">Lihat File</a>
    <?php
}

function render_attendance_photo_preview(?string $path): void {
    if ($path === null || $path === '') {
        echo '-';
        return;
    }

    ?>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer">
            <img src="<?= h($path) ?>" alt="Bukti absensi" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;">
        </a>
        <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">Lihat</a>
    </div>
    <?php
}

function page_login(?array $u, ?array $flash): void {
    render_header('Login', $u, $flash);
    ?>
    <div class="login-wrap">
        <div class="row justify-content-center align-items-center w-100 g-4">
            <div class="col-lg-5">
                <div class="card hero-panel">
                    <div class="card-body login-hero p-4 p-lg-5 d-flex align-items-center justify-content-center text-center" style="min-height: 420px;">
                        <div class="w-100">
                            <img
                                src="storage/site-assets/hansirus-sawit.jpg"
                                alt="Perkebunan kelapa sawit Hansirus"
                                class="img-fluid login-hero-image mb-4"
                                style="max-height: 190px; width: 100%; object-fit: cover; border-radius: 20px; box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);"
                            >
                            <h2 class="login-hero-title mb-2">Sistem Absensi Karyawan</h2>
                            <div class="login-hero-subtitle">PT Hansirus Agro Andalan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm"><div class="card-body p-4">
                    <h4 class="text-center mb-1">Masuk ke Sistem</h4>
                    <p class="small text-muted text-center">Gunakan akun admin atau karyawan untuk demo.</p>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-2"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <button class="btn btn-primary w-100">Masuk</button>
                    </form>
                    <hr>
                    <p class="small mb-1">Akun awal:</p>
                    <p class="small mb-0">Admin: <code>admin / admin123</code></p>
                    <p class="small mb-0">Karyawan: <code>karyawan / karyawan123</code></p>
                </div></div>
            </div>
        </div>
    </div>
    <?php
    render_footer();
}

function page_dashboard(array $u, ?array $flash): void {
    render_header('Dashboard', $u, $flash);

    if ($u['role'] === 'admin') {
        $stat = db()->query("SELECT SUM(status='pending') pending, SUM(status='approved') approved, SUM(status='rejected') rejected FROM attendance_logs WHERE attendance_date=CURDATE()")->fetch();
        $employeeCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role='karyawan'")->fetchColumn();
        $pendingLeave = (int) db()->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'")->fetchColumn();
        $feedbackCount = (int) db()->query("SELECT COUNT(*) FROM feedbacks")->fetchColumn();
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><a href="?page=employees" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Master Data</small><div class="section-title mt-1">Data Karyawan</div></div></a></div>
            <div class="col-md-3"><a href="?page=schedules" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Operasional</small><div class="section-title mt-1">Kelola Jadwal</div></div></a></div>
            <div class="col-md-3"><a href="?page=verify_attendance" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Validasi</small><div class="section-title mt-1">Verifikasi Absensi</div></div></a></div>
            <div class="col-md-3"><a href="?page=leave_approval" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Validasi</small><div class="section-title mt-1">Persetujuan Izin</div></div></a></div>
            <div class="col-md-3"><a href="?page=reports" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Analitik</small><div class="section-title mt-1">Laporan</div></div></a></div>
            <div class="col-md-3"><a href="?page=feedback_inbox" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Masukan</small><div class="section-title mt-1">Feedback Masuk</div></div></a></div>
            <div class="col-md-3"><a href="?page=audit" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Keamanan</small><div class="section-title mt-1">Audit Log</div></div></a></div>
        </div>
        <div class="row g-3">
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Total Karyawan</small><h4><?= $employeeCount ?></h4></div></div></div>
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Absensi Pending</small><h4><?= (int) ($stat['pending'] ?? 0) ?></h4></div></div></div>
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Izin Pending</small><h4><?= $pendingLeave ?></h4></div></div></div>
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Feedback Masuk</small><h4><?= $feedbackCount ?></h4></div></div></div>
        </div>
        <div class="card mt-3"><div class="card-body">
            <h5>Ringkasan Hari Ini</h5>
            <p class="mb-1">Approved: <strong><?= (int) ($stat['approved'] ?? 0) ?></strong></p>
            <p class="mb-1">Rejected: <strong><?= (int) ($stat['rejected'] ?? 0) ?></strong></p>
            <p class="mb-0">Tanggal: <strong><?= h(date('Y-m-d')) ?></strong></p>
        </div></div>
        <?php
    } else {
        $today = today_attendance((int) $u['id']);
        $upcomingSchedule = db()->prepare('SELECT * FROM schedules WHERE user_id=? AND work_date>=CURDATE() ORDER BY work_date ASC LIMIT 1');
        $upcomingSchedule->execute([(int) $u['id']]);
        $nextSchedule = $upcomingSchedule->fetch() ?: null;
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><a href="?page=attendance" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Aktivitas</small><div class="section-title mt-1">Absensi Hari Ini</div></div></a></div>
            <div class="col-md-3"><a href="?page=my_schedule" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Aktivitas</small><div class="section-title mt-1">Jadwal Kerja</div></div></a></div>
            <div class="col-md-3"><a href="?page=leave" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Aktivitas</small><div class="section-title mt-1">Permohonan Izin</div></div></a></div>
            <div class="col-md-3"><a href="?page=feedback" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Aktivitas</small><div class="section-title mt-1">Feedback</div></div></a></div>
        </div>
        <div class="card mb-3"><div class="card-body">
            <h5>Status Absensi Hari Ini</h5>
            <p class="mb-1">Masuk: <?= h((string) ($today['check_in'] ?? '-')) ?></p>
            <p class="mb-1">Pulang: <?= h((string) ($today['check_out'] ?? '-')) ?></p>
            <p class="mb-0">Verifikasi: <strong><?= h((string) ($today['status'] ?? 'belum ada data')) ?></strong></p>
        </div></div>
        <div class="card"><div class="card-body">
            <h5>Jadwal Terdekat</h5>
            <?php if ($nextSchedule): ?>
                <p class="mb-1">Tanggal: <?= h((string) $nextSchedule['work_date']) ?></p>
                <p class="mb-1">Jam: <?= h((string) $nextSchedule['start_time']) ?> - <?= h((string) $nextSchedule['end_time']) ?></p>
                <p class="mb-0">Lokasi: <?= h((string) ($nextSchedule['location'] ?? '-')) ?></p>
            <?php else: ?>
                <p class="mb-0 text-muted">Belum ada jadwal kerja berikutnya.</p>
            <?php endif; ?>
        </div></div>
        <?php
    }

    render_footer();
}

function page_attendance(array $u, ?array $flash): void {
    render_header('Absensi', $u, $flash);
    $today = today_attendance((int) $u['id']);
    $scheduleStmt = db()->prepare('SELECT * FROM schedules WHERE user_id=? AND work_date=CURDATE() LIMIT 1');
    $scheduleStmt->execute([(int) $u['id']]);
    $schedule = $scheduleStmt->fetch() ?: null;
    ?>
    <div class="card"><div class="card-body">
        <h5>Input Absensi</h5>
        <?php if ($schedule): ?>
            <div class="alert alert-info py-2">
                Jadwal hari ini: <?= h((string) $schedule['start_time']) ?> - <?= h((string) $schedule['end_time']) ?>
                di <?= h((string) ($schedule['location'] ?? 'lokasi belum diisi')) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning py-2">Belum ada jadwal kerja untuk hari ini.</div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="attendance_submit">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Jenis</label>
                    <select name="kind" class="form-select">
                        <option value="masuk">Masuk</option>
                        <option value="mulai_istirahat">Mulai Istirahat</option>
                        <option value="selesai_istirahat">Selesai Istirahat</option>
                        <option value="pulang">Pulang</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 mobile-only">
                    <label class="form-label">Ambil Foto Langsung</label>
                    <input type="file" id="attendance_evidence_camera" name="attendance_evidence_camera" class="file-picker-input" accept="image/*,.jpg,.jpeg,.png" capture="user">
                    <label for="attendance_evidence_camera" class="file-picker-trigger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M10.5 2a.5.5 0 0 1 .471.332L11.208 3H13.5A1.5 1.5 0 0 1 15 4.5v7A1.5 1.5 0 0 1 13.5 13h-11A1.5 1.5 0 0 1 1 11.5v-7A1.5 1.5 0 0 1 2.5 3h2.292l.237-.668A.5.5 0 0 1 5.5 2zM8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6"/>
                            <path d="M8 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3"/>
                        </svg>
                        <span>Buka Kamera</span>
                    </label>
                    <div class="file-picker-name" id="attendance_evidence_camera_name">Belum ada foto dipilih.</div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Upload File</label>
                    <input type="file" id="attendance_evidence_gallery" name="attendance_evidence_gallery" class="file-picker-input" accept="image/*,.jpg,.jpeg,.png">
                    <label for="attendance_evidence_gallery" class="file-picker-trigger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4.502 1a1.5 1.5 0 0 0-1.415 1H2.5A1.5 1.5 0 0 0 1 3.5v9A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 13.5 2h-.586a1.5 1.5 0 0 0-1.415-1zM4.5 2a.5.5 0 0 1 .492.41L5.09 3h5.82l.098-.59A.5.5 0 0 1 11.5 2zm4 3a2.5 2.5 0 1 1-2.45 3h-.55l-1.5 2h7l-1.75-2.333-.85 1.133A2.5 2.5 0 0 1 8.5 5"/>
                        </svg>
                        <span>Pilih Foto</span>
                    </label>
                    <div class="file-picker-name" id="attendance_evidence_gallery_name">Belum ada foto dipilih.</div>
                </div>
                <div class="col-lg-2 col-md-4"><button class="btn btn-primary w-100">Submit</button></div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-12">
                    <div class="form-text">
                        <span class="d-none d-md-inline">Upload foto dari file/galeri. Format JPG, JPEG, atau PNG maksimal 2 MB.</span>
                        <span class="d-md-none">Pilih salah satu: ambil foto langsung dari kamera depan atau upload dari galeri. Format JPG, JPEG, atau PNG maksimal 2 MB.</span>
                    </div>
                </div>
            </div>
        </form>
        <hr>
        <p class="mb-1">Tanggal: <?= h(date('Y-m-d')) ?></p>
        <p class="mb-1">Masuk: <?= h((string) ($today['check_in'] ?? '-')) ?></p>
        <p class="mb-1">Istirahat Mulai: <?= h((string) ($today['break_start'] ?? '-')) ?></p>
        <p class="mb-1">Istirahat Selesai: <?= h((string) ($today['break_end'] ?? '-')) ?></p>
        <p class="mb-1">Pulang: <?= h((string) ($today['check_out'] ?? '-')) ?></p>
        <div class="mb-1">Foto Bukti: <?php render_attendance_photo_preview((string) ($today['evidence_photo'] ?? '')); ?></div>
        <p class="mb-0">Status: <strong><?= h((string) ($today['status'] ?? 'belum ada data')) ?></strong></p>
    </div></div>
    <script>
        (function () {
            var bindFileName = function (inputId, outputId) {
                var input = document.getElementById(inputId);
                var output = document.getElementById(outputId);
                if (!input || !output) {
                    return;
                }

                input.addEventListener('change', function () {
                    output.textContent = input.files && input.files.length > 0
                        ? input.files[0].name
                        : 'Belum ada foto dipilih.';
                });
            };

            bindFileName('attendance_evidence_camera', 'attendance_evidence_camera_name');
            bindFileName('attendance_evidence_gallery', 'attendance_evidence_gallery_name');
        })();
    </script>
    <?php
    render_footer();
}

function page_schedules(array $u, ?array $flash): void {
    db()->exec("UPDATE schedules SET acknowledged_seen_at=NOW() WHERE acknowledged_at IS NOT NULL AND acknowledged_seen_at IS NULL");
    render_header('Kelola Jadwal', $u, $flash);
    $employees = db()->query("SELECT id, name FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();
    $editingId = (int) ($_GET['edit_schedule_id'] ?? 0);
    $editingSchedule = $editingId > 0 ? find_schedule_by_id($editingId) : null;
    $rows = db()->query('SELECT s.*, u.name employee_name FROM schedules s JOIN users u ON u.id=s.user_id ORDER BY s.work_date DESC LIMIT 100')->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><?= $editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h5>
            <?php if ($editingSchedule): ?>
                <a href="?page=schedules" class="btn btn-sm btn-outline-secondary">Batal Edit</a>
            <?php endif; ?>
        </div>
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="save_schedule">
            <input type="hidden" name="schedule_id" value="<?= (int) ($editingSchedule['id'] ?? 0) ?>">
            <div class="col-md-3">
                <label class="form-label">Karyawan</label>
                <select class="form-select" name="employee_id">
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= (int) $e['id'] ?>" <?= (int) ($editingSchedule['user_id'] ?? 0) === (int) $e['id'] ? 'selected' : '' ?>><?= h((string) $e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="work_date" class="form-control" value="<?= h((string) ($editingSchedule['work_date'] ?? '')) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control" value="<?= h((string) ($editingSchedule['start_time'] ?? '')) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control" value="<?= h((string) ($editingSchedule['end_time'] ?? '')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" value="<?= h((string) ($editingSchedule['location'] ?? '')) ?>"></div>
            <div class="col-md-10"><label class="form-label">Catatan</label><input name="notes" class="form-control" value="<?= h((string) ($editingSchedule['notes'] ?? '')) ?>"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><?= $editingSchedule ? 'Update' : 'Simpan' ?></button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <h5>Daftar Jadwal</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jam</th><th>Lokasi</th><th>Konfirmasi Karyawan</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h((string) $r['work_date']) ?></td>
                        <td><?= h((string) $r['employee_name']) ?></td>
                        <td><?= h((string) $r['start_time']) ?> - <?= h((string) $r['end_time']) ?></td>
                        <td><?= h((string) ($r['location'] ?? '-')) ?></td>
                        <td>
                            <?php if (!empty($r['acknowledged_at'])): ?>
                                <span class="text-success"><?= h((string) $r['acknowledged_at']) ?></span>
                            <?php else: ?>
                                <span class="text-danger">Belum</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?page=schedules&edit_schedule_id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" onsubmit="return confirm('Hapus jadwal ini?')">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
    <?php
    render_footer();
}

function page_my_schedule(array $u, ?array $flash): void {
    render_header('Jadwal Kerja Saya', $u, $flash);
    $stmt = db()->prepare('SELECT * FROM schedules WHERE user_id=? ORDER BY work_date DESC');
    $stmt->execute([(int) $u['id']]);
    $rows = $stmt->fetchAll();
    ?>
    <div class="card"><div class="card-body">
        <h5>Jadwal Kerja</h5>
        <div class="table-responsive"><table class="table table-striped table-sm align-middle"><thead><tr><th>Tanggal</th><th>Mulai</th><th>Selesai</th><th>Lokasi</th><th>Catatan</th><th>Status Konfirmasi</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= h((string) $r['work_date']) ?></td>
                <td><?= h((string) $r['start_time']) ?></td>
                <td><?= h((string) $r['end_time']) ?></td>
                <td><?= h((string) ($r['location'] ?? '-')) ?></td>
                <td><?= h((string) ($r['notes'] ?? '-')) ?></td>
                <td>
                    <?php if (!empty($r['acknowledged_at'])): ?>
                        <span class="text-success"><?= h((string) $r['acknowledged_at']) ?></span>
                    <?php else: ?>
                        <span class="text-danger">Belum</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (empty($r['acknowledged_at'])): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="confirm_schedule">
                            <input type="hidden" name="schedule_id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-sm btn-success">Konfirmasi</button>
                        </form>
                    <?php else: ?>
                        <span class="text-success small">Terkonfirmasi</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php
    render_footer();
}

function page_verify_attendance(array $u, ?array $flash): void {
    render_header('Verifikasi Absensi', $u, $flash);
    $rows = db()->query('SELECT a.*, u.name FROM attendance_logs a JOIN users u ON u.id=a.user_id ORDER BY a.attendance_date DESC LIMIT 100')->fetchAll();
    ?>
    <div class="card"><div class="card-body">
        <h5>Data Absensi</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Masuk</th><th>Pulang</th><th>Bukti Foto</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['attendance_date']) ?></td>
                    <td><?= h((string) $r['name']) ?></td>
                    <td><?= h((string) ($r['check_in'] ?? '-')) ?></td>
                    <td><?= h((string) ($r['check_out'] ?? '-')) ?></td>
                    <td><?php render_attendance_photo_preview((string) ($r['evidence_photo'] ?? '')); ?></td>
                    <td><span class="badge bg-<?= badge_class((string) $r['status']) ?>"><?= h((string) $r['status']) ?></span></td>
                    <td>
                        <form method="post" class="d-flex gap-1">
                            <input type="hidden" name="action" value="verify_attendance">
                            <input type="hidden" name="attendance_id" value="<?= (int) $r['id'] ?>">
                            <select name="status" class="form-select form-select-sm"><option value="approved">approved</option><option value="rejected">rejected</option></select>
                            <input name="verification_note" class="form-control form-control-sm" placeholder="Catatan">
                            <button class="btn btn-sm btn-primary">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
    <?php
    render_footer();
}

function page_leave(array $u, ?array $flash): void {
    render_header('Permohonan Izin', $u, $flash);
    $stmt = db()->prepare('SELECT * FROM leave_requests WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([(int) $u['id']]);
    $rows = $stmt->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <h5>Ajukan Izin</h5>
        <form method="post" enctype="multipart/form-data" class="row g-2">
            <input type="hidden" name="action" value="submit_leave">
            <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="leave_date" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Jenis</label><select name="type" class="form-select"><option value="izin">Izin</option><option value="sakit">Sakit</option><option value="cuti">Cuti</option></select></div>
            <div class="col-md-3"><label class="form-label">Bukti</label><input type="file" name="evidence_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div>
            <div class="col-md-5"><label class="form-label">Alasan</label><input name="reason" class="form-control" required></div>
            <div class="col-12"><small class="text-muted">Upload opsional. Format: JPG, JPEG, PNG, PDF. Maksimal 2 MB.</small></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Kirim</button></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <h5>Riwayat Izin</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Bukti</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['leave_date']) ?></td>
                    <td><?= h((string) $r['type']) ?></td>
                    <td><?= h((string) $r['reason']) ?></td>
                    <td><?php render_evidence_preview((string) ($r['evidence'] ?? '')); ?></td>
                    <td><?= h((string) $r['status']) ?></td>
                    <td><?= h((string) ($r['admin_note'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php
    render_footer();
}

function page_leave_approval(array $u, ?array $flash): void {
    render_header('Persetujuan Izin', $u, $flash);
    $rows = db()->query('SELECT l.*, u.name FROM leave_requests l JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 100')->fetchAll();
    ?>
    <div class="card"><div class="card-body">
        <h5>Permohonan Masuk</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Karyawan</th><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['name']) ?></td><td><?= h((string) $r['leave_date']) ?></td><td><?= h((string) $r['type']) ?></td><td><?= h((string) $r['reason']) ?></td>
                    <td><?php render_evidence_preview((string) ($r['evidence'] ?? '')); ?></td>
                    <td><?= h((string) $r['status']) ?></td>
                    <td>
                        <div class="d-flex gap-1 mb-1">
                            <form method="post" class="d-flex gap-1">
                                <input type="hidden" name="action" value="process_leave"><input type="hidden" name="leave_id" value="<?= (int) $r['id'] ?>">
                                <select name="status" class="form-select form-select-sm"><option value="approved">approved</option><option value="rejected">rejected</option></select>
                                <input name="admin_note" class="form-control form-control-sm" placeholder="Catatan"><button class="btn btn-sm btn-primary">Simpan</button>
                            </form>
                        </div>
                        <form method="post" onsubmit="return confirm('Hapus data izin ini beserta file buktinya?')">
                            <input type="hidden" name="action" value="delete_leave">
                            <input type="hidden" name="leave_id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php
    render_footer();
}

function page_feedback(array $u, ?array $flash): void {
    render_header('Feedback', $u, $flash);
    $stmt = db()->prepare('SELECT * FROM feedbacks WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([(int) $u['id']]);
    $rows = $stmt->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <h5>Kirim Feedback</h5>
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="save_feedback">
            <div class="col-md-3"><label class="form-label">Kategori</label><select name="category" class="form-select"><option value="saran">Saran</option><option value="kritik">Kritik</option><option value="masalah">Masalah</option></select></div>
            <div class="col-md-9"><label class="form-label">Pesan</label><input name="message" class="form-control" required></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Kirim</button></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <h5>Riwayat Feedback</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Waktu</th><th>Kategori</th><th>Pesan</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['created_at']) ?></td>
                    <td><?= h((string) $r['category']) ?></td>
                    <td><?= h((string) $r['message']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
    <?php
    render_footer();
}

function page_employees(array $u, ?array $flash): void {
    render_header('Data Karyawan', $u, $flash);
    $editingId = (int) ($_GET['edit_employee_id'] ?? 0);
    $editingEmployee = $editingId > 0 ? find_user_by_id($editingId) : null;
    if ($editingEmployee && $editingEmployee['role'] !== 'karyawan') {
        $editingEmployee = null;
    }
    $rows = db()->query("SELECT id, name, username, position, created_at FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><?= $editingEmployee ? 'Edit Karyawan' : 'Tambah Karyawan' ?></h5>
            <?php if ($editingEmployee): ?>
                <a href="?page=employees" class="btn btn-sm btn-outline-secondary">Batal Edit</a>
            <?php endif; ?>
        </div>
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="<?= $editingEmployee ? 'update_employee' : 'create_employee' ?>">
            <?php if ($editingEmployee): ?>
                <input type="hidden" name="employee_id" value="<?= (int) $editingEmployee['id'] ?>">
            <?php endif; ?>
            <div class="col-md-3"><label class="form-label">Nama</label><input name="name" class="form-control" value="<?= h((string) ($editingEmployee['name'] ?? '')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= h((string) ($editingEmployee['username'] ?? '')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Jabatan</label><input name="position" class="form-control" value="<?= h((string) ($editingEmployee['position'] ?? '')) ?>"></div>
            <?php if (!$editingEmployee): ?>
                <div class="col-md-3"><label class="form-label">Password Awal</label><input type="password" name="password" class="form-control" required></div>
            <?php else: ?>
                <div class="col-md-3 d-flex align-items-end"><div class="small text-muted">Password tetap sama kecuali direset dari tabel bawah.</div></div>
            <?php endif; ?>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><?= $editingEmployee ? 'Update' : 'Tambah' ?></button></div>
        </form>
    </div></div>
    <?php if (!$editingEmployee): ?>
        <div class="card mb-3"><div class="card-body">
            <h5 class="mb-1">Import Karyawan dari CSV</h5>
            <p class="text-muted mb-2">Upload file CSV untuk menambahkan banyak karyawan sekaligus.</p>
            <div class="mb-3">
                <a href="?page=employees&download=employee_template_csv" class="btn btn-outline-secondary">Download Template CSV</a>
            </div>
            <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="import_employees_csv">
                <div class="col-md-8">
                    <label class="form-label">File CSV Karyawan</label>
                    <input type="file" name="employees_csv" class="form-control" accept=".csv,text/csv" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100">Import CSV</button>
                </div>
            </form>
            <div class="small text-muted mt-2">Gunakan file template agar format kolom sesuai.</div>
        </div></div>
    <?php endif; ?>
    <div class="card"><div class="card-body">
        <h5>Daftar Karyawan</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Nama</th><th>Username</th><th>Jabatan</th><th>Dibuat</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['name']) ?></td>
                    <td><?= h((string) $r['username']) ?></td>
                    <td><?= h((string) ($r['position'] ?? '-')) ?></td>
                    <td><?= h((string) $r['created_at']) ?></td>
                    <td>
                        <div class="d-flex gap-1 mb-1">
                            <a href="?page=employees&edit_employee_id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus karyawan ini beserta data terkait?')">
                                <input type="hidden" name="action" value="delete_employee">
                                <input type="hidden" name="employee_id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </div>
                        <form method="post" class="d-flex gap-1">
                            <input type="hidden" name="action" value="reset_employee_password">
                            <input type="hidden" name="employee_id" value="<?= (int) $r['id'] ?>">
                            <input type="password" name="new_password" class="form-control form-control-sm" placeholder="Password baru" required>
                            <button class="btn btn-sm btn-outline-warning">Reset</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
    <?php
    render_footer();
}

function page_profile(array $u, ?array $flash): void {
    render_header('Profil', $u, $flash);
    ?>
    <div class="card"><div class="card-body">
        <h5>Profil Saya</h5>
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="update_profile">
            <div class="col-md-6"><label class="form-label">Nama</label><input name="name" class="form-control" value="<?= h((string) $u['name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Jabatan</label><input name="position" class="form-control" value="<?= h((string) ($u['position'] ?? '')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Password Baru (opsional)</label><input type="password" name="new_password" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Simpan</button></div>
        </form>
    </div></div>
    <?php
    render_footer();
}

function page_reports(array $u, ?array $flash): void {
    render_header('Laporan Absensi', $u, $flash);
    $period = $_GET['period'] ?? 'bulan';
    if (!isset($_GET['from']) || !isset($_GET['to'])) {
        if ($period === 'hari') {
            $from = date('Y-m-d');
            $to = date('Y-m-d');
        } elseif ($period === 'minggu') {
            $from = date('Y-m-d', strtotime('monday this week'));
            $to = date('Y-m-d', strtotime('sunday this week'));
        } else {
            $from = date('Y-m-01');
            $to = date('Y-m-d');
        }
    } else {
        $from = $_GET['from'];
        $to = $_GET['to'];
    }
    $employeeId = (int) ($_GET['employee_id'] ?? 0);

    $employees = db()->query("SELECT id, name FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();

    $sql = 'SELECT u.name, a.attendance_date, a.check_in, a.check_out, a.status FROM attendance_logs a JOIN users u ON u.id=a.user_id WHERE a.attendance_date BETWEEN ? AND ?';
    $params = [$from, $to];
    if ($employeeId > 0) {
        $sql .= ' AND a.user_id=?';
        $params[] = $employeeId;
    }
    $sql .= ' ORDER BY a.attendance_date DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $attendanceCountSql = "SELECT COUNT(*) FROM attendance_logs a WHERE a.status='approved' AND a.attendance_date BETWEEN ? AND ?";
    $attendanceCountParams = [$from, $to];
    if ($employeeId > 0) {
        $attendanceCountSql .= ' AND a.user_id=?';
        $attendanceCountParams[] = $employeeId;
    }
    $attendanceStmt = db()->prepare($attendanceCountSql);
    $attendanceStmt->execute($attendanceCountParams);
    $hadirTotal = (int) $attendanceStmt->fetchColumn();

    $lateSql = "SELECT COUNT(*) FROM attendance_logs a JOIN schedules s ON s.user_id=a.user_id AND s.work_date=a.attendance_date WHERE a.status='approved' AND TIME(a.check_in) > s.start_time AND a.attendance_date BETWEEN ? AND ?";
    $lateParams = [$from, $to];
    if ($employeeId > 0) {
        $lateSql .= ' AND a.user_id=?';
        $lateParams[] = $employeeId;
    }
    $lateStmt = db()->prepare($lateSql);
    $lateStmt->execute($lateParams);
    $terlambat = (int) $lateStmt->fetchColumn();

    $leaveSql = "SELECT COUNT(*) FROM leave_requests WHERE status='approved' AND leave_date BETWEEN ? AND ?";
    $leaveParams = [$from, $to];
    if ($employeeId > 0) {
        $leaveSql .= ' AND user_id=?';
        $leaveParams[] = $employeeId;
    }
    $leaveStmt = db()->prepare($leaveSql);
    $leaveStmt->execute($leaveParams);
    $izin = (int) $leaveStmt->fetchColumn();

    $scheduleSql = 'SELECT COUNT(*) FROM schedules WHERE work_date BETWEEN ? AND ?';
    $scheduleParams = [$from, $to];
    if ($employeeId > 0) {
        $scheduleSql .= ' AND user_id=?';
        $scheduleParams[] = $employeeId;
    }
    $scheduleStmt = db()->prepare($scheduleSql);
    $scheduleStmt->execute($scheduleParams);
    $totalJadwal = (int) $scheduleStmt->fetchColumn();

    $hadir = max($hadirTotal - $terlambat, 0);
    $absen = max($totalJadwal - $hadirTotal - $izin, 0);
    ?>
    <div class="card mb-3"><div class="card-body">
        <h5>Filter</h5>
        <form method="get" class="row g-2">
            <input type="hidden" name="page" value="reports">
            <div class="col-md-2"><label class="form-label">Periode</label><select class="form-select" name="period"><option value="hari" <?= $period === 'hari' ? 'selected' : '' ?>>Per Hari</option><option value="minggu" <?= $period === 'minggu' ? 'selected' : '' ?>>Per Minggu</option><option value="bulan" <?= $period === 'bulan' ? 'selected' : '' ?>>Per Bulan</option></select></div>
            <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" name="from" value="<?= h($from) ?>"></div>
            <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" name="to" value="<?= h($to) ?>"></div>
            <div class="col-md-3"><label class="form-label">Karyawan</label><select name="employee_id" class="form-select"><option value="0">Semua</option><?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>" <?= $employeeId === (int) $e['id'] ? 'selected' : '' ?>><?= h((string) $e['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-secondary w-100">Terapkan</button></div>
        </form>
        <form method="post" class="mt-2">
            <input type="hidden" name="action" value="export_report">
            <input type="hidden" name="from" value="<?= h($from) ?>">
            <input type="hidden" name="to" value="<?= h($to) ?>">
            <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
            <button class="btn btn-primary">Export CSV</button>
        </form>
    </div></div>
    <div class="row g-2 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Hadir</small><h4><?= $hadir ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Izin</small><h4><?= $izin ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Terlambat</small><h4><?= $terlambat ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Absen</small><h4><?= $absen ?></h4></div></div></div>
    </div>
    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Tanggal</th><th>Karyawan</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <tr><td><?= h((string) $r['attendance_date']) ?></td><td><?= h((string) $r['name']) ?></td><td><?= h((string) ($r['check_in'] ?? '-')) ?></td><td><?= h((string) ($r['check_out'] ?? '-')) ?></td><td><?= h((string) $r['status']) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php
    render_footer();
}

function page_audit(array $u, ?array $flash): void {
    render_header('Audit Log', $u, $flash);
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');

    $stmt = db()->prepare('SELECT COALESCE(u.username, "sistem") username, a.action, a.description, a.ip_address, a.created_at FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE DATE(a.created_at) BETWEEN ? AND ? ORDER BY a.created_at DESC LIMIT 200');
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <form method="get" class="row g-2">
            <input type="hidden" name="page" value="audit">
            <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" name="from" value="<?= h($from) ?>"></div>
            <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" name="to" value="<?= h($to) ?>"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-secondary w-100">Terapkan</button></div>
        </form>
        <form method="post" class="mt-2">
            <input type="hidden" name="action" value="export_audit">
            <input type="hidden" name="from" value="<?= h($from) ?>">
            <input type="hidden" name="to" value="<?= h($to) ?>">
            <button class="btn btn-primary">Export CSV</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <tr><td><?= h((string) $r['created_at']) ?></td><td><?= h((string) $r['username']) ?></td><td><?= h((string) $r['action']) ?></td><td><?= h((string) ($r['description'] ?? '-')) ?></td><td><?= h((string) ($r['ip_address'] ?? '-')) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php
    render_footer();
}

function page_feedback_inbox(array $u, ?array $flash): void {
    db()->exec("UPDATE feedbacks SET admin_seen_at=NOW() WHERE admin_seen_at IS NULL");
    render_header('Feedback Masuk', $u, $flash);
    $rows = db()->query('SELECT f.*, u.name, u.username FROM feedbacks f JOIN users u ON u.id=f.user_id ORDER BY f.created_at DESC LIMIT 200')->fetchAll();
    ?>
    <div class="card"><div class="card-body">
        <h5>Masukan Karyawan</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Waktu</th><th>Karyawan</th><th>Username</th><th>Kategori</th><th>Pesan</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['created_at']) ?></td>
                    <td><?= h((string) $r['name']) ?></td>
                    <td><?= h((string) $r['username']) ?></td>
                    <td><?= h((string) $r['category']) ?></td>
                    <td><?= h((string) $r['message']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
    <?php
    render_footer();
}
