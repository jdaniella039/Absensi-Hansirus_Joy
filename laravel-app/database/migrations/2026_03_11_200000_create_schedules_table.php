<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration untuk tabel jadwal kerja karyawan.
return new class extends Migration
{
    // Membuat tabel schedules.
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            // Karyawan pemilik jadwal; ikut terhapus jika user dihapus.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location', 120)->nullable();
            $table->string('notes', 255)->nullable();
            // Waktu saat karyawan mengonfirmasi bahwa jadwal sudah dilihat.
            $table->dateTime('acknowledged_at')->nullable();
            // Admin yang membuat jadwal; dibuat null jika admin dihapus.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Satu karyawan hanya boleh punya satu jadwal per tanggal.
            $table->unique(['user_id', 'work_date']);
        });
    }

    // Menghapus tabel schedules saat rollback.
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
