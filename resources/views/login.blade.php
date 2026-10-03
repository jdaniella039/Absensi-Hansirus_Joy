<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Pengaturan karakter dan viewport agar halaman responsif di mobile. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Judul yang tampil di tab browser. -->
    <title>Portal Absensi - PT. Hansirus Agro Andalan</title>

    <!-- Memuat Bootstrap dari CDN untuk styling cepat. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS sederhana khusus halaman login ini. -->
    <style>
        body { background-color: #f8f9fa; }
        .login-container { margin-top: 100px; max-width: 400px; }
    </style>
</head>
<body>
    <!-- Container utama form login. -->
    <div class="container login-container bg-white p-4 shadow-sm rounded">
        <!-- Judul identitas aplikasi. -->
        <h3 class="text-center mb-4">PORTAL ABSENSI KARYAWAN</h3>
        <p class="text-center text-muted small">PT. Hansirus Agro Andalan</p>
        
        <!-- Form login tampilan saja; action/backend belum dihubungkan di file ini. -->
        <form>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" placeholder="Masukkan Username">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" placeholder="Masukkan Password">
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">LOGIN</button>
            </div>
            <!-- Link tambahan untuk flow lupa password. -->
            <div class="text-center">
                <a href="#" class="text-decoration-none small">Lupa Kata Sandi?</a>
            </div>
            <hr>
            <!-- Tombol tambahan untuk flow registrasi akun baru. -->
            <div class="d-grid">
                <button class="btn btn-outline-secondary btn-sm">Buat Akun Baru</button>
            </div>
        </form>
    </div>
</body>
</html>
