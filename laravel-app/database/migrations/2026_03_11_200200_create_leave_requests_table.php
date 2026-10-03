<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration untuk tabel pengajuan izin/sakit/cuti.
return new class extends Migration
{
    // Membuat tabel leave_requests.
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            // Karyawan yang mengajukan izin.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('leave_date');
            $table->enum('type', ['izin', 'sakit', 'cuti']);
            $table->text('reason');
            // Path file bukti opsional.
            $table->string('evidence', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('admin_note', 255)->nullable();
            // Admin yang memproses pengajuan.
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    // Menghapus tabel leave_requests saat rollback.
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
