<?php
// Script sementara untuk melihat dan upgrade role user Telegram
// Jalankan: php artisan_helper.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TelegramUser;

echo "\n=== DAFTAR USER TELEGRAM ===\n\n";

$users = TelegramUser::all();

if ($users->isEmpty()) {
    echo "Belum ada user terdaftar. Silakan ketik /start di bot terlebih dahulu.\n";
    exit;
}

foreach ($users as $u) {
    echo "ID      : {$u->telegram_id}\n";
    echo "Username: @{$u->username}\n";
    echo "Nama    : {$u->first_name} {$u->last_name}\n";
    echo "Role    : {$u->role}\n";
    echo "Aktif   : " . ($u->is_active ? 'Ya' : 'Tidak') . "\n";
    echo "---\n";
}

echo "\nUntuk upgrade semua user jadi ADMIN, jalankan:\n";
echo "  php upgrade_admin.php\n\n";
