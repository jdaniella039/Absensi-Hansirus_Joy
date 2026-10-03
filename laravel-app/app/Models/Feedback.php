<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model feedbacks: menyimpan kritik, saran, atau laporan masalah dari karyawan.
class Feedback extends Model
{
    // Nama tabel eksplisit karena bentuk pluralnya tidak mengikuti konvensi umum bahasa Inggris.
    protected $table = 'feedbacks';

    // Kolom yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'user_id',
        'category',
        'message',
    ];

    // Casting timestamp agar otomatis menjadi objek Carbon.
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relasi: feedback dikirim oleh satu user/karyawan.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
