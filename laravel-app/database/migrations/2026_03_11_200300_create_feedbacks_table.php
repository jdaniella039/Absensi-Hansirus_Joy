<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration untuk tabel feedback karyawan.
return new class extends Migration
{
    // Membuat tabel feedbacks.
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            // Karyawan yang mengirim feedback.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('category', ['kritik', 'saran', 'masalah'])->default('saran');
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    // Menghapus tabel feedbacks saat rollback.
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
