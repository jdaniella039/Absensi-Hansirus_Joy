<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model audit_logs: menyimpan jejak aktivitas penting user/admin.
class AuditLog extends Model
{
    // Kolom yang boleh diisi melalui mass assignment.
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    // Casting timestamp agar otomatis menjadi objek Carbon.
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relasi: audit log bisa terkait ke satu user, atau null untuk aksi sistem.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
