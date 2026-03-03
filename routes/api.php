<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ─── Telegram Webhook ───────────────────────────────────────────────────────
// Route webhook - tanpa CSRF karena dipanggil oleh server Telegram
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])
    ->name('telegram.webhook');

// ─── Management Routes (untuk developer) ────────────────────────────────────
Route::prefix('telegram')->name('telegram.')->group(function () {
    Route::get('/set-webhook', [TelegramController::class, 'setWebhook'])->name('set-webhook');
    Route::get('/delete-webhook', [TelegramController::class, 'deleteWebhook'])->name('delete-webhook');
    Route::get('/webhook-info', [TelegramController::class, 'webhookInfo'])->name('webhook-info');
});
