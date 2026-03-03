<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected TelegramBotService $bot;

    public function __construct(TelegramBotService $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Endpoint webhook yang dipanggil oleh Telegram
     */
    public function webhook(Request $request): Response
    {
        try {
            $update = $request->all();
            Log::info('Telegram Update:', $update);
            $this->bot->handleUpdate($update);
        } catch (\Exception $e) {
            Log::error('Telegram Webhook Error: ' . $e->getMessage());
        }

        return response('OK', 200);
    }

    /**
     * Set / register webhook ke Telegram API
     */
    public function setWebhook(): \Illuminate\Http\JsonResponse
    {
        $token   = config('telegram.bot_token');
        $url     = config('telegram.webhook_url');
        $apiUrl  = "https://api.telegram.org/bot{$token}/setWebhook";

        $response = \Illuminate\Support\Facades\Http::post($apiUrl, [
            'url'             => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);

        return response()->json($response->json());
    }

    /**
     * Hapus webhook
     */
    public function deleteWebhook(): \Illuminate\Http\JsonResponse
    {
        $token    = config('telegram.bot_token');
        $response = \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$token}/deleteWebhook"
        );

        return response()->json($response->json());
    }

    /**
     * Info webhook saat ini
     */
    public function webhookInfo(): \Illuminate\Http\JsonResponse
    {
        $token    = config('telegram.bot_token');
        $response = \Illuminate\Support\Facades\Http::get(
            "https://api.telegram.org/bot{$token}/getWebhookInfo"
        );

        return response()->json($response->json());
    }
}
