<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration untuk tabel absensi harian.
return new class extends Migration
{
    // Membuat tabel attendance_logs.
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            // Karyawan pemilik absensi.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            // Waktu setiap tahap absensi.
            $table->dateTime('check_in')->nullable();
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->dateTime('check_out')->nullable();
            // Status verifikasi admin.
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('verification_note', 255)->nullable();
            // Admin yang memverifikasi absensi.
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Satu karyawan hanya boleh punya satu record absensi per tanggal.
            $table->unique(['user_id', 'attendance_date']);
        });
    }

    // Menghapus tabel attendance_logs saat rollback.
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
