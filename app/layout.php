<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function navigation_items(array $u): array {
    if (($u['role'] ?? '') === 'admin') {
        return [
            'dashboard' => 'Dashboard',
            'employees' => 'Karyawan',
            'schedules' => 'Jadwal',
            'verify_attendance' => 'Verifikasi',
            'leave_approval' => 'Izin',
            'reports' => 'Laporan',
            'feedback_inbox' => 'Feedback',
            'audit' => 'Audit',
        ];
    }

    return [
        'dashboard' => 'Dashboard',
        'attendance' => 'Absensi',
        'my_schedule' => 'Jadwal',
        'leave' => 'Izin',
        'feedback' => 'Feedback',
        'profile' => 'Profil',
    ];
}

function render_header(string $title, ?array $u, ?array $flash): void {
    $currentPage = (string) ($_GET['page'] ?? ($u ? 'dashboard' : 'login'));
    $navItems = $u ? navigation_items($u) : [];
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= h($title) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-900: #16302b;
            --brand-700: #245347;
            --brand-500: #3f7d62;
            --sand-100: #f4efe6;
            --sand-200: #ebe3d3;
            --ink-700: #34423d;
        }

        body {
            min-height: 100vh;
            color: var(--ink-700);
            background:
                radial-gradient(circle at top left, rgba(63, 125, 98, 0.18), transparent 28%),
                linear-gradient(180deg, #f8f6f1 0%, #edf3ef 100%);
        }

        .app-shell {
            padding-bottom: 3rem;
        }

        .hero-panel {
            color: #fff;
            border: 0;
            overflow: hidden;
            background: linear-gradient(135deg, var(--brand-900), var(--brand-500));
            box-shadow: 0 16px 40px rgba(22, 48, 43, 0.18);
        }

        .hero-panel .muted {
            color: rgba(255,255,255,0.78);
        }

        .glass-nav {
            backdrop-filter: blur(12px);
            background: rgba(22, 48, 43, 0.92);
            box-shadow: 0 10px 30px rgba(22, 48, 43, 0.16);
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-weight: 700;
            background: rgba(255,255,255,0.12);
        }

        .nav-pills-demo .nav-link {
            color: rgba(255,255,255,0.78);
            border-radius: 999px;
            padding-inline: 0.9rem;
        }

        .nav-pills-demo .nav-link.active,
        .nav-pills-demo .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.12);
        }

        .content-card,
        .card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(37, 54, 46, 0.08);
        }

        .card .card-body {
            padding: 1.25rem 1.25rem;
        }

        .section-title {
            font-weight: 700;
            color: var(--brand-900);
            letter-spacing: 0.02em;
        }

        .stat-tile {
            color: #fff;
            background: linear-gradient(135deg, var(--brand-700), var(--brand-500));
        }

        .table {
            --bs-table-bg: transparent;
        }

        .table thead th {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #66756f;
            border-bottom-width: 1px;
        }

        .table-responsive {
            border-radius: 16px;
        }

        .form-control,
        .form-select,
        .btn {
            border-radius: 12px;
        }

        .btn-primary {
            background-color: var(--brand-700);
            border-color: var(--brand-700);
        }

        .btn-primary:hover {
            background-color: var(--brand-900);
            border-color: var(--brand-900);
        }

        .page-head {
            margin-bottom: 1.25rem;
        }

        .page-head p {
            margin-bottom: 0;
            color: #5d6a65;
        }

        .login-wrap {
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark glass-nav mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="?page=dashboard">
            <span class="brand-mark">HA</span>
            <span>
                <strong>Absensi Hansirus</strong><br>
                <small class="text-white-50">Sistem Monitoring Kehadiran</small>
            </span>
        </a>
        <?php if ($u): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav nav-pills-demo me-auto ms-lg-4 mb-2 mb-lg-0">
                    <?php foreach ($navItems as $page => $label): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === $page ? 'active' : '' ?>" href="?page=<?= h($page) ?>"><?= h($label) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center text-white small">
                    <a href="?page=profile" class="btn btn-sm btn-outline-light">Profil</a>
                    <span><?= h((string) $u['name']) ?> (<?= h((string) $u['role']) ?>)</span>
                    <form method="post">
                        <input type="hidden" name="action" value="logout">
                        <button class="btn btn-sm btn-warning">Logout</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container app-shell">
    <?php if ($u): ?>
        <div class="card hero-panel mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="small text-uppercase fw-semibold muted">Demo Tugas Akhir</div>
                        <h1 class="h3 mt-2 mb-2"><?= h($title) ?></h1>
                        <p class="muted">Portal absensi PT. Hansirus Agro Andalan untuk pengelolaan kehadiran, izin, jadwal kerja, dan pelaporan.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="small muted">Tanggal sistem</div>
                        <div class="fs-5 fw-semibold"><?= h(date('d M Y')) ?></div>
                        <div class="small muted"><?= h(date('H:i')) ?> WIB</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= h((string) $flash['type']) ?> border-0 shadow-sm"><?= h((string) $flash['message']) ?></div>
    <?php endif; ?>
    <?php
}

function render_footer(): void {
    echo '</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
}
