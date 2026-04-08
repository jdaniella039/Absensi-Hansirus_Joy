<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/actions.php';
require_once __DIR__ . '/app/pages.php';

$page = $_GET['page'] ?? (user() ? 'dashboard' : 'login');
$flash = flash();
$u = user();

if ($page === 'login') {
    page_login($u, $flash);
    exit;
}

need_auth();
$u = user();

switch ($page) {
    case 'dashboard':
        page_dashboard($u, $flash);
        break;
    case 'attendance':
        page_attendance($u, $flash);
        break;
    case 'my_schedule':
        page_my_schedule($u, $flash);
        break;
    case 'leave':
        page_leave($u, $flash);
        break;
    case 'feedback':
        page_feedback($u, $flash);
        break;
    case 'profile':
        page_profile($u, $flash);
        break;
    case 'employees':
        need_admin();
        page_employees($u, $flash);
        break;
    case 'schedules':
        need_admin();
        page_schedules($u, $flash);
        break;
    case 'feedback_inbox':
        need_admin();
        page_feedback_inbox($u, $flash);
        break;
    case 'verify_attendance':
        need_admin();
        page_verify_attendance($u, $flash);
        break;
    case 'leave_approval':
        need_admin();
        page_leave_approval($u, $flash);
        break;
    case 'reports':
        need_admin();
        page_reports($u, $flash);
        break;
    case 'audit':
        need_admin();
        page_audit($u, $flash);
        break;
    default:
        render_header('Not Found', $u, $flash);
        echo "<div class='alert alert-warning'>Halaman tidak ditemukan.</div>";
        render_footer();
}
