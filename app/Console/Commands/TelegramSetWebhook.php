<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhook extends Command
{
    protected $signature   = 'telegram:set-webhook {url? : URL webhook HTTPS}';
    protected $description = 'Register/Set webhook URL ke Telegram API';

    public function handle(): void
    {
        $url   = $this->argument('url') ?: config('telegram.webhook_url');
        $token = config('telegram.bot_token');

        if (empty($url)) {
            $this->error('❌ Webhook URL tidak ditemukan. Set TELEGRAM_WEBHOOK_URL di .env atau berikan sebagai argumen.');
            return;
        }

        if (empty($token)) {
            $this->error('❌ Bot token tidak ditemukan. Set TELEGRAM_BOT_TOKEN di .env');
            return;
        }

        $this->info("🔗 Mendaftarkan webhook: {$url}");

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url'             => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);

        $data = $response->json();

        if ($data['ok'] ?? false) {
            $this->info('✅ Webhook berhasil didaftarkan!');
            $this->line('   ' . ($data['description'] ?? ''));
        } else {
            $this->error('❌ Gagal mendaftarkan webhook:');
            $this->error('   ' . ($data['description'] ?? json_encode($data)));
        }

        // Tampilkan info webhook saat ini
        $infoResponse = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
        $info         = $infoResponse->json()['result'] ?? [];

        $this->newLine();
        $this->info('📊 Info Webhook Saat Ini:');
        $this->table(['Field', 'Value'], [
            ['URL', $info['url'] ?? '-'],
            ['Has Custom Certificate', $info['has_custom_certificate'] ? 'Ya' : 'Tidak'],
            ['Pending Updates', $info['pending_update_count'] ?? 0],
            ['Last Error', $info['last_error_message'] ?? 'Tidak ada'],
        ]);
    }
}
