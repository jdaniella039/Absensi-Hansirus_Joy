<?php

// Mengaktifkan mode tipe ketat untuk file kumpulan halaman.
declare(strict_types=1);

// Memuat layout agar setiap halaman bisa memakai render_header() dan render_footer().
require_once __DIR__ . '/layout.php';

// Mengubah status database menjadi class warna Bootstrap untuk badge.
function badge_class(string $status): string {
    return match ($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning text-dark',
        default => 'secondary',
    };
}

// Menampilkan preview bukti izin, baik gambar, PDF, maupun file lain.
function render_evidence_preview(?string $path): void {
    // Jika tidak ada file bukti, tampilkan tanda kosong.
    if ($path === null || $path === '') {
        echo '-';
        return;
    }

    // Ambil ekstensi untuk menentukan cara menampilkan file.
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    // File gambar ditampilkan sebagai thumbnail dan tombol lihat.
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

    // File PDF ditampilkan sebagai tombol buka PDF.
    if ($extension === 'pdf') {
        ?>
        <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">Buka PDF</a>
        <?php
        return;
    }

    ?>
    <!-- Fallback untuk jenis file lain yang tetap ingin dibuka di tab baru. -->
    <a href="<?= h($path) ?>" target="_blank" rel="noopener noreferrer">Lihat File</a>
    <?php
}

