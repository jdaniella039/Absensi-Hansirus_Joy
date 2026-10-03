<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model leave_requests: menyimpan pengajuan izin/sakit/cuti karyawan.
class LeaveRequest extends Model
{
    // Kolom yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'user_id',
        'leave_date',
        'type',
        'reason',
        'evidence',
        'status',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    // Casting tanggal proses agar otomatis menjadi objek Carbon.
    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relasi: pengajuan izin dimiliki oleh satu user/karyawan.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: pengajuan izin diproses oleh satu user admin.
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
