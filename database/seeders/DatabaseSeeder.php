<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\TelegramUser;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tambah beberapa barang contoh
        $barangs = [
            ['kode_barang' => 'BRG-00001', 'nama_barang' => 'Kertas A4 80gr', 'kategori' => 'Alat Tulis', 'satuan' => 'rim', 'stok' => 50, 'stok_minimal' => 10, 'harga_satuan' => 55000, 'lokasi_penyimpanan' => 'Rak 1'],
            ['kode_barang' => 'BRG-00002', 'nama_barang' => 'Pulpen Pilot', 'kategori' => 'Alat Tulis', 'satuan' => 'pcs', 'stok' => 200, 'stok_minimal' => 30, 'harga_satuan' => 8500, 'lokasi_penyimpanan' => 'Rak 1'],
            ['kode_barang' => 'BRG-00003', 'nama_barang' => 'Tinta Printer Hitam', 'kategori' => 'Elektronik', 'satuan' => 'botol', 'stok' => 8, 'stok_minimal' => 5, 'harga_satuan' => 85000, 'lokasi_penyimpanan' => 'Lemari A'],
            ['kode_barang' => 'BRG-00004', 'nama_barang' => 'Staples No.3', 'kategori' => 'Alat Tulis', 'satuan' => 'kotak', 'stok' => 0, 'stok_minimal' => 5, 'harga_satuan' => 12000, 'lokasi_penyimpanan' => 'Rak 2'],
            ['kode_barang' => 'BRG-00005', 'nama_barang' => 'Spidol Whiteboard', 'kategori' => 'Alat Tulis', 'satuan' => 'pcs', 'stok' => 15, 'stok_minimal' => 10, 'harga_satuan' => 15000, 'lokasi_penyimpanan' => 'Rak 2'],
            ['kode_barang' => 'BRG-00006', 'nama_barang' => 'Amplop Coklat A4', 'kategori' => 'Alat Tulis', 'satuan' => 'pak', 'stok' => 25, 'stok_minimal' => 5, 'harga_satuan' => 22000, 'lokasi_penyimpanan' => 'Rak 3'],
            ['kode_barang' => 'BRG-00007', 'nama_barang' => 'Hand Sanitizer 500ml', 'kategori' => 'Perlengkapan', 'satuan' => 'botol', 'stok' => 12, 'stok_minimal' => 5, 'harga_satuan' => 45000, 'lokasi_penyimpanan' => 'Gudang B'],
            ['kode_barang' => 'BRG-00008', 'nama_barang' => 'Tissu Kantor', 'kategori' => 'Perlengkapan', 'satuan' => 'pak', 'stok' => 3, 'stok_minimal' => 5, 'harga_satuan' => 18000, 'lokasi_penyimpanan' => 'Gudang B'],
        ];

        foreach ($barangs as $barang) {
            Barang::updateOrCreate(['kode_barang' => $barang['kode_barang']], $barang);
        }

        $this->command->info('✅ Data barang contoh berhasil ditambahkan!');
    }
}
