<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique()->comment('Nomor transaksi unik');
            $table->foreignId('barang_id')->constrained('barang')->onDelete('restrict');
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->onDelete('set null');
            $table->integer('quantity')->comment('Jumlah barang keluar');
            $table->decimal('harga_satuan', 15, 2)->default(0)->comment('Harga jual per satuan');
            $table->decimal('total_harga', 15, 2)->default(0)->comment('Total harga = quantity * harga_satuan');
            $table->string('penerima')->nullable()->comment('Nama penerima barang');
            $table->string('divisi_penerima')->nullable()->comment('Divisi/bagian penerima');
            $table->string('no_permintaan')->nullable()->comment('Nomor surat permintaan');
            $table->date('tanggal')->comment('Tanggal barang keluar');
            $table->string('foto')->nullable()->comment('Path foto bukti pengambilan');
            $table->string('foto_tanda_terima')->nullable()->comment('Path foto tanda terima');
            $table->text('keterangan')->nullable()->comment('Catatan tambahan');
            $table->enum('keperluan', ['operasional', 'proyek', 'maintenance', 'lainnya'])->default('operasional');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('catatan_approval')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_keluar');
    }
};
