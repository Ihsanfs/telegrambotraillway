<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_masuk', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique()->comment('Nomor transaksi unik');
            $table->foreignId('barang_id')->constrained('barang')->onDelete('restrict');
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->onDelete('set null');
            $table->integer('quantity')->comment('Jumlah barang masuk');
            $table->decimal('harga_satuan', 15, 2)->default(0)->comment('Harga beli per satuan');
            $table->decimal('total_harga', 15, 2)->default(0)->comment('Total harga = quantity * harga_satuan');
            $table->string('supplier')->nullable()->comment('Nama supplier/pemasok');
            $table->string('no_surat_jalan')->nullable()->comment('Nomor surat jalan');
            $table->date('tanggal')->comment('Tanggal barang masuk');
            $table->string('foto')->nullable()->comment('Path foto struk/barang');
            $table->text('keterangan')->nullable()->comment('Catatan tambahan');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_masuk');
    }
};
