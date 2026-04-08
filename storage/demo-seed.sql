USE absensi_hansirus;

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM audit_logs;
DELETE FROM feedbacks;
DELETE FROM leave_requests;
DELETE FROM attendance_logs;
DELETE FROM schedules;
DELETE FROM users;
ALTER TABLE audit_logs AUTO_INCREMENT = 1;
ALTER TABLE feedbacks AUTO_INCREMENT = 1;
ALTER TABLE leave_requests AUTO_INCREMENT = 1;
ALTER TABLE attendance_logs AUTO_INCREMENT = 1;
ALTER TABLE schedules AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (id, name, username, password_hash, role, position, created_at) VALUES
(1, 'Administrator', 'admin', '$2y$10$PIHq.dsHJ7.IF2913Qsa8OA2gz5aQUfIv/jkvjKAOHeUjZG8VjqJG', 'admin', 'Admin HRD', '2026-03-01 08:00:00'),
(2, 'Budi Santoso', 'budi', '$2y$10$sROosBgWBXiYOhTpEqo1He6gyB0WtHhpluVsAFXItBNXnkwJD3MEK', 'karyawan', 'Supervisor Lapangan', '2026-03-01 08:10:00'),
(3, 'Siti Rahma', 'siti', '$2y$10$sROosBgWBXiYOhTpEqo1He6gyB0WtHhpluVsAFXItBNXnkwJD3MEK', 'karyawan', 'Admin Kebun', '2026-03-01 08:12:00'),
(4, 'Andi Saputra', 'andi', '$2y$10$sROosBgWBXiYOhTpEqo1He6gyB0WtHhpluVsAFXItBNXnkwJD3MEK', 'karyawan', 'Mandor Panen', '2026-03-01 08:15:00'),
(5, 'Rina Marlina', 'rina', '$2y$10$sROosBgWBXiYOhTpEqo1He6gyB0WtHhpluVsAFXItBNXnkwJD3MEK', 'karyawan', 'Staff Gudang', '2026-03-01 08:18:00'),
(6, 'Dewi Lestari', 'dewi', '$2y$10$sROosBgWBXiYOhTpEqo1He6gyB0WtHhpluVsAFXItBNXnkwJD3MEK', 'karyawan', 'Staff Administrasi', '2026-03-01 08:20:00');

INSERT INTO schedules (id, user_id, work_date, start_time, end_time, location, notes, acknowledged_at, created_by, created_at) VALUES
(1, 2, '2026-03-08', '07:30:00', '16:00:00', 'Blok A', 'Monitoring panen pagi', '2026-03-07 16:10:00', 1, '2026-03-07 15:00:00'),
(2, 3, '2026-03-08', '08:00:00', '16:30:00', 'Kantor Kebun', 'Rekap administrasi harian', '2026-03-07 16:20:00', 1, '2026-03-07 15:02:00'),
(3, 4, '2026-03-08', '07:00:00', '15:30:00', 'Blok C', 'Koordinasi tim panen', NULL, 1, '2026-03-07 15:04:00'),
(4, 5, '2026-03-08', '08:00:00', '17:00:00', 'Gudang Pusat', 'Pemeriksaan stok pupuk', '2026-03-07 16:45:00', 1, '2026-03-07 15:06:00'),
(5, 6, '2026-03-08', '08:00:00', '16:00:00', 'Kantor Utama', 'Input laporan produksi', '2026-03-07 16:30:00', 1, '2026-03-07 15:08:00'),
(6, 2, '2026-03-09', '07:30:00', '16:00:00', 'Blok B', 'Cek hasil panen sore', '2026-03-08 17:05:00', 1, '2026-03-08 16:00:00'),
(7, 3, '2026-03-09', '08:00:00', '16:30:00', 'Kantor Kebun', 'Input absensi lapangan', '2026-03-08 17:07:00', 1, '2026-03-08 16:02:00'),
(8, 4, '2026-03-09', '07:00:00', '15:30:00', 'Blok D', 'Pantau pengiriman buah', '2026-03-08 17:10:00', 1, '2026-03-08 16:04:00'),
(9, 5, '2026-03-09', '08:00:00', '17:00:00', 'Gudang Pusat', 'Pengecekan surat jalan', '2026-03-08 17:12:00', 1, '2026-03-08 16:06:00'),
(10, 6, '2026-03-09', '08:00:00', '16:00:00', 'Kantor Utama', 'Persiapan laporan mingguan', NULL, 1, '2026-03-08 16:08:00'),
(11, 2, '2026-03-10', '07:30:00', '16:00:00', 'Blok A', 'Koordinasi evaluasi panen', NULL, 1, '2026-03-09 16:30:00'),
(12, 3, '2026-03-10', '08:00:00', '16:30:00', 'Kantor Kebun', 'Validasi data absensi', NULL, 1, '2026-03-09 16:32:00'),
(13, 4, '2026-03-10', '07:00:00', '15:30:00', 'Blok C', 'Briefing tenaga lapangan', NULL, 1, '2026-03-09 16:34:00'),
(14, 5, '2026-03-10', '08:00:00', '17:00:00', 'Gudang Pusat', 'Stock opname harian', NULL, 1, '2026-03-09 16:36:00'),
(15, 6, '2026-03-10', '08:00:00', '16:00:00', 'Kantor Utama', 'Input laporan produksi', NULL, 1, '2026-03-09 16:38:00'),
(16, 2, '2026-03-11', '07:30:00', '16:00:00', 'Blok B', 'Tindak lanjut evaluasi', NULL, 1, '2026-03-10 17:00:00'),
(17, 3, '2026-03-11', '08:00:00', '16:30:00', 'Kantor Kebun', 'Finalisasi rekap bulanan', NULL, 1, '2026-03-10 17:02:00'),
(18, 4, '2026-03-11', '07:00:00', '15:30:00', 'Blok D', 'Kontrol kualitas panen', NULL, 1, '2026-03-10 17:04:00'),
(19, 5, '2026-03-11', '08:00:00', '17:00:00', 'Gudang Pusat', 'Penerimaan barang masuk', NULL, 1, '2026-03-10 17:06:00'),
(20, 6, '2026-03-11', '08:00:00', '16:00:00', 'Kantor Utama', 'Pengarsipan dokumen', NULL, 1, '2026-03-10 17:08:00');

