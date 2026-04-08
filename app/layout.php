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
    $navBadges = [];
    $mobileNavHasNotifications = false;

    if ($u && ($u['role'] ?? '') === 'admin') {
        $navBadges = [
            'schedules' => (int) db()->query("SELECT COUNT(*) FROM schedules WHERE acknowledged_at IS NOT NULL AND acknowledged_seen_at IS NULL")->fetchColumn(),
            'verify_attendance' => (int) db()->query("SELECT COUNT(*) FROM attendance_logs WHERE status='pending'")->fetchColumn(),
            'leave_approval' => (int) db()->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'")->fetchColumn(),
            'feedback_inbox' => (int) db()->query("SELECT COUNT(*) FROM feedbacks WHERE admin_seen_at IS NULL")->fetchColumn(),
        ];
    } elseif ($u && ($u['role'] ?? '') === 'karyawan') {
        $scheduleBadgeStmt = db()->prepare("SELECT COUNT(*) FROM schedules WHERE user_id=? AND acknowledged_at IS NULL");
        $scheduleBadgeStmt->execute([(int) $u['id']]);
        $navBadges = [
            'my_schedule' => (int) $scheduleBadgeStmt->fetchColumn(),
        ];
    }

    $mobileNavHasNotifications = array_sum($navBadges) > 0;
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= h($title) ?> - <?= h(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            color: var(--ink-700);
            background:
                radial-gradient(circle at top left, rgba(63, 125, 98, 0.18), transparent 28%),
                linear-gradient(180deg, #f8f6f1 0%, #edf3ef 100%);
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .section-title,
        .navbar-brand strong,
        .brand-mark,
        .btn {
            font-family: 'Poppins', sans-serif;
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

        .mobile-nav-toggle-wrap {
            position: relative;
            display: inline-flex;
        }

        .mobile-nav-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: #ffcd35;
            border: 2px solid rgba(22, 48, 43, 0.92);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.08);
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

        .nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.25rem;
            height: 1.25rem;
            margin-left: 0.45rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: #ffcd35;
            color: #16302b;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1;
            vertical-align: middle;
        }

        .nav-badge-mobile-only {
            display: none;
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

        .login-hero-title {
            font-size: clamp(2rem, 2vw + 1.2rem, 3rem);
            line-height: 1.15;
        }

        .login-hero-subtitle {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.78);
        }

        .file-picker-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .file-picker-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            min-height: 52px;
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #d6dfda;
            border-radius: 14px;
            background: #f8fbf9;
            color: var(--brand-900);
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .file-picker-trigger:hover {
            border-color: var(--brand-500);
            background: #eef5f1;
        }

        .file-picker-trigger svg {
            flex: 0 0 auto;
        }

        .file-picker-input:focus + .file-picker-trigger,
        .file-picker-input:focus-visible + .file-picker-trigger {
            outline: 0;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 0.2rem rgba(63, 125, 98, 0.15);
        }

        .file-picker-name {
            margin-top: 0.45rem;
            font-size: 0.88rem;
            color: #66756f;
            word-break: break-word;
        }

        @media (min-width: 768px) {
            .mobile-only {
                display: none !important;
            }

            .file-picker-input {
                position: static;
                width: 100%;
                height: auto;
                padding: 0.375rem 0.75rem;
                margin: 0;
                overflow: visible;
                clip: auto;
                white-space: normal;
                border: var(--bs-border-width) solid var(--bs-border-color);
                border-radius: 12px;
                background: var(--bs-body-bg);
            }

            .file-picker-trigger,
            .file-picker-name {
                display: none;
            }
        }

        @media (max-width: 991.98px) {
            .glass-nav .container {
                align-items: flex-start;
            }

            .navbar-brand {
                max-width: calc(100% - 56px);
            }

            .navbar-brand strong {
                display: block;
                line-height: 1.2;
            }

            #mainNav {
                width: 100%;
                margin-top: 0.9rem;
            }

            .nav-pills-demo {
                gap: 0.35rem;
            }

            .nav-pills-demo .nav-link {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 0.7rem 0.95rem;
            }

            .nav-badge {
                margin-left: 0.75rem;
                flex: 0 0 auto;
            }

            .nav-badge-mobile-only {
                display: inline-flex;
            }
        }

        @media (max-width: 767.98px) {
            body {
                background:
                    radial-gradient(circle at top left, rgba(63, 125, 98, 0.14), transparent 34%),
                    linear-gradient(180deg, #f8f6f1 0%, #edf3ef 100%);
            }

            .app-shell {
                padding-bottom: 2rem;
            }

            .container {
                padding-inline: 0.9rem;
            }

            .card,
            .content-card {
                border-radius: 16px;
            }

            .card .card-body {
                padding: 1rem;
            }

            .glass-nav {
                margin-bottom: 1rem;
            }

            .brand-mark {
                width: 36px;
                height: 36px;
                border-radius: 10px;
            }

            .hero-panel .card-body {
                padding: 1.1rem !important;
            }

            .hero-panel h1 {
                font-size: 1.55rem;
            }

            .hero-panel .row > div:last-child {
                text-align: left !important;
            }

            .login-wrap {
                min-height: auto;
                align-items: stretch;
                padding-top: 0.5rem;
            }

            .login-hero {
                min-height: 320px !important;
            }

            .login-hero-image {
                max-height: 150px !important;
                margin-bottom: 1rem !important;
                border-radius: 16px !important;
            }

            .login-hero-title {
                font-size: 1.85rem;
            }

            .login-hero-subtitle {
                font-size: 1.05rem;
            }

            .table {
                font-size: 0.92rem;
            }

            .table thead th {
                font-size: 0.72rem;
                white-space: nowrap;
            }

            .table-responsive {
                margin-inline: -0.2rem;
            }

            .table td .d-flex,
            .table td form.d-flex {
                flex-wrap: wrap;
            }

            .table td .btn,
            .table td form.d-flex .form-control,
            .table td form.d-flex .form-select,
            .table td form.d-flex .btn {
                width: 100%;
            }

            .table td form.d-flex .form-control,
            .table td form.d-flex .form-select {
                min-width: 0;
            }

            code {
                white-space: normal;
                word-break: break-word;
            }
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
            <span class="mobile-nav-toggle-wrap">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <?php if ($mobileNavHasNotifications): ?>
                    <span class="mobile-nav-dot d-lg-none"></span>
                <?php endif; ?>
            </span>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav nav-pills-demo me-auto ms-lg-4 mb-2 mb-lg-0">
                    <?php foreach ($navItems as $page => $label): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === $page ? 'active' : '' ?>" href="?page=<?= h($page) ?>">
                                <?= h($label) ?>
                                <?php if (($navBadges[$page] ?? 0) > 0): ?>
                                    <?php
                                    $badgeClass = 'nav-badge';
                                    if ($page === 'my_schedule') {
                                        $badgeClass .= ' nav-badge-mobile-only';
                                    }
                                    ?>
                                    <span class="<?= h($badgeClass) ?>"><?= (int) $navBadges[$page] ?></span>
                                <?php endif; ?>
                            </a>
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
                        <h1 class="h3 mb-2"><?= h($title) ?></h1>
                        <p class="muted">Portal absensi PT. Hansirus Agro Andalan.</p>
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
