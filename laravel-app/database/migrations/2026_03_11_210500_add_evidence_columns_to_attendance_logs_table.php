<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration tambahan untuk menyimpan foto bukti di tiap tahap absensi.
return new class extends Migration
{
    // Menambahkan kolom foto ke tabel attendance_logs.
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            // Masing-masing tahap absensi punya file bukti sendiri.
            $table->string('check_in_photo')->nullable()->after('check_in');
            $table->string('break_start_photo')->nullable()->after('break_start');
            $table->string('break_end_photo')->nullable()->after('break_end');
            $table->string('check_out_photo')->nullable()->after('check_out');
        });
    }

    // Menghapus kolom foto saat rollback.
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_photo',
                'break_start_photo',
                'break_end_photo',
                'check_out_photo',
            ]);
        });
    }
};
