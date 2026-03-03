<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarangKeluar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang_keluar';

    protected $fillable = [
        'no_transaksi',
        'barang_id',
        'barang_masuk_id',
        'telegram_user_id',
        'quantity',
        'harga_satuan',
        'total_harga',
        'penerima',
        'divisi_penerima',
        'no_permintaan',
        'tanggal',
        'foto',
        'foto_tanda_terima',
        'keterangan',
        'keperluan',
        'status',
        'catatan_approval',
        'approved_at',
        'updated_by_id',
    ];

    public function updater()
    {
        return $this->belongsTo(TelegramUser::class, 'updated_by_id');
    }

    protected $casts = [
        'quantity'     => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_harga'  => 'decimal:2',
        'tanggal'      => 'date',
        'approved_at'  => 'datetime',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public static function generateNoTransaksi(): string
    {
        $date     = now()->format('Ymd');
        $lastData = static::withTrashed()->whereDate('created_at', today())->count() + 1;
        return 'BK-' . $date . '-' . str_pad($lastData, 4, '0', STR_PAD_LEFT);
    }

    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto) {
            return asset('storage/' . $this->foto);
        }
        return null;
    }
}