INSERT INTO attendance_logs (id, user_id, attendance_date, check_in, break_start, break_end, check_out, status, verification_note, verified_by, verified_at, created_at) VALUES
(1, 2, '2026-03-08', '2026-03-08 07:28:00', '2026-03-08 12:05:00', '2026-03-08 13:00:00', '2026-03-08 16:01:00', 'approved', 'Hadir tepat waktu', 1, '2026-03-08 16:20:00', '2026-03-08 07:28:00'),
(2, 3, '2026-03-08', '2026-03-08 08:10:00', '2026-03-08 12:01:00', '2026-03-08 12:50:00', '2026-03-08 16:35:00', 'approved', 'Terlambat 10 menit', 1, '2026-03-08 16:40:00', '2026-03-08 08:10:00'),
(3, 4, '2026-03-08', '2026-03-08 06:58:00', '2026-03-08 11:59:00', '2026-03-08 12:40:00', '2026-03-08 15:20:00', 'approved', 'Pulang lebih awal sesuai instruksi lapangan', 1, '2026-03-08 15:45:00', '2026-03-08 06:58:00'),
(4, 5, '2026-03-08', '2026-03-08 08:01:00', '2026-03-08 12:00:00', '2026-03-08 12:45:00', '2026-03-08 17:02:00', 'approved', 'Lengkap', 1, '2026-03-08 17:15:00', '2026-03-08 08:01:00'),
(5, 6, '2026-03-08', '2026-03-08 08:03:00', NULL, NULL, NULL, 'rejected', 'Lupa menyelesaikan absensi pulang', 1, '2026-03-08 16:30:00', '2026-03-08 08:03:00'),
(6, 2, '2026-03-09', '2026-03-09 07:31:00', '2026-03-09 12:00:00', '2026-03-09 12:50:00', '2026-03-09 16:02:00', 'approved', 'Lengkap', 1, '2026-03-09 16:20:00', '2026-03-09 07:31:00'),
(7, 3, '2026-03-09', '2026-03-09 07:58:00', '2026-03-09 12:03:00', '2026-03-09 12:47:00', '2026-03-09 16:28:00', 'approved', 'Lengkap', 1, '2026-03-09 16:35:00', '2026-03-09 07:58:00'),
(8, 4, '2026-03-09', '2026-03-09 07:05:00', '2026-03-09 12:01:00', '2026-03-09 12:43:00', '2026-03-09 15:29:00', 'approved', 'Sedikit terlambat', 1, '2026-03-09 15:50:00', '2026-03-09 07:05:00'),
(9, 5, '2026-03-09', '2026-03-09 08:00:00', '2026-03-09 12:06:00', '2026-03-09 12:55:00', '2026-03-09 17:00:00', 'approved', 'Lengkap', 1, '2026-03-09 17:20:00', '2026-03-09 08:00:00'),
(10, 6, '2026-03-09', '2026-03-09 08:02:00', NULL, NULL, '2026-03-09 16:01:00', 'pending', NULL, NULL, NULL, '2026-03-09 08:02:00'),
(11, 2, '2026-03-10', '2026-03-10 07:29:00', '2026-03-10 12:02:00', '2026-03-10 12:55:00', NULL, 'pending', NULL, NULL, NULL, '2026-03-10 07:29:00'),
(12, 3, '2026-03-10', '2026-03-10 08:11:00', '2026-03-10 12:10:00', '2026-03-10 13:00:00', NULL, 'pending', NULL, NULL, NULL, '2026-03-10 08:11:00'),
(13, 5, '2026-03-10', '2026-03-10 07:59:00', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-03-10 07:59:00');

INSERT INTO leave_requests (id, user_id, leave_date, type, reason, evidence, status, admin_note, processed_by, processed_at, created_at) VALUES
(1, 4, '2026-03-10', 'izin', 'Menghadiri keperluan keluarga di pagi hari', NULL, 'approved', 'Disetujui setengah hari', 1, '2026-03-09 18:00:00', '2026-03-09 09:00:00'),
(2, 6, '2026-03-10', 'sakit', 'Demam dan perlu istirahat di rumah', NULL, 'pending', NULL, NULL, NULL, '2026-03-10 06:40:00'),
(3, 5, '2026-03-11', 'cuti', 'Keperluan keluarga luar kota', NULL, 'approved', 'Cuti disetujui', 1, '2026-03-10 10:00:00', '2026-03-10 08:15:00'),
(4, 3, '2026-03-12', 'izin', 'Mengurus dokumen pribadi', NULL, 'rejected', 'Mohon ajukan ulang dengan bukti pendukung', 1, '2026-03-10 11:10:00', '2026-03-10 08:30:00');

INSERT INTO feedbacks (id, user_id, category, message, created_at) VALUES
(1, 2, 'saran', 'Dashboard sudah membantu, akan lebih baik jika ada filter tanggal di absensi saya.', '2026-03-09 14:20:00'),
(2, 3, 'kritik', 'Perlu notifikasi saat jadwal baru ditambahkan oleh admin.', '2026-03-09 15:10:00'),
(3, 5, 'masalah', 'Upload bukti izin sempat gagal saat ukuran file terlalu besar.', '2026-03-10 09:45:00');

INSERT INTO audit_logs (id, user_id, action, description, ip_address, created_at) VALUES
(1, 1, 'LOGIN', 'Masuk sistem', '127.0.0.1', '2026-03-08 07:00:00'),
(2, 1, 'CREATE_JADWAL', 'Tambah jadwal', '127.0.0.1', '2026-03-08 15:00:00'),
(3, 2, 'KONFIRMASI_JADWAL', 'Konfirmasi jadwal 6', '127.0.0.1', '2026-03-08 17:05:00'),
(4, 2, 'ABSENSI_MASUK', 'Karyawan melakukan absensi', '127.0.0.1', '2026-03-09 07:31:00'),
(5, 3, 'ABSENSI_MASUK', 'Karyawan melakukan absensi', '127.0.0.1', '2026-03-09 07:58:00'),
(6, 1, 'VERIFIKASI_ABSENSI', 'Admin verifikasi absensi', '127.0.0.1', '2026-03-09 16:20:00'),
(7, 6, 'SUBMIT_IZIN', 'Pengajuan izin', '127.0.0.1', '2026-03-10 06:40:00'),
(8, 1, 'PROSES_IZIN', 'Admin proses izin', '127.0.0.1', '2026-03-10 10:00:00'),
(9, 5, 'KIRIM_FEEDBACK', 'Kirim feedback', '127.0.0.1', '2026-03-10 09:45:00'),
(10, 1, 'EXPORT_LAPORAN', 'Export laporan absensi', '127.0.0.1', '2026-03-10 13:10:00');
