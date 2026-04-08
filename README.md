# Sistem Absensi PT. Hansirus Agro Andalan

Implementasi awal sistem absensi berbasis web sesuai proposal TA.

## Fitur yang sudah dibuat

- Autentikasi login + RBAC (`admin`, `karyawan`)
- Absensi karyawan: masuk, mulai istirahat, selesai istirahat, pulang
- Verifikasi absensi oleh admin (approve/reject + catatan)
- Manajemen jadwal kerja oleh admin (tambah, edit, hapus)
- Lihat dan konfirmasi jadwal kerja oleh karyawan
- Pengajuan izin karyawan
- Pengajuan izin karyawan + upload bukti file
- Persetujuan izin oleh admin
- Pengelolaan profil pengguna
- Manajemen data karyawan oleh admin (tambah, edit, hapus, reset password)
- Feedback karyawan
- Inbox feedback untuk admin
- Audit log aktivitas
- Laporan absensi (periode hari/minggu/bulan + ringkasan hadir/izin/terlambat/absen) + export CSV
- Export audit log CSV

## Persiapan

1. Pastikan PHP 8.1+ dan MySQL aktif.
2. Buat database MySQL:

```sql
CREATE DATABASE absensi_hansirus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Buat file `.env` dari `.env.example`, lalu isi koneksi database Anda:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_hansirus
DB_USERNAME=root
DB_PASSWORD=
```

Jika Anda memakai Herd, XAMPP, atau Laragon dengan port/user/password berbeda, sesuaikan nilainya di file `.env`.

## Menjalankan

```bash
php -S 127.0.0.1:8000
```

Buka: `http://127.0.0.1:8000`

## Akun awal

- Admin: `admin / admin123`
- Karyawan: `karyawan / karyawan123`

## Data Dummy Demo

Jika ingin menyiapkan data demo sidang dengan cepat, import file [storage/demo-seed.sql](/c:/DATA%20JOY/Tugas/TA/absensi-hansirus/storage/demo-seed.sql) ke database `absensi_hansirus`.

Akun demo tambahan setelah import:

- Admin: `admin / admin123`
- Karyawan: `budi / demo123`
- Karyawan: `siti / demo123`
- Karyawan: `andi / demo123`
- Karyawan: `rina / demo123`
- Karyawan: `dewi / demo123`

Import di HeidiSQL:

1. Pilih database `absensi_hansirus`
2. Buka file `storage/demo-seed.sql`
3. Jalankan semua query

Catatan:

- Script ini akan menghapus data lama lalu menggantinya dengan data demo baru.
- Data demo mencakup jadwal, absensi dengan status `approved/pending/rejected`, izin, feedback, dan audit log.

## Catatan

- Tabel database dibuat otomatis saat aplikasi pertama kali diakses.
- Seed akun awal dibuat otomatis jika tabel `users` masih kosong.
- Implementasi ini memakai PHP native agar cepat dieksekusi dari struktur repo saat ini (repo belum berisi struktur Laravel lengkap).
- Konfigurasi database dibaca dari file `.env` jika tersedia, lalu fallback ke environment variable/default.
- Validasi dasar sudah mencakup urutan absensi dan pencegahan jadwal ganda pada tanggal yang sama untuk karyawan yang sama.
- Bukti izin disimpan ke folder `storage/leave_evidence` dengan format file `JPG`, `JPEG`, `PNG`, atau `PDF` maksimal `2 MB`.
- Bukti izin gambar ditampilkan sebagai thumbnail kecil, file PDF diberi tombol buka, dan saat admin menghapus data izin file buktinya ikut dihapus.
