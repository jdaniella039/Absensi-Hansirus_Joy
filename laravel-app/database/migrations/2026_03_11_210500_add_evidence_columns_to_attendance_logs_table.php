<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('check_in_photo')->nullable()->after('check_in');
            $table->string('break_start_photo')->nullable()->after('break_start');
            $table->string('break_end_photo')->nullable()->after('break_end');
            $table->string('check_out_photo')->nullable()->after('check_out');
        });
    }

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
