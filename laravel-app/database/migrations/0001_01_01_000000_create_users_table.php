<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration awal untuk tabel user, reset password, dan session Laravel.
return new class extends Migration
{
    /**
     * Membuat tabel yang dibutuhkan autentikasi.
     */
    public function up(): void
    {
        // Tabel users menyimpan akun admin dan karyawan.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('username', 60)->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'karyawan']);
            $table->string('position', 100)->nullable();
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // Tabel token reset password dengan username sebagai primary key.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('username')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Tabel sessions dipakai Laravel saat session driver database digunakan.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Menghapus tabel saat migration di-rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
