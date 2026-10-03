<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Model users: dipakai untuk login, role admin/karyawan, dan relasi data user.
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi mass assignment lewat create() atau update().
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'position',
    ];

    /**
     * Kolom yang disembunyikan saat model diubah ke array/JSON.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Casting tipe data otomatis untuk atribut tertentu.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relasi: satu user bisa punya banyak jadwal kerja.
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // Relasi: satu user bisa punya banyak log absensi.
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    // Relasi: satu user bisa punya banyak pengajuan izin.
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // Relasi: satu user bisa punya banyak feedback.
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    // Helper untuk mengecek apakah user berperan sebagai admin.
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
