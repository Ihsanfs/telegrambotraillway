<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Token
    |--------------------------------------------------------------------------
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    | URL ini harus HTTPS dan dapat diakses oleh server Telegram
    | Contoh: https://yourapp.ngrok.io/api/telegram/webhook
    |--------------------------------------------------------------------------
    */
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin Telegram IDs
    | Daftar Telegram ID yang akan dijadikan admin saat pertama kali /start
    |--------------------------------------------------------------------------
    */
    'admin_ids' => explode(',', env('TELEGRAM_ADMIN_IDS', '')),
];
