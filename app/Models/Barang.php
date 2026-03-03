<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'stok',
        'stok_minimal',
        'harga_satuan',
        'lokasi_penyimpanan',
        'deskripsi',
        'foto',
        'is_active',
    ];

    protected $casts = [
        'stok'         => 'integer',
        'stok_minimal' => 'integer',
        'harga_satuan' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function barangMasuk()
    {
        return $this->hasMany(BarangMasuk::class);
    }

    public function barangKeluar()
    {
        return $this->hasMany(BarangKeluar::class);
    }

    public function isStokRendah(): bool
    {
        return $this->stok <= $this->stok_minimal;
    }

    public function getStatusStokAttribute(): string
    {
        if ($this->stok === 0) {
            return '🔴 Habis';
        } elseif ($this->stok <= $this->stok_minimal) {
            return '🟡 Menipis';
        } else {
            return '🟢 Aman';
        }
    }

    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto) {
            return asset('storage/' . $this->foto);
        }
        return null;
    }

    public static function generateKode(): string
    {
        $lastBarang = static::orderBy('id', 'desc')->first();
        $lastId     = $lastBarang ? $lastBarang->id + 1 : 1;
        return 'BRG-' . str_pad($lastId, 5, '0', STR_PAD_LEFT);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
