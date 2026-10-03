<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model attendance_logs: menyimpan absensi harian dan status verifikasinya.
class AttendanceLog extends Model
{
    // Kolom yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'user_id',
        'attendance_date',
        'check_in',
        'check_in_photo',
        'break_start',
        'break_start_photo',
        'break_end',
        'break_end_photo',
        'check_out',
        'check_out_photo',
        'status',
        'verification_note',
        'verified_by',
        'verified_at',
    ];

    // Casting tanggal/jam agar otomatis menjadi objek Carbon.
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'break_start' => 'datetime',
            'break_end' => 'datetime',
            'check_out' => 'datetime',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relasi: absensi dimiliki oleh satu user/karyawan.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: absensi diverifikasi oleh satu user admin.
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