// Menampilkan preview foto bukti absensi.
function render_attendance_photo_preview(?string $path): void {
    // Jika belum ada foto, tampilkan tanda kosong.
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

// Halaman login untuk user yang belum masuk sistem.
function page_login(?array $u, ?array $flash): void {
    render_header('Login', $u, $flash);
    ?>
    <div class="login-wrap">
        <div class="row justify-content-center align-items-center w-100 g-4">
            <!-- Panel kiri berisi identitas aplikasi dan gambar sawit. -->
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
            <!-- Panel kanan berisi form login. -->
            <div class="col-lg-4">
                <div class="card shadow-sm"><div class="card-body p-4">
                    <h4 class="text-center mb-1">Masuk ke Sistem</h4>
                    <p class="small text-muted text-center">Masukkan username dan password untuk masuk ke portal absensi.</p>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-2"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
                        <div class="mb-3">
                            <label class="form-label" for="login-password">Password</label>
                            <div class="password-field">
                                <input id="login-password" type="password" name="password" class="form-control" autocomplete="current-password" required>
                                <button type="button" class="password-toggle" data-password-toggle="login-password" aria-label="Lihat password" title="Lihat password">
                                    <svg class="password-icon password-icon-eye" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="password-icon password-icon-off" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 3l18 18"></path>
                                        <path d="M10.6 10.6A2 2 0 0 0 13.4 13.4"></path>
                                        <path d="M7.1 7.1C3.8 8.9 2 12 2 12s3.5 6.5 10 6.5c1.6 0 3-.4 4.2-.9"></path>
                                        <path d="M12 5.5c6.5 0 10 6.5 10 6.5a16 16 0 0 1-2.7 3.5"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100">Masuk</button>
                    </form>
                </div></div>
            </div>
        </div>
    </div>
    <?php
    render_footer();
}

// Halaman dashboard; isi tampilannya berbeda untuk admin dan karyawan.
function page_dashboard(array $u, ?array $flash): void {
    render_header('Dashboard', $u, $flash);

    // Dashboard admin berisi shortcut fitur dan statistik seluruh karyawan.
    if ($u['role'] === 'admin') {
        // Hitung ringkasan status absensi hari ini.
        $stat = db()->query("SELECT SUM(status='pending') pending, SUM(status='approved') approved, SUM(status='rejected') rejected FROM attendance_logs WHERE attendance_date=CURDATE()")->fetch();

        // Hitung total data yang ditampilkan di kartu statistik.
        $employeeCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role='karyawan'")->fetchColumn();
        $pendingLeave = (int) db()->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'")->fetchColumn();
        $feedbackCount = (int) db()->query("SELECT COUNT(*) FROM feedbacks")->fetchColumn();
        ?>
        <!-- Shortcut cepat menuju modul-modul admin. -->
        <div class="row g-3 mb-4">
            <div class="col-md-3"><a href="?page=employees" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Master Data</small><div class="section-title mt-1">Data Karyawan</div></div></a></div>
            <div class="col-md-3"><a href="?page=schedules" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Operasional</small><div class="section-title mt-1">Kelola Jadwal</div></div></a></div>
            <div class="col-md-3"><a href="?page=verify_attendance" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Validasi</small><div class="section-title mt-1">Verifikasi Absensi</div></div></a></div>
            <div class="col-md-3"><a href="?page=leave_approval" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Validasi</small><div class="section-title mt-1">Persetujuan Izin</div></div></a></div>
            <div class="col-md-3"><a href="?page=reports" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Analitik</small><div class="section-title mt-1">Laporan</div></div></a></div>
            <div class="col-md-3"><a href="?page=feedback_inbox" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Masukan</small><div class="section-title mt-1">Feedback Masuk</div></div></a></div>
            <div class="col-md-3"><a href="?page=audit" class="card text-decoration-none h-100"><div class="card-body"><small class="text-muted">Keamanan</small><div class="section-title mt-1">Audit Log</div></div></a></div>
        </div>
        <!-- Statistik angka penting untuk admin. -->
        <div class="row g-3">
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Total Karyawan</small><h4><?= $employeeCount ?></h4></div></div></div>
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Absensi Perlu Ditinjau</small><h4><?= (int) ($stat['pending'] ?? 0) ?></h4></div></div></div>
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Izin Perlu Ditinjau</small><h4><?= $pendingLeave ?></h4></div></div></div>
            <div class="col-md-3"><div class="card stat-tile"><div class="card-body"><small class="text-white-50">Feedback Masuk</small><h4><?= $feedbackCount ?></h4></div></div></div>
        </div>
        <div class="card mt-3"><div class="card-body">
            <h5>Ringkasan Hari Ini</h5>
            <p class="mb-1">Disetujui: <strong><?= (int) ($stat['approved'] ?? 0) ?></strong></p>
            <p class="mb-1">Ditolak: <strong><?= (int) ($stat['rejected'] ?? 0) ?></strong></p>
            <p class="mb-0">Tanggal: <strong><?= h(date('Y-m-d')) ?></strong></p>
        </div></div>
        <?php
    } else {
        // Dashboard karyawan berisi status absensi pribadi dan jadwal terdekat.
        $today = today_attendance((int) $u['id']);
        $upcomingSchedule = db()->prepare('SELECT * FROM schedules WHERE user_id=? AND work_date>=CURDATE() ORDER BY work_date ASC LIMIT 1');
        $upcomingSchedule->execute([(int) $u['id']]);
        $nextSchedule = $upcomingSchedule->fetch() ?: null;
        ?>
        <!-- Shortcut cepat fitur karyawan. -->
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
            <p class="mb-0">Verifikasi: <strong><?= h($today ? status_label((string) $today['status']) : 'belum ada data') ?></strong></p>
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

// Halaman input absensi karyawan untuk hari berjalan.
function page_attendance(array $u, ?array $flash): void {
    render_header('Absensi', $u, $flash);

    // Ambil record absensi user hari ini, jika sudah pernah dibuat.
    $today = today_attendance((int) $u['id']);

    // Ambil jadwal kerja user untuk hari ini.
    $scheduleStmt = db()->prepare('SELECT * FROM schedules WHERE user_id=? AND work_date=CURDATE() LIMIT 1');
    $scheduleStmt->execute([(int) $u['id']]);
    $schedule = $scheduleStmt->fetch() ?: null;
    ?>
    <div class="card"><div class="card-body">
        <h5>Input Absensi</h5>
        <?php if ($schedule): ?>
            <!-- Info jadwal hari ini supaya karyawan tahu jam dan lokasi kerja. -->
            <div class="alert alert-info py-2">
                Jadwal hari ini: <?= h((string) $schedule['start_time']) ?> - <?= h((string) $schedule['end_time']) ?>
                di <?= h((string) ($schedule['location'] ?? 'lokasi belum diisi')) ?>
            </div>
        <?php else: ?>
            <!-- Peringatan jika admin belum membuat jadwal hari ini. -->
            <div class="alert alert-warning py-2">Belum ada jadwal kerja untuk hari ini.</div>
        <?php endif; ?>
        <!-- Form absensi mengirim jenis absensi dan foto bukti. -->
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="attendance_submit">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Jenis</label>
                    <select name="kind" class="form-select">
                        ss

                        <option value="pulang">Pulang</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 mobile-only">
                    <label class="form-label">Ambil Foto Langsung</label>
                    <input type="file" id="attendance_evidence_camera" name="attendance_evidence_camera" class="file-picker-input" accept="image/*,.jpg,.jpeg,.png" capture="user">
                    <!-- Tombol custom untuk membuka kamera pada perangkat mobile. -->
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
                    <!-- Tombol custom untuk memilih foto dari file/galeri. -->
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
                        <span class="d-none d-md-inline">Upload foto dari file/galeri. Format JPG, JPEG, atau PNG maksimal 10 MB.</span>
                        <span class="d-md-none">Pilih salah satu: ambil foto langsung dari kamera depan atau upload dari galeri. Format JPG, JPEG, atau PNG maksimal 10 MB.</span>
                    </div>
                </div>
            </div>
        </form>
        <hr>
        <!-- Ringkasan status absensi user untuk hari ini. -->
        <p class="mb-1">Tanggal: <?= h(date('Y-m-d')) ?></p>
        <p class="mb-1">Masuk: <?= h((string) ($today['check_in'] ?? '-')) ?></p>

        <p class="mb-1">Pulang: <?= h((string) ($today['check_out'] ?? '-')) ?></p>
        <div class="mb-1">Foto Bukti: <?php render_attendance_photo_preview((string) ($today['evidence_photo'] ?? '')); ?></div>
        <p class="mb-0">Status: <strong><?= h($today ? status_label((string) $today['status']) : 'belum ada data') ?></strong></p>
    </div></div>
    <script>
        (function () {
            // Menghubungkan input file dengan teks nama file yang dipilih.
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

// Halaman admin untuk tambah, edit, hapus, dan melihat jadwal kerja.
function page_schedules(array $u, ?array $flash): void {
    // Saat admin membuka halaman ini, notifikasi jadwal yang sudah dikonfirmasi dianggap sudah dilihat.
    db()->exec("UPDATE schedules SET acknowledged_seen_at=NOW() WHERE acknowledged_at IS NOT NULL AND acknowledged_seen_at IS NULL");
    render_header('Kelola Jadwal', $u, $flash);

    // Data karyawan dipakai untuk pilihan dropdown form jadwal.
    $employees = db()->query("SELECT id, name, username FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();

    // Jika ada edit_schedule_id di URL, form berubah menjadi mode edit.
    $editingId = (int) ($_GET['edit_schedule_id'] ?? 0);
    $editingSchedule = $editingId > 0 ? find_schedule_by_id($editingId) : null;

    // Ambil daftar jadwal terbaru untuk ditampilkan di tabel.
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
                <div class="input-group mb-2">
                    <input type="search" id="schedule-employee-search" class="form-control" placeholder="Cari nama atau username" autocomplete="off">
                    <button type="button" id="schedule-employee-search-button" class="btn btn-outline-secondary">Cari</button>
                </div>
                <select class="form-select" id="schedule-employee-select" name="employee_id">
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= (int) $e['id'] ?>" data-search="<?= h(strtolower((string) $e['name'] . ' ' . (string) $e['username'])) ?>" <?= (int) ($editingSchedule['user_id'] ?? 0) === (int) $e['id'] ? 'selected' : '' ?>><?= h((string) $e['name']) ?> (<?= h((string) $e['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="work_date" class="form-control" value="<?= h((string) ($editingSchedule['work_date'] ?? '')) ?>" min="<?= h(date('Y-m-d')) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control" value="<?= h((string) ($editingSchedule['start_time'] ?? '')) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control" value="<?= h((string) ($editingSchedule['end_time'] ?? '')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" value="<?= h((string) ($editingSchedule['location'] ?? '')) ?>"></div>
            <div class="col-md-10"><label class="form-label">Catatan</label><input name="notes" class="form-control" value="<?= h((string) ($editingSchedule['notes'] ?? '')) ?>"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><?= $editingSchedule ? 'Update' : 'Simpan' ?></button></div>
        </form>
    </div></div>

    <?php if (!$editingSchedule): ?>
        <div class="card mb-3"><div class="card-body">
            <h5 class="mb-1">Import Jadwal dari CSV</h5>
            <p class="text-muted mb-2">Tambahkan banyak jadwal sekaligus menggunakan username karyawan.</p>
            <div class="mb-3">
                <a href="?page=schedules&download=schedule_template_csv" class="btn btn-outline-secondary">Download Template CSV</a>
            </div>
            <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="import_schedules_csv">
                <div class="col-md-8">
                    <label class="form-label">File CSV Jadwal</label>
                    <input type="file" name="schedules_csv" class="form-control" accept=".csv,text/csv" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100">Import CSV</button>
                </div>
            </form>
            <div class="small text-muted mt-2">Kolom wajib: username, tanggal, mulai, selesai. Lokasi dan catatan bersifat opsional. Maksimal 2 MB.</div>
        </div></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <h5>Daftar Jadwal</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Tanggal</th><th>Karyawan</th><th>Jam</th><th>Lokasi</th><th>Konfirmasi Karyawan</th><th>Aksi</th></tr></thead>
            <tbody>
                <!-- Loop semua jadwal yang diambil dari database. -->
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
                                <form method="post" data-confirm-submit="Yakin hapus jadwal ini?">
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
    <script>
        (function () {
            var searchInput = document.getElementById('schedule-employee-search');
            var searchButton = document.getElementById('schedule-employee-search-button');
            var employeeSelect = document.getElementById('schedule-employee-select');

            function searchEmployee() {
                var keyword = searchInput.value.trim().toLowerCase();
                if (!keyword) {
                    searchInput.setCustomValidity('Masukkan nama atau username karyawan.');
                    searchInput.reportValidity();
                    return;
                }

                var match = Array.from(employeeSelect.options).find(function (option) {
                    return option.dataset.search.indexOf(keyword) !== -1;
                });
                if (!match) {
                    searchInput.setCustomValidity('Karyawan tidak ditemukan.');
                    searchInput.reportValidity();
                    return;
                }

                searchInput.setCustomValidity('');
                employeeSelect.value = match.value;
                employeeSelect.focus();
            }

            searchInput.addEventListener('input', function () {
                searchInput.setCustomValidity('');
            });
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchEmployee();
                }
            });
            searchButton.addEventListener('click', searchEmployee);
        })();
    </script>
    <?php
    render_footer();
}

// Halaman karyawan untuk melihat dan mengonfirmasi jadwal miliknya.
function page_my_schedule(array $u, ?array $flash): void {
    render_header('Jadwal Kerja Saya', $u, $flash);

    // Ambil semua jadwal milik user yang sedang login.
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

// Halaman admin untuk verifikasi absensi karyawan.
function page_verify_attendance(array $u, ?array $flash): void {
    render_header('Verifikasi Absensi', $u, $flash);

    // Ambil data absensi beserta jadwal untuk penilaian otomatis.
    $rows = db()->query('SELECT a.*, u.name, s.start_time schedule_start, s.end_time schedule_end FROM attendance_logs a JOIN users u ON u.id=a.user_id LEFT JOIN schedules s ON s.user_id=a.user_id AND s.work_date=a.attendance_date ORDER BY a.attendance_date DESC LIMIT 100')->fetchAll();
    ?>
    <div class="card"><div class="card-body">
        <h5>Data Absensi</h5>
        <form id="bulk-attendance-form" method="post" class="row g-2 align-items-end mb-3" data-confirm-submit="Verifikasi semua data absensi yang dipilih?">
            <input type="hidden" name="action" value="bulk_verify_attendance">
            <div class="col-md-2">
                <label class="form-label">Status Massal</label>
                <select name="status" class="form-select" required>
                    <option value="" selected disabled>Pilih status</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                    <option value="pending">Perlu Ditinjau</option>
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
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th><input id="attendance-select-all" type="checkbox" class="form-check-input" aria-label="Pilih semua absensi"></th><th>Tanggal</th><th>Karyawan</th><th>Jadwal</th><th>Masuk</th><th>Pulang</th><th>Bukti Foto</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                    $assessment = attendance_system_assessment($r);
                    $selectedStatus = (string) $r['status'];
                ?>
                <tr>
                    <td><input type="checkbox" class="form-check-input attendance-select" form="bulk-attendance-form" name="attendance_ids[]" value="<?= (int) $r['id'] ?>" aria-label="Pilih absensi <?= h((string) $r['name']) ?> tanggal <?= h((string) $r['attendance_date']) ?>"></td>
                    <td><?= h((string) $r['attendance_date']) ?></td>
                    <td><?= h((string) $r['name']) ?></td>
                    <td><?= $r['schedule_start'] ? h((string) $r['schedule_start']) . ' - ' . h((string) $r['schedule_end']) : '-' ?></td>
                    <td><?= h((string) ($r['check_in'] ?? '-')) ?></td>
                    <td><?= h((string) ($r['check_out'] ?? '-')) ?></td>
                    <td><?php render_attendance_photo_preview((string) ($r['evidence_photo'] ?? '')); ?></td>
                    <td>
                        <span class="badge bg-<?= badge_class($selectedStatus) ?>"><?= h(status_label($selectedStatus)) ?></span>
                        <div class="small text-muted mt-1">Catatan sistem: <?= h($assessment['summary']) ?></div>
                    </td>
                    <td>
                        <form method="post" class="d-flex gap-1">
                            <input type="hidden" name="action" value="verify_attendance">
                            <input type="hidden" name="attendance_id" value="<?= (int) $r['id'] ?>">
                            <select name="status" class="form-select form-select-sm" required><option value="approved" <?= $selectedStatus === 'approved' ? 'selected' : '' ?>>Disetujui</option><option value="rejected" <?= $selectedStatus === 'rejected' ? 'selected' : '' ?>>Ditolak</option><option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Perlu Ditinjau</option></select>
                            <input name="verification_note" class="form-control form-control-sm" value="<?= h((string) ($r['verification_note'] ?? '')) ?>" placeholder="Catatan">
                            <button class="btn btn-sm btn-primary">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div></div>
    <script>
        (function () {
            var selectAll = document.getElementById('attendance-select-all');
            var checkboxes = Array.from(document.querySelectorAll('.attendance-select'));
            var selectedCount = document.getElementById('attendance-selected-count');
            var submitButton = document.getElementById('bulk-attendance-submit');

            function updateSelection() {
                var checkedCount = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
                selectedCount.textContent = String(checkedCount);
                submitButton.disabled = checkedCount === 0;
                selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            }

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
                updateSelection();
            });
            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateSelection);
            });
            updateSelection();
        })();
    </script>
    <?php
    render_footer();
}
// Halaman karyawan untuk mengajukan izin/sakit/cuti dan melihat riwayatnya.
function page_leave(array $u, ?array $flash): void {
    render_header('Permohonan Izin', $u, $flash);

    // Ambil riwayat izin milik user yang sedang login.
    $stmt = db()->prepare('SELECT * FROM leave_requests WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([(int) $u['id']]);
    $rows = $stmt->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <h5>Ajukan Izin</h5>
        <!-- Form pengajuan izin dengan upload bukti opsional. -->
        <form method="post" enctype="multipart/form-data" class="row g-2">
            <input type="hidden" name="action" value="submit_leave">
            <div class="col-md-2"><label class="form-label">Tanggal</label><input type="date" name="leave_date" class="form-control" min="<?= h(date('Y-m-d')) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Jenis</label><select name="type" class="form-select"><option value="izin">Izin</option><option value="sakit">Sakit</option><option value="cuti">Cuti</option></select></div>
            <div class="col-md-3"><label class="form-label">Bukti</label><input type="file" name="evidence_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf"></div>
            <div class="col-md-5"><label class="form-label">Alasan</label><input name="reason" class="form-control" required></div>
            <div class="col-12"><small class="text-muted">Upload opsional. Format: JPG, JPEG, PNG, PDF. Maksimal 10 MB.</small></div>
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
                    <td><?= h(status_label((string) $r['status'])) ?></td>
                    <td><?= h((string) ($r['admin_note'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <?php
    render_footer();
}

// Halaman admin untuk menyetujui, menolak, atau menghapus pengajuan izin.
function page_leave_approval(array $u, ?array $flash): void {
    render_header('Persetujuan Izin', $u, $flash);

    // Ambil semua pengajuan izin terbaru beserta nama karyawan.
    $rows = db()->query('SELECT l.*, u.name FROM leave_requests l JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 100')->fetchAll();
    ?>
    <div class="card"><div class="card-body">
        <h5>Permohonan Masuk</h5>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Karyawan</th><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h((string) $r['name']) ?></td><td><?= h((string) $r['leave_date']) ?></td><td><?= h((string) $r['type']) ?></td><td><?= h((string) $r['reason']) ?></td>
                    <td><?php render_evidence_preview((string) ($r['evidence'] ?? '')); ?></td>
                    <td><?= h(status_label((string) $r['status'])) ?></td>
                    <td>
                        <div class="d-flex gap-1 mb-1">
                            <form method="post" class="d-flex gap-1">
                                <input type="hidden" name="action" value="process_leave"><input type="hidden" name="leave_id" value="<?= (int) $r['id'] ?>">
                                <select name="status" class="form-select form-select-sm"><option value="approved" <?= $r['status'] === 'approved' ? 'selected' : '' ?>>Disetujui</option><option value="rejected" <?= $r['status'] === 'rejected' ? 'selected' : '' ?>>Ditolak</option><option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Perlu Ditinjau</option></select>
                                <input name="admin_note" class="form-control form-control-sm" placeholder="Catatan"><button class="btn btn-sm btn-primary">Simpan</button>
                            </form>
                        </div>
                        <form method="post" data-confirm-submit="Yakin hapus data izin ini beserta file buktinya?">
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

// Halaman user untuk mengirim feedback dan melihat riwayat feedback pribadi.
function page_feedback(array $u, ?array $flash): void {
    render_header('Feedback', $u, $flash);

    // Ambil riwayat feedback milik user yang sedang login.
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

// Halaman admin untuk mengelola data karyawan.
function page_employees(array $u, ?array $flash): void {
    render_header('Data Karyawan', $u, $flash);

    // Jika ada edit_employee_id di URL, form berubah menjadi mode edit.
    $editingId = (int) ($_GET['edit_employee_id'] ?? 0);
    $editingEmployee = $editingId > 0 ? find_user_by_id($editingId) : null;

    // Pastikan data yang diedit benar-benar karyawan, bukan admin.
    if ($editingEmployee && $editingEmployee['role'] !== 'karyawan') {
        $editingEmployee = null;
    }

    // Ambil semua karyawan untuk ditampilkan di tabel.
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
        <!-- Panel import CSV hanya tampil saat tidak sedang edit karyawan. -->
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
        <div class="employee-list-head">
            <div>
                <h5 class="mb-1">Daftar Karyawan</h5>
                <div class="small text-muted">
                    <span data-employee-visible-count><?= count($rows) ?></span> dari <?= count($rows) ?> karyawan ditampilkan
                </div>
            </div>
            <div class="employee-search">
                <label for="employee-search" class="form-label visually-hidden">Cari karyawan</label>
                <input id="employee-search" type="search" class="form-control" placeholder="Cari nama, username, atau jabatan" autocomplete="off" data-employee-search>
            </div>
        </div>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle">
            <thead><tr><th>Nama</th><th>Username</th><th>Jabatan</th><th>Dibuat</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                    // Teks gabungan untuk fitur search karyawan di sisi browser.
                    $employeeSearchText = strtolower(trim(implode(' ', [
                        (string) $r['name'],
                        (string) $r['username'],
                        (string) ($r['position'] ?? ''),
                    ])));
                ?>
                <tr data-employee-row data-employee-search-text="<?= h($employeeSearchText) ?>">
                    <td><?= h((string) $r['name']) ?></td>
                    <td><?= h((string) $r['username']) ?></td>
                    <td><?= h((string) ($r['position'] ?? '-')) ?></td>
                    <td><?= h((string) $r['created_at']) ?></td>
                    <td>
                        <div class="d-flex gap-1 mb-1">
                            <a href="?page=employees&edit_employee_id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" data-confirm-submit="Yakin hapus karyawan ini beserta semua data terkait?">
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
                <tr class="d-none" data-employee-empty-row>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada karyawan yang cocok dengan pencarian.</td>
                </tr>
            </tbody>
        </table></div>
    </div></div>
    <?php
    render_footer();
}

// Halaman untuk mengubah profil user yang sedang login.
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

// Halaman laporan absensi untuk admin, lengkap dengan filter dan ringkasan.
function page_reports(array $u, ?array $flash): void {
    render_header('Laporan Absensi', $u, $flash);

    // Periode default adalah bulan berjalan, kecuali user memilih periode lain.
    $period = $_GET['period'] ?? 'bulan';

    // Jika from/to belum dikirim, sistem membuat rentang tanggal berdasarkan periode.
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

    // Pastikan akhir periode tidak pernah lebih awal dari awal periode.
    if ($to < $from) {
        $to = $from;
    }

    // Filter karyawan bersifat opsional; 0 berarti semua karyawan.
    $employeeId = (int) ($_GET['employee_id'] ?? 0);

    // Data karyawan untuk dropdown filter.
    $employees = db()->query("SELECT id, name FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();

    // Query detail absensi sesuai filter tanggal dan karyawan.
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

    // Hitung jumlah absensi approved pada periode terpilih.
    $attendanceCountSql = "SELECT COUNT(*) FROM attendance_logs a WHERE a.status='approved' AND a.attendance_date BETWEEN ? AND ?";
    $attendanceCountParams = [$from, $to];
    if ($employeeId > 0) {
        $attendanceCountSql .= ' AND a.user_id=?';
        $attendanceCountParams[] = $employeeId;
    }
    $attendanceStmt = db()->prepare($attendanceCountSql);
    $attendanceStmt->execute($attendanceCountParams);
    $hadirTotal = (int) $attendanceStmt->fetchColumn();

    // Hitung keterlambatan berdasarkan perbandingan check_in dengan start_time jadwal.
    $lateSql = "SELECT COUNT(*) FROM attendance_logs a JOIN schedules s ON s.user_id=a.user_id AND s.work_date=a.attendance_date WHERE a.status='approved' AND TIME(a.check_in) > s.start_time AND a.attendance_date BETWEEN ? AND ?";
    $lateParams = [$from, $to];
    if ($employeeId > 0) {
        $lateSql .= ' AND a.user_id=?';
        $lateParams[] = $employeeId;
    }
    $lateStmt = db()->prepare($lateSql);
    $lateStmt->execute($lateParams);
    $terlambat = (int) $lateStmt->fetchColumn();

    // Hitung izin yang sudah approved pada periode terpilih.
    $leaveSql = "SELECT COUNT(*) FROM leave_requests WHERE status='approved' AND leave_date BETWEEN ? AND ?";
    $leaveParams = [$from, $to];
    if ($employeeId > 0) {
        $leaveSql .= ' AND user_id=?';
        $leaveParams[] = $employeeId;
    }
    $leaveStmt = db()->prepare($leaveSql);
    $leaveStmt->execute($leaveParams);
    $izin = (int) $leaveStmt->fetchColumn();

    // Hitung total jadwal sebagai dasar menghitung absen.
    $scheduleSql = 'SELECT COUNT(*) FROM schedules WHERE work_date BETWEEN ? AND ?';
    $scheduleParams = [$from, $to];
    if ($employeeId > 0) {
        $scheduleSql .= ' AND user_id=?';
        $scheduleParams[] = $employeeId;
    }
    $scheduleStmt = db()->prepare($scheduleSql);
    $scheduleStmt->execute($scheduleParams);
    $totalJadwal = (int) $scheduleStmt->fetchColumn();

    // Hadir tidak terlambat = total hadir approved dikurangi yang terlambat.
    $hadir = max($hadirTotal - $terlambat, 0);

    // Absen dihitung dari jadwal yang tidak punya absensi approved dan bukan izin approved.
    $absen = max($totalJadwal - $hadirTotal - $izin, 0);
    ?>
    <div class="card mb-3"><div class="card-body">
        <h5>Filter</h5>
        <form method="get" class="row g-2">
            <input type="hidden" name="page" value="reports">
            <div class="col-md-2"><label class="form-label">Periode</label><select class="form-select" name="period"><option value="hari" <?= $period === 'hari' ? 'selected' : '' ?>>Per Hari</option><option value="minggu" <?= $period === 'minggu' ? 'selected' : '' ?>>Per Minggu</option><option value="bulan" <?= $period === 'bulan' ? 'selected' : '' ?>>Per Bulan</option></select></div>
            <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" id="report-from" name="from" value="<?= h($from) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" id="report-to" name="to" value="<?= h($to) ?>" min="<?= h($from) ?>" required></div>
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
                <tr><td><?= h((string) $r['attendance_date']) ?></td><td><?= h((string) $r['name']) ?></td><td><?= h((string) ($r['check_in'] ?? '-')) ?></td><td><?= h((string) ($r['check_out'] ?? '-')) ?></td><td><?= h(status_label((string) $r['status'])) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div></div>
    <script>
        (function () {
            var fromInput = document.getElementById('report-from');
            var toInput = document.getElementById('report-to');

            function syncReportDates() {
                toInput.min = fromInput.value;
                if (toInput.value && toInput.value < fromInput.value) {
                    toInput.value = fromInput.value;
                }
            }

            fromInput.addEventListener('change', syncReportDates);
            syncReportDates();
        })();
    </script>
    <?php
    render_footer();
}

// Halaman admin untuk melihat dan export audit log.
function page_audit(array $u, ?array $flash): void {
    render_header('Audit Log', $u, $flash);

    // Rentang tanggal default adalah awal bulan sampai hari ini.
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');

    // Pastikan akhir periode tidak pernah lebih awal dari awal periode.
    if ($to < $from) {
        $to = $from;
    }

    // Ambil audit log sesuai rentang tanggal.
    $stmt = db()->prepare('SELECT COALESCE(u.username, "sistem") username, a.action, a.description, a.ip_address, a.created_at FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE DATE(a.created_at) BETWEEN ? AND ? ORDER BY a.created_at DESC LIMIT 200');
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
    ?>
    <div class="card mb-3"><div class="card-body">
        <form method="get" class="row g-2">
            <input type="hidden" name="page" value="audit">
            <div class="col-md-2"><label class="form-label">Dari</label><input type="date" class="form-control" id="audit-from" name="from" value="<?= h($from) ?>" required></div>
            <div class="col-md-2"><label class="form-label">Sampai</label><input type="date" class="form-control" id="audit-to" name="to" value="<?= h($to) ?>" min="<?= h($from) ?>" required></div>
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
    <script>
        (function () {
            var fromInput = document.getElementById('audit-from');
            var toInput = document.getElementById('audit-to');

            function syncAuditDates() {
                toInput.min = fromInput.value;
                if (toInput.value && toInput.value < fromInput.value) {
                    toInput.value = fromInput.value;
                }
            }

            fromInput.addEventListener('change', syncAuditDates);
            syncAuditDates();
        })();
    </script>
    <?php
    render_footer();
}

// Halaman admin untuk membaca feedback masuk dari karyawan.
function page_feedback_inbox(array $u, ?array $flash): void {
    // Saat halaman dibuka, semua feedback yang belum dilihat ditandai sudah dilihat.
    db()->exec("UPDATE feedbacks SET admin_seen_at=NOW() WHERE admin_seen_at IS NULL");
    render_header('Feedback Masuk', $u, $flash);

    // Ambil feedback terbaru beserta identitas karyawan pengirim.
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
