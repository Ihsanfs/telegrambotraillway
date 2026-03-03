<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarangRusak extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang_rusak';

    protected $fillable = [
        'no_transaksi',
        'barang_id',
        'telegram_user_id',
        'quantity',
        'foto',
        'alasan',
        'tanggal',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'tanggal'  => 'date',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public static function generateNoTransaksi(): string
    {
        $date     = now()->format('Ymd');
        $lastData = static::withTrashed()->whereDate('created_at', today())->count() + 1;
        return 'BR-' . $date . '-' . str_pad($lastData, 4, '0', STR_PAD_LEFT);
    }

    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto) {
            return asset('storage/' . $this->foto);
        }
        return null;
    }
}
