<?php

// Mengaktifkan mode tipe ketat untuk mencegah pemanggilan fungsi dengan tipe data yang salah.
declare(strict_types=1);

// Memuat pondasi aplikasi: session, database, helper, dan konfigurasi .env.
require_once __DIR__ . '/app/bootstrap.php';

// Memuat handler untuk semua request POST seperti login, absensi, simpan jadwal, dan export.
require_once __DIR__ . '/app/actions.php';

// Memuat fungsi-fungsi render halaman yang menampilkan HTML ke browser.
require_once __DIR__ . '/app/pages.php';

// Menentukan halaman aktif dari query string; jika kosong, arahkan sesuai status login.
$page = $_GET['page'] ?? (user() ? 'dashboard' : 'login');

// Mengambil flash message yang mungkin dibuat oleh proses sebelumnya.
$flash = flash();

// Mengambil data user login saat ini, jika ada.
$u = user();

// Halaman login boleh dibuka tanpa login terlebih dahulu.
if ($page === 'login') {
    page_login($u, $flash);
    exit;
}

// Selain halaman login, semua halaman wajib melewati autentikasi.
need_auth();

// Ambil ulang data user setelah validasi login untuk memastikan data masih tersedia.
$u = user();

// Router sederhana: memilih fungsi halaman berdasarkan nilai ?page=...
switch ($page) {
    case 'dashboard':
        // Halaman ringkasan utama untuk admin atau karyawan.
        page_dashboard($u, $flash);
        break;
    case 'attendance':
        // Halaman absensi harian karyawan.
        page_attendance($u, $flash);
        break;
    case 'my_schedule':
        // Halaman jadwal milik karyawan yang sedang login.
        page_my_schedule($u, $flash);
        break;
    case 'leave':
        // Halaman pengajuan izin/sakit/cuti untuk karyawan.
        page_leave($u, $flash);
        break;
    case 'feedback':
        // Halaman kirim feedback dari karyawan.
        page_feedback($u, $flash);
        break;
    case 'profile':
        // Halaman edit profil user yang sedang login.
        page_profile($u, $flash);
        break;
    case 'employees':
        // Halaman master data karyawan hanya untuk admin.
        need_admin();

        // Download template CSV untuk import karyawan.
        if (($_GET['download'] ?? '') === 'employee_template_csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=employee-import-template.csv');
            echo "sep=;\n";
            echo "nama;username;jabatan;password\n";
            echo "Contoh Karyawan;contoh_karyawan;Karyawan Lapangan;password123\n";
            exit;
        }
        page_employees($u, $flash);
        break;
    case 'schedules':
        // Halaman kelola jadwal kerja karyawan.
        need_admin();

        // Download template CSV untuk import jadwal.
        if (($_GET['download'] ?? '') === 'schedule_template_csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=schedule-import-template.csv');
            echo "sep=;\n";
            echo "username;tanggal;mulai;selesai;lokasi;catatan\n";
            echo 'contoh_karyawan;' . date('Y-m-d', strtotime('+1 day')) . ";08:00;17:00;Kantor;Shift pagi\n";
            exit;
        }
        page_schedules($u, $flash);
        break;
    case 'feedback_inbox':
        // Halaman admin untuk melihat feedback masuk.
        need_admin();
        page_feedback_inbox($u, $flash);
        break;
    case 'verify_attendance':
        // Halaman admin untuk menyetujui atau menolak absensi.
        need_admin();
        page_verify_attendance($u, $flash);
        break;
    case 'leave_approval':
        // Halaman admin untuk memproses izin/sakit/cuti.
        need_admin();
        page_leave_approval($u, $flash);
        break;
    case 'reports':
        // Halaman laporan absensi dan export CSV.
        need_admin();
        page_reports($u, $flash);
        break;
    case 'audit':
        // Halaman log aktivitas sistem.
        need_admin();
        page_audit($u, $flash);
        break;
    default:
        // Fallback jika nilai ?page= tidak dikenali.
        render_header('Not Found', $u, $flash);
        echo "<div class='alert alert-warning'>Halaman tidak ditemukan.</div>";
        render_footer();
}
