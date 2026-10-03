<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration untuk tabel audit log aktivitas.
return new class extends Migration
{
    // Membuat tabel audit_logs.
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // User pelaku aktivitas; nullable untuk aktivitas sistem.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('description', 255)->nullable();
            $table->string('ip_address', 60)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    // Menghapus tabel audit_logs saat rollback.
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
