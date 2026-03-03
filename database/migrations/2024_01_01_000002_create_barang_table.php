<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('nama_barang');
            $table->string('kategori')->nullable();
            $table->string('satuan')->default('pcs')->comment('pcs, kg, liter, dll');
            $table->integer('stok')->default(0);
            $table->integer('stok_minimal')->default(5)->comment('Batas minimum stok untuk notifikasi');
            $table->decimal('harga_satuan', 15, 2)->default(0)->comment('Harga per satuan');
            $table->string('lokasi_penyimpanan')->nullable()->comment('Rak, gudang, dll');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable()->comment('Path foto barang');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
