<?php
// Script untuk upgrade user menjadi admin
// Jalankan: php upgrade_admin.php [telegram_id]
// Contoh  : php upgrade_admin.php 123456789
// Tanpa ID : upgrade SEMUA user jadi admin

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TelegramUser;

$targetId = $argv[1] ?? null;

if ($targetId) {
    // Upgrade user tertentu
    $user = TelegramUser::where('telegram_id', $targetId)->first();
    if (!$user) {
        echo "❌ User dengan Telegram ID {$targetId} tidak ditemukan.\n";
        echo "Pastikan Anda sudah ketik /start di bot terlebih dahulu.\n";
        exit(1);
    }
    $oldRole = $user->role;
    $user->update(['role' => 'admin', 'is_active' => true]);
    echo "✅ User @{$user->username} (ID: {$targetId}) berhasil di-upgrade!\n";
    echo "   Role: {$oldRole} --> admin\n";
} else {
    // Upgrade semua user
    $users = TelegramUser::all();
    if ($users->isEmpty()) {
        echo "❌ Belum ada user. Ketik /start di bot dulu.\n";
        exit(1);
    }
    foreach ($users as $user) {
        $oldRole = $user->role;
        $user->update(['role' => 'admin', 'is_active' => true]);
        echo "✅ @{$user->username} (ID: {$user->telegram_id}): {$oldRole} --> admin\n";
    }
}

echo "\n🎉 Selesai! Coba ketik /start lagi di bot Telegram.\n";
