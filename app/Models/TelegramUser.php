<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'role',
        'is_active',
        'phone_number',
        'session_state',
        'session_data',
        'last_active_at',
    ];

    protected $casts = [
        'session_data'   => 'array',
        'is_active'      => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class);
    }

    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return in_array($this->role, ['admin', 'operator']);
    }

    public function clearSession(): void
    {
        $this->update([
            'session_state' => null,
            'session_data'  => null,
        ]);
    }

    public function setSession(string $state, array $data = []): void
    {
        $this->update([
            'session_state' => $state,
            'session_data'  => $data,
        ]);
    }
}
