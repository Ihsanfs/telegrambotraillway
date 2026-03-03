<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_rusak', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->foreignId('barang_id')->constrained('barang')->onDelete('restrict');
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->onDelete('set null');
            $table->integer('quantity');
            $table->string('foto')->nullable();
            $table->text('alasan')->nullable();
            $table->date('tanggal');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->foreignId('updated_by_id')->nullable()->after('telegram_user_id')->constrained('telegram_users')->onDelete('set null');
        });

        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->foreignId('updated_by_id')->nullable()->after('telegram_user_id')->constrained('telegram_users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn('updated_by_id');
        });
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn('updated_by_id');
        });
        Schema::dropIfExists('barang_rusak');
    }
};
