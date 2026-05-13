# Sistem Absensi PT. Hansirus Agro Andalan

Sistem absensi karyawan berbasis web untuk PT. Hansirus Agro Andalan. Aplikasi ini memakai PHP native dan MariaDB.

## Fitur Utama

- Login dengan role `admin` dan `karyawan`
- Absensi karyawan: masuk, mulai istirahat, selesai istirahat, dan pulang
- Upload foto bukti absensi dari kamera atau galeri
- Verifikasi absensi oleh admin
- Manajemen jadwal kerja
- Konfirmasi jadwal oleh karyawan
- Pengajuan izin dengan upload bukti
- Persetujuan izin oleh admin
- Manajemen data karyawan, termasuk import CSV dan reset password
- Pengelolaan profil pengguna
- Feedback karyawan dan inbox feedback admin
- Audit log aktivitas
- Laporan absensi dan export CSV
- Export audit log CSV

## Struktur Singkat

```text
.
|-- app/                  # Logic aplikasi PHP native
|-- resources/            # Resource/view tambahan
|-- routes/               # Route sederhana
|-- storage/              # File demo, template CSV, upload, dan asset
|-- index.php             # Entry point aplikasi utama
`-- .env.example          # Contoh konfigurasi database
```

## Persiapan

1. Pastikan PHP 8.1+ dan MariaDB aktif.
2. Buat database MariaDB:

```sql
CREATE DATABASE absensi_hansirus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Salin `.env.example` menjadi `.env`.
4. Isi koneksi database di `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_hansirus
DB_USERNAME=root
DB_PASSWORD=
```

Jika memakai XAMPP, Laragon, atau Herd dengan port/user/password berbeda, sesuaikan nilai di `.env`.

## Menjalankan Aplikasi

Untuk akses dari laptop sendiri:

```bash
php -S 127.0.0.1:8000
```

Buka:

```text
http://127.0.0.1:8000
```

Untuk akses dari HP dalam jaringan WiFi yang sama:

```bash
php -S 0.0.0.0:8000
```

Lalu buka dari HP memakai IP laptop, contoh:

```text
http://192.168.1.3:8000
```

Catatan: `0.0.0.0` dipakai untuk menjalankan server, bukan untuk dibuka langsung di browser.

## Akun Awal

Akun awal otomatis dibuat saat aplikasi pertama kali mengakses database.

- Admin: `admin / admin123`
- Karyawan: `karyawan / karyawan123`

## Data Karyawan

Data karyawan disimpan di tabel `users` pada database MariaDB `absensi_hansirus`. Data ini tidak expired dan tidak perlu diperbarui setiap bulan.

Data dapat hilang jika:

- Database MariaDB dihapus/reset
- File `.env` diarahkan ke database lain
- Karyawan dihapus lewat tombol `Hapus`
- Script demo seed dijalankan ulang

Jadwal kerja berbeda dengan data karyawan. Jadwal perlu diisi sesuai tanggal atau periode kerja yang dibutuhkan.

## Import Karyawan CSV

Admin dapat menambahkan banyak karyawan sekaligus melalui halaman `Karyawan`.

Langkah umum:

1. Buka halaman `Karyawan`.
2. Klik `Download Template CSV`.
3. Isi data karyawan sesuai format template.
4. Upload file CSV lewat form `Import Karyawan dari CSV`.

Kolom yang dipakai:

- `nama`
- `username`
- `jabatan`
- `password`

Import CSV hanya menambah data baru. Jika ada username yang sudah dipakai, import dibatalkan agar data lama tidak tertimpa.

## Catatan Teknis

- Tabel database dibuat otomatis saat aplikasi pertama kali dibuka.
- Konfigurasi database dibaca dari `.env`.
- Bukti absensi disimpan di `storage/attendance_evidence`.
- Bukti izin disimpan di `storage/leave_evidence`.
- Format bukti izin: `JPG`, `JPEG`, `PNG`, atau `PDF`, maksimal `2 MB`.
- Format bukti absensi: `JPG`, `JPEG`, atau `PNG`, maksimal `2 MB`.
- File log dan cache tidak perlu masuk Git karena bisa dibuat ulang.

## Backup Database

Disarankan backup database sebelum demo, sebelum import besar, atau sebelum mengubah data dalam jumlah banyak.

Contoh backup:

```bash
mariadb-dump -u root absensi_hansirus > backup_absensi_hansirus.sql
```

Jika MariaDB memakai password:

```bash
mariadb-dump -u root -p absensi_hansirus > backup_absensi_hansirus.sql
```
