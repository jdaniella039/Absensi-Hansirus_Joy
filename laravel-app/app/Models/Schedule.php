<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model schedules: menyimpan jadwal kerja karyawan.
class Schedule extends Model
{
    // Kolom yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
        'location',
        'notes',
        'acknowledged_at',
        'created_by',
    ];

    // Casting tanggal agar mudah dipakai di view/controller.
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'acknowledged_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relasi: jadwal dimiliki oleh satu user/karyawan.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: jadwal dibuat oleh satu user admin.
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
