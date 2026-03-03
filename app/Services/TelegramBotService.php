<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\BarangRusak;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramBotService
{
    protected string $token;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token  = config('telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    // ═══════════════════════════════════════════════════════════
    //  API HELPERS
    // ═══════════════════════════════════════════════════════════

    public function sendMessage(int|string $chatId, string $text, array $extra = []): array
    {
        Log::debug("Bot sending message to {$chatId}: " . substr($text, 0, 50) . '...');
        $response = Http::withoutVerifying()->post("{$this->apiUrl}/sendMessage", array_merge([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra));
        
        $res = $response->json();
        if (!($res['ok'] ?? false)) {
            Log::error('Telegram sendMessage error: ' . json_encode($res));
        }
        
        return $res ?? [];
    }

    public function sendPhoto(int|string $chatId, string $photo, string $caption = '', array $extra = []): void
    {
        $request = Http::withoutVerifying();
        $params = array_merge([
            'chat_id'    => $chatId,
            'caption'    => $caption,
            'parse_mode' => 'HTML',
        ], $extra);

        // Jika photo adalah path lokal, upload filenya
        if (!str_starts_with($photo, 'http') && Storage::disk('public')->exists($photo)) {
            $response = $request->attach('photo', Storage::disk('public')->get($photo), basename($photo))
                                ->post("{$this->apiUrl}/sendPhoto", $params);
        } else {
            $params['photo'] = $photo;
            $response = $request->post("{$this->apiUrl}/sendPhoto", $params);
        }

        $res = $response->json();
        if (!($res['ok'] ?? false)) {
            Log::error('Telegram sendPhoto error: ' . json_encode($res));
        }
    }

    public function answerCallbackQuery(string $id, string $text = ''): void
    {
        Http::post("{$this->apiUrl}/answerCallbackQuery", [
            'callback_query_id' => $id,
            'text'              => $text,
        ]);
    }

    public function downloadAndSave(string $fileId, string $folder = 'telegram'): ?string
    {
        $res = Http::get("{$this->apiUrl}/getFile", ['file_id' => $fileId])->json();
        if (!($res['ok'] ?? false)) return null;

        $filePath = $res['result']['file_path'];
        $contents = Http::get("https://api.telegram.org/file/bot{$this->token}/{$filePath}")->body();
        $ext      = pathinfo($filePath, PATHINFO_EXTENSION);
        $name     = $folder . '/' . uniqid() . '.' . $ext;
        Storage::disk('public')->put($name, $contents);
        return $name;
    }

    // ═══════════════════════════════════════════════════════════
    //  KEYBOARD HELPERS
    // ═══════════════════════════════════════════════════════════

    protected function inlineKeyboard(array $buttons): array
    {
        return ['reply_markup' => json_encode(['inline_keyboard' => $buttons])];
    }

    protected function replyKeyboard(array $buttons, bool $oneTime = false): array
    {
        return ['reply_markup' => json_encode([
            'keyboard'          => $buttons,
            'resize_keyboard'   => true,
            'one_time_keyboard' => $oneTime,
        ])];
    }

    protected function removeKeyboard(): array
    {
        return ['reply_markup' => json_encode(['remove_keyboard' => true])];
    }

    protected function mainMenu(): array
    {
        return $this->replyKeyboard([
            [['text' => '📥 Barang Masuk'], ['text' => '📤 Barang Keluar']],
            [['text' => '�️ Barang Rusak'], ['text' => '�📊 Laporan']],
            [['text' => '❓ Bantuan']],
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  ENTRY POINT
    // ═══════════════════════════════════════════════════════════

    public function handleUpdate(array $update): void
    {
        Log::debug('Bot handleUpdate started', ['update_id' => $update['update_id'] ?? 'unknown']);
        try {
            if (isset($update['callback_query'])) {
                Log::debug('Processing callback_query');
                $this->handleCallback($update['callback_query']);
                return;
            }
            if (!isset($update['message'])) {
                Log::debug('No message in update');
                return;
            }

            $msg  = $update['message'];
            $from = $msg['from'];
            
            Log::debug('Processing message from: ' . ($from['id'] ?? 'unknown'));

            $user = TelegramUser::updateOrCreate(
                ['telegram_id' => $from['id']],
                [
                    'username'       => $from['username'] ?? null,
                    'first_name'     => $from['first_name'] ?? null,
                    'last_name'      => $from['last_name'] ?? null,
                    'last_active_at' => now(),
                ]
            );

            if (!$user->is_active) {
                Log::debug('User is inactive');
                $this->sendMessage($msg['chat']['id'], '⛔ Akun Anda dinonaktifkan.');
                return;
            }

            // Dalam sesi multi-step?
            if ($user->session_state) {
                Log::debug('Handling session: ' . $user->session_state);
                $this->handleSession($msg, $user);
                return;
            }

            if (isset($msg['text'])) {
                Log::debug('Handling text: ' . $msg['text']);
                $this->handleText($msg, $user);
            }
        } catch (\Throwable $e) {
            Log::error('TelegramBot Critical Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  TEKS COMMAND
    // ═══════════════════════════════════════════════════════════

    protected function handleText(array $msg, TelegramUser $user): void
    {
        $chatId = $msg['chat']['id'];
        $text   = trim($msg['text']);

        match (true) {
            $text === '/start' || $text === '/menu'                         => $this->cmdStart($chatId, $user),
            str_contains($text, 'Barang Masuk')                            => $this->startMasuk($chatId, $user),
            str_contains($text, 'Barang Keluar')                           => $this->startKeluar($chatId, $user),
            str_contains($text, 'Barang Rusak')                            => $this->startRusak($chatId, $user),
            str_contains($text, 'Laporan')                                 => $this->showLaporanMenu($chatId),
            str_contains($text, 'Bantuan')                                 => $this->cmdBantuan($chatId),
            default                                                        => $this->sendMessage($chatId,
                '❓ Ketik /menu atau pilih menu di bawah.', $this->mainMenu())
        };
    }

    protected function cmdStart(int $chatId, TelegramUser $user): void
    {
        $name = $user->first_name ?: ('@' . $user->username);
        $msg  = "👋 <b>Halo, {$name}!</b>\n\n";
        $msg .= "🤖 <b>Bot Kartu Stok Barang</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "Pilih menu di bawah ini:";
        $this->sendMessage($chatId, $msg, $this->mainMenu());
    }

    protected function cmdBantuan(int $chatId): void
    {
        $msg  = "❓ <b>CARA PAKAI BOT</b>\n\n";
        $msg .= "📥 <b>Barang Masuk</b>\n";
        $msg .= "   Ketik nama barang → pilih → isi qty → foto (opsional) → harga (opsional) → tanggal → selesai\n\n";
        $msg .= "📤 <b>Barang Keluar</b>\n";
        $msg .= "   Sama seperti barang masuk\n\n";
        $msg .= "📊 <b>Laporan</b>\n";
        $msg .= "   Lihat rekap harian, mingguan, atau bulanan\n\n";
        $msg .= "Ketik /batal untuk membatalkan proses yang sedang berjalan.";
        $this->sendMessage($chatId, $msg, $this->mainMenu());
    }

    // ═══════════════════════════════════════════════════════════
    //  SESI HANDLER
    // ═══════════════════════════════════════════════════════════

    protected function handleSession(array $msg, TelegramUser $user): void
    {
        $chatId = $msg['chat']['id'];
        $text   = $msg['text'] ?? null;

        // Batal kapan saja
        if ($text === '/batal' || ($text && str_contains($text, 'Batal'))) {
            $user->clearSession();
            $this->sendMessage($chatId, '❌ Dibatalkan.', $this->mainMenu());
            return;
        }

        $state = $user->session_state;

        if (str_starts_with($state, 'masuk_')) {
            $this->sessionMasuk($msg, $user, $state);
        } elseif (str_starts_with($state, 'keluar_')) {
            $this->sessionKeluar($msg, $user, $state);
        } elseif (str_starts_with($state, 'rusak_')) {
            $this->sessionRusak($msg, $user, $state);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  BARANG MASUK — FLOW
    // ═══════════════════════════════════════════════════════════
    //  masuk_cari → masuk_pilih → masuk_qty → masuk_foto
    //  → masuk_tanya_harga → masuk_harga → masuk_tanggal → SIMPAN
    // ═══════════════════════════════════════════════════════════

    protected function startMasuk(int $chatId, TelegramUser $user): void
    {
        $user->setSession('masuk_cari', []);
        $this->sendMessage($chatId,
            "📥 <b>BARANG MASUK</b>\n\nKetik nama atau kode barang yang masuk:\n(ketik /batal untuk membatalkan)",
            $this->replyKeyboard([[['text' => '❌ Batal']]])
        );
    }

    protected function sessionMasuk(array $msg, TelegramUser $user, string $state): void
    {
        $chatId = $msg['chat']['id'];
        $text   = $msg['text'] ?? null;
        $data   = $user->session_data ?? [];

        switch ($state) {

            // ── STEP 1: Cari barang ──────────────────────────────
            case 'masuk_cari':
                $results = Barang::where('nama_barang', 'like', "%{$text}%")
                    ->orWhere('kode_barang', $text)
                    ->where('is_active', true)
                    ->limit(8)->get();

                if ($results->isEmpty()) {
                    // Tidak ada di DB → tanya buat baru
                    $user->setSession('masuk_qty', array_merge($data, [
                        'nama_barang' => $text,
                        'barang_id'   => null,
                        'kode_barang' => null,
                    ]));
                    $this->sendMessage($chatId,
                        "ℹ️ Barang <b>\"{$text}\"</b> belum terdaftar.\nAkan dicatat sebagai barang baru.\n\n📦 Masukkan <b>quantity / jumlah</b>:",
                        $this->replyKeyboard([[['text' => '❌ Batal']]])
                    );
                    return;
                }

                if ($results->count() === 1) {
                    // Langsung pilih karena cuma 1 hasil
                    $b = $results->first();
                    $user->setSession('masuk_qty', array_merge($data, [
                        'barang_id'   => $b->id,
                        'nama_barang' => $b->nama_barang,
                        'kode_barang' => $b->kode_barang,
                    ]));
                    $this->sendMessage($chatId,
                        "✅ <b>{$b->nama_barang}</b>\n📦 Stok saat ini: <b>{$b->stok} {$b->satuan}</b>\n\nMasukkan <b>quantity / jumlah</b> yang masuk:",
                        $this->replyKeyboard([[['text' => '❌ Batal']]])
                    );
                    return;
                }

                // Multiple results → tampilkan inline keyboard pilihan
                $user->setSession('masuk_pilih', array_merge($data, ['keyword' => $text]));
                $buttons = [];
                foreach ($results as $b) {
                    $buttons[] = [['text' => "📦 {$b->nama_barang} (stok: {$b->stok})", 'callback_data' => "pilih_masuk_{$b->id}"]];
                }
                $buttons[] = [['text' => "➕ Buat baru: \"{$text}\"", 'callback_data' => "pilih_masuk_baru_{$text}"]];
                $this->sendMessage($chatId,
                    "🔍 Ditemukan <b>{$results->count()}</b> barang. Pilih salah satu:",
                    $this->inlineKeyboard($buttons)
                );
                break;

            // ── STEP 2: Quantity ─────────────────────────────────
            case 'masuk_qty':
                if (!is_numeric($text) || (int)$text <= 0) {
                    $this->sendMessage($chatId, '❌ Masukkan angka yang valid, contoh: <b>10</b>');
                    return;
                }
                $user->setSession('masuk_foto', array_merge($data, ['quantity' => (int)$text]));
                $this->sendMessage($chatId,
                    "📦 Quantity: <b>{$text}</b>\n\n📸 Kirim <b>foto barang/struk</b> (opsional):\n(Kirim foto langsung, atau lanjut <code>-</code> untuk lewati)",
                    $this->replyKeyboard([[['text' => '-']], [['text' => '❌ Batal']]])
                );
                break;

            // ── STEP 3: Foto ─────────────────────────────────────
            case 'masuk_foto':
                $fotoPath = null;
                if (isset($msg['photo'])) {
                    $bestPhoto = end($msg['photo']);
                    $fotoPath  = $this->downloadAndSave($bestPhoto['file_id'], 'barang_masuk');
                }
                // text "-" → lewati
                $user->setSession('masuk_tanya_harga', array_merge($data, ['foto' => $fotoPath]));
                $this->sendMessage($chatId,
                    "💰 Apakah Anda ingin menambahkan <b>harga</b>?",
                    $this->replyKeyboard([[['text' => '✅ Ya, tambah harga'], ['text' => '⏭️ Lewati']], [['text' => '❌ Batal']]])
                );
                break;

            // ── STEP 4: Tanya harga ──────────────────────────────
            case 'masuk_tanya_harga':
                if ($text === '✅ Ya, tambah harga') {
                    $user->setSession('masuk_harga', $data);
                    $this->sendMessage($chatId,
                        "💰 Masukkan <b>harga satuan</b> (Rp):\nContoh: <code>15000</code>",
                        $this->replyKeyboard([[['text' => '❌ Batal']]])
                    );
                } else {
                    // Lewati → lanjut ke tanggal
                    $user->setSession('masuk_tanggal', array_merge($data, ['harga_satuan' => 0]));
                    $this->tanyaTanggal($chatId);
                }
                break;

            // ── STEP 5: Harga ────────────────────────────────────
            case 'masuk_harga':
                $harga = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $text));
                if ($harga < 0) {
                    $this->sendMessage($chatId, '❌ Masukkan angka harga yang valid.');
                    return;
                }
                $user->setSession('masuk_tanggal', array_merge($data, ['harga_satuan' => $harga]));
                $this->tanyaTanggal($chatId);
                break;

            // ── STEP 6: Tanggal ──────────────────────────────────
            case 'masuk_tanggal':
                $tanggal = $this->parseTanggal($text);
                if ($tanggal === 'MANUAL_INPUT') {
                    $this->sendMessage($chatId,
                        "📅 Ketik tanggal dengan format: <code>DD-MM-YYYY</code>\nContoh: <code>" . now()->format('d-m-Y') . "</code>",
                        $this->replyKeyboard([[['text' => '❌ Batal']]])
                    );
                    return;
                }
                if (!$tanggal) {
                    $this->sendMessage($chatId,
                        "❌ Format tidak valid. Gunakan: <code>DD-MM-YYYY</code>\nContoh: <code>" . now()->format('d-m-Y') . "</code>"
                    );
                    return;
                }
                $user->setSession('masuk_konfirmasi', array_merge($data, ['tanggal' => $tanggal]));
                $this->tanyaKonfirmasiMasuk($chatId, array_merge($data, ['tanggal' => $tanggal]));
                break;

            case 'masuk_konfirmasi':
                if ($text === '✅ Konfirmasi & Simpan') {
                    $this->simpanMasuk($chatId, $user, $data);
                } else {
                    $this->sendMessage($chatId, '❓ Pilih menu di bawah untuk lanjut atau edit.', $this->mainMenu());
                }
                break;
        }
    }

    protected function simpanMasuk(int $chatId, TelegramUser $user, array $data): void
    {
        // Buat barang baru jika belum ada di DB
        if (empty($data['barang_id'])) {
            $barang = Barang::create([
                'kode_barang' => Barang::generateKode(),
                'nama_barang' => $data['nama_barang'],
                'satuan'      => 'pcs',
                'stok'        => 0,
                'stok_minimal'=> 5,
                'harga_satuan'=> $data['harga_satuan'] ?? 0,
                'is_active'   => true,
            ]);
        } else {
            $barang = Barang::find($data['barang_id']);
        }

        $qty        = $data['quantity'];
        $harga      = $data['harga_satuan'] ?? 0;
        $totalHarga = $harga * $qty;
        $noTrx      = BarangMasuk::generateNoTransaksi();

        BarangMasuk::create([
            'no_transaksi'     => $noTrx,
            'barang_id'        => $barang->id,
            'telegram_user_id' => $user->id,
            'quantity'         => $qty,
            'harga_satuan'     => $harga,
            'total_harga'      => $totalHarga,
            'tanggal'          => $data['tanggal'],
            'foto'             => $data['foto'] ?? null,
            'status'           => 'verified',
        ]);

        // Update stok
        $stokLama = $barang->stok;
        $barang->increment('stok', $qty);

        $user->clearSession();

        $tgl = \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y');
        $msg  = "✅ <b>BERHASIL DISIMPAN!</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📋 No: <code>{$noTrx}</code>\n";
        $msg .= "📦 Barang: <b>{$barang->nama_barang}</b>\n";
        $msg .= "🔢 Qty masuk: <b>+{$qty}</b>\n";
        if ($harga > 0) {
            $msg .= "💰 Harga: <b>Rp " . number_format($harga, 0, ',', '.') . "</b>\n";
            $msg .= "💵 Total: <b>Rp " . number_format($totalHarga, 0, ',', '.') . "</b>\n";
        }
        $msg .= "📅 Tanggal: <b>{$tgl}</b>\n";
        $msg .= "📸 Foto: " . ($data['foto'] ? '✅ Ada' : '—') . "\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "� Oleh: <b>" . ($user->first_name ?: $user->username) . "</b>\n";
        $msg .= "�📈 Stok: <b>{$stokLama}</b> → <b>{$barang->stok}</b>";

        $this->sendMessage($chatId, $msg, $this->mainMenu());
        $this->sendMessage($chatId, "✅ <b>Data Berhasil Disimpan!</b>");
    }

    protected function tanyaKonfirmasiMasuk(int $chatId, array $data): void
    {
        $harga = $data['harga_satuan'] ?? 0;
        $tgl   = \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y');
        
        $msg  = "🧐 <b>PERIKSA KEMBALI DATA ANDA</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📥 TIPE: BARANG MASUK\n";
        $msg .= "📦 Barang: <b>{$data['nama_barang']}</b>\n";
        $msg .= "🔢 Quantity: <b>{$data['quantity']}</b>\n";
        $msg .= "💰 Harga: <b>Rp " . number_format($harga, 0, ',', '.') . "</b>\n";
        $msg .= "📅 Tanggal: <b>{$tgl}</b>\n";
        $msg .= ($data['foto'] ?? null) ? "📸 Foto: Tersedia\n" : "📸 Foto: Tidak ada\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "Apakah data di atas sudah benar?";

        $this->sendMessage($chatId, $msg, $this->inlineKeyboard([
            [['text' => '✅ SIMPAN DATA', 'callback_data' => 'save_masuk']],
            [
                ['text' => '🔢 Edit Qty', 'callback_data' => 'edit_masuk_qty'],
                ['text' => '💰 Edit Harga', 'callback_data' => 'edit_masuk_harga'],
            ],
            [['text' => '📅 Edit Tanggal', 'callback_data' => 'edit_masuk_tanggal']],
            [['text' => '❌ BATALKAN', 'callback_data' => 'batal_masuk']],
        ]));
    }

    // ═══════════════════════════════════════════════════════════
    //  BARANG KELUAR — FLOW
    // ═══════════════════════════════════════════════════════════
    //  keluar_cari → keluar_pilih → keluar_qty → keluar_foto
    //  → keluar_tanya_harga → keluar_harga → keluar_tanggal → SIMPAN
    // ═══════════════════════════════════════════════════════════

    protected function startKeluar(int $chatId, TelegramUser $user): void
    {
        $user->setSession('keluar_tgl', []);
        $hari = now()->format('d-m-Y');
        $kemarin = now()->subDay()->format('d-m-Y');
        $this->sendMessage($chatId,
            "📤 <b>BARANG KELUAR</b>\n\nPilih tanggal <b>Barang Masuk</b> yang akan dikurangi stoknya:",
            $this->replyKeyboard([
                [['text' => "📅 Hari Ini ({$hari})"], ['text' => "📅 Kemarin ({$kemarin})"]],
                [['text' => '📝 Pilih Tanggal Lain']],
                [['text' => '❌ Batal']],
            ])
        );
    }

    protected function sessionKeluar(array $msg, TelegramUser $user, string $state): void
    {
        $chatId = $msg['chat']['id'];
        $text   = $msg['text'] ?? null;
        $data   = $user->session_data ?? [];

        switch ($state) {

            // STEP 1: Pilih Tanggal Barang Masuk
            case 'keluar_tgl':
                if (str_contains($text, 'Pilih Tanggal Lain')) {
                    $this->sendMessage($chatId, "📅 Ketik tanggal dengan format: <code>DD-MM-YYYY</code>\nContoh: <code>" . now()->format('d-m-Y') . "</code>", $this->replyKeyboard([[['text' => '❌ Batal']]]));
                    return;
                }
                
                $tanggal = $this->parseTanggal($text);
                if (!$tanggal) {
                    $this->sendMessage($chatId, "❌ Format tidak valid. Gunakan: <code>DD-MM-YYYY</code>");
                    return;
                }

                $masukList = BarangMasuk::with('barang')
                    ->where('tanggal', $tanggal)
                    ->get();

                if ($masukList->isEmpty()) {
                    $this->sendMessage($chatId, "📭 Tidak ada data barang masuk pada tanggal <b>" . \Carbon\Carbon::parse($tanggal)->format('d/m/Y') . "</b>.", $this->mainMenu());
                    $user->clearSession();
                    return;
                }

                $user->setSession('keluar_pilih_batch', array_merge($data, ['tanggal_masuk' => $tanggal]));
                
                $buttons = [];
                foreach ($masukList as $bm) {
                    $buttons[] = [['text' => "📦 {$bm->barang->nama_barang} (+{$bm->quantity})", 'callback_data' => "pilih_batch_{$bm->id}"]];
                }
                
                $this->sendMessage($chatId, 
                    "🔍 Ditemukan <b>{$masukList->count()}</b> transaksi masuk pada " . \Carbon\Carbon::parse($tanggal)->format('d/m/Y') . ".\n\nPilih barang yang ingin dikurangi stoknya:", 
                    $this->inlineKeyboard($buttons)
                );
                break;

            // STEP 2: Input Quantity (Setelah pilih batch via callback)
            case 'keluar_qty':
                if (!is_numeric($text) || (int)$text <= 0) {
                    $this->sendMessage($chatId, '❌ Masukkan angka yang valid, contoh: <b>5</b>');
                    return;
                }
                $qty = (int)$text;
                
                // Cek stok fisik barang
                if ($qty > ($data['stok'] ?? 0)) {
                    $this->sendMessage($chatId,
                        "❌ Qty melebihi stok barang!\nStok tersedia: <b>{$data['stok']}</b>\nMasukkan angka yang sesuai:"
                    );
                    return;
                }

                // Cek qty batch asal (opsional, tapi bagus untuk validasi konteks)
                if ($qty > ($data['batch_qty'] ?? 0)) {
                    $this->sendMessage($chatId, "⚠️ Jumlah keluar (<b>{$qty}</b>) lebih besar dari jumlah masuk di batch ini (<b>{$data['batch_qty']}</b>). Tetap lanjut?");
                }

                $user->setSession('keluar_foto', array_merge($data, ['quantity' => $qty]));
                $this->sendMessage($chatId,
                    "📦 Quantity: <b>{$qty}</b>\n\n📸 Kirim <b>foto bukti</b> (opsional):\n(Kirim foto langsung, atau ketik <code>-</code> untuk lewati)",
                    $this->replyKeyboard([[['text' => '-']], [['text' => '❌ Batal']]])
                );
                break;

            case 'keluar_foto':
                $fotoPath = null;
                if (isset($msg['photo'])) {
                    $bestPhoto = end($msg['photo']);
                    $fotoPath  = $this->downloadAndSave($bestPhoto['file_id'], 'barang_keluar');
                }
                $user->setSession('keluar_tanya_harga', array_merge($data, ['foto' => $fotoPath]));
                $this->sendMessage($chatId,
                    "💰 Apakah Anda ingin mencatat <b>harga</b>?",
                    $this->replyKeyboard([[['text' => '✅ Ya, tambah harga'], ['text' => '⏭️ Lewati']], [['text' => '❌ Batal']]])
                );
                break;

            case 'keluar_tanya_harga':
                if ($text === '✅ Ya, tambah harga') {
                    $user->setSession('keluar_harga', $data);
                    $this->sendMessage($chatId,
                        "💰 Masukkan <b>harga satuan</b> (Rp):\nContoh: <code>25000</code>",
                        $this->replyKeyboard([[['text' => '❌ Batal']]])
                    );
                } else {
                    $user->setSession('keluar_tanggal', array_merge($data, ['harga_satuan' => 0]));
                    $this->tanyaTanggal($chatId);
                }
                break;

            case 'keluar_harga':
                $harga = (float) preg_replace('/[^0-9]/', '', $text);
                if ($harga < 0) {
                    $this->sendMessage($chatId, '❌ Masukkan angka harga yang valid.');
                    return;
                }
                $user->setSession('keluar_tanggal', array_merge($data, ['harga_satuan' => $harga]));
                $this->tanyaTanggal($chatId);
                break;

            case 'keluar_tanggal':
                $tanggal = $this->parseTanggal($text);
                if ($tanggal === 'MANUAL_INPUT') {
                    $this->sendMessage($chatId, "📅 Ketik tanggal keluar: <code>DD-MM-YYYY</code>", $this->replyKeyboard([[['text' => '❌ Batal']]]));
                    return;
                }
                if (!$tanggal) { $this->sendMessage($chatId, "❌ Format salah."); return; }
                
                $user->setSession('keluar_konfirmasi', array_merge($data, ['tanggal' => $tanggal]));
                $this->tanyaKonfirmasiKeluar($chatId, array_merge($data, ['tanggal' => $tanggal]));
                break;

            case 'keluar_konfirmasi':
                if ($text === '✅ Konfirmasi & Simpan') {
                    $this->simpanKeluar($chatId, $user, $data);
                }
                break;
        }
    }

    protected function tanyaKonfirmasiKeluar(int $chatId, array $data): void
    {
        $barang = Barang::find($data['barang_id']);
        $harga = $data['harga_satuan'] ?? 0;
        $tgl   = \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y');
        
        $msg  = "🧐 <b>PERIKSA KEMBALI DATA ANDA</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📤 TIPE: BARANG KELUAR\n";
        $msg .= "📦 Barang: <b>" . ($barang->nama_barang ?? '???') . "</b>\n";
        $msg .= "🔢 Quantity: <b>{$data['quantity']}</b>\n";
        $msg .= "💰 Harga: <b>Rp " . number_format($harga, 0, ',', '.') . "</b>\n";
        $msg .= "📅 Tanggal: <b>{$tgl}</b>\n";
        $msg .= ($data['foto'] ?? null) ? "📸 Foto: Tersedia\n" : "📸 Foto: Tidak ada\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "Apakah data di atas sudah benar?";

        $this->sendMessage($chatId, $msg, $this->inlineKeyboard([
            [['text' => '✅ SIMPAN DATA', 'callback_data' => 'save_keluar']],
            [
                ['text' => '🔢 Edit Qty', 'callback_data' => 'edit_keluar_qty'],
                ['text' => '💰 Edit Harga', 'callback_data' => 'edit_keluar_harga'],
            ],
            [['text' => '📅 Edit Tanggal', 'callback_data' => 'edit_keluar_tanggal']],
            [['text' => '❌ BATALKAN', 'callback_data' => 'batal_keluar']],
        ]));
    }

    protected function simpanKeluar(int $chatId, TelegramUser $user, array $data): void
    {
        $barang = Barang::find($data['barang_id']);
        if (!$barang) {
            $this->sendMessage($chatId, '❌ Barang tidak ditemukan.');
            $user->clearSession();
            return;
        }
        if ($barang->stok < $data['quantity']) {
            $this->sendMessage($chatId,
                "❌ Stok tidak mencukupi!\nStok tersedia: <b>{$barang->stok}</b>, diminta: <b>{$data['quantity']}</b>"
            );
            $user->clearSession();
            return;
        }

        $qty        = $data['quantity'];
        $harga      = $data['harga_satuan'] ?? 0;
        $totalHarga = $harga * $qty;
        $noTrx      = BarangKeluar::generateNoTransaksi();

        BarangKeluar::create([
            'no_transaksi'     => $noTrx,
            'barang_id'        => $barang->id,
            'barang_masuk_id'  => $data['batch_id'] ?? null,
            'telegram_user_id' => $user->id,
            'quantity'         => $qty,
            'harga_satuan'     => $harga,
            'total_harga'      => $totalHarga,
            'tanggal'          => $data['tanggal'],
            'foto'             => $data['foto'] ?? null,
            'status'           => 'approved',
        ]);

        // Kurangi qty di record BarangMasuk asal (sesuai permintaan user)
        if (!empty($data['batch_id'])) {
            $bm = BarangMasuk::find($data['batch_id']);
            if ($bm) {
                $bm->decrement('quantity', $qty);
            }
        }

        $stokLama = $barang->stok;
        $barang->decrement('stok', $qty);
        $barang->refresh();

        $user->clearSession();

        $tgl  = \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y');
        $msg  = "✅ <b>BERHASIL DISIMPAN!</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📋 No: <code>{$noTrx}</code>\n";
        $msg .= "📦 Barang: <b>{$barang->nama_barang}</b>\n";
        $msg .= "🔢 Qty keluar: <b>-{$qty}</b>\n";
        if ($harga > 0) {
            $msg .= "💰 Harga: <b>Rp " . number_format($harga, 0, ',', '.') . "</b>\n";
            $msg .= "💵 Total: <b>Rp " . number_format($totalHarga, 0, ',', '.') . "</b>\n";
        }
        $msg .= "📅 Tanggal: <b>{$tgl}</b>\n";
        $msg .= "📸 Foto: " . ($data['foto'] ? '✅ Ada' : '—') . "\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "� Oleh: <b>" . ($user->first_name ?: $user->username) . "</b>\n";
        $msg .= "�📉 Stok: <b>{$stokLama}</b> → <b>{$barang->stok}</b>";

        if ($barang->stok <= $barang->stok_minimal) {
            $msg .= "\n\n⚠️ <b>Perhatian:</b> Stok barang ini sudah menipis!";
        }

        $this->sendMessage($chatId, $msg, $this->mainMenu());
        $this->sendMessage($chatId, "✅ <b>Data Berhasil Disimpan!</b>");
    }

    // ═══════════════════════════════════════════════════════════
    //  BARANG RUSAK — FLOW
    // ═══════════════════════════════════════════════════════════

    protected function startRusak(int $chatId, TelegramUser $user): void
    {
        $user->setSession('rusak_cari', []);
        $this->sendMessage($chatId,
            "⚠️ <b>LAPOR BARANG RUSAK</b>\n\nKetik nama atau kode barang:\n(ketik /batal untuk membatalkan)",
            $this->replyKeyboard([[['text' => '❌ Batal']]])
        );
    }

    protected function sessionRusak(array $msg, TelegramUser $user, string $state): void
    {
        $chatId = $msg['chat']['id'];
        $text   = $msg['text'] ?? null;
        $data   = $user->session_data ?? [];

        switch ($state) {
            case 'rusak_cari':
                $results = Barang::where('nama_barang', 'like', "%{$text}%")
                    ->orWhere('kode_barang', $text)
                    ->where('is_active', true)
                    ->limit(8)->get();

                if ($results->isEmpty()) {
                    $this->sendMessage($chatId, "❌ Barang tidak ditemukan. Coba ketik nama lain:");
                    return;
                }

                if ($results->count() === 1) {
                    $b = $results->first();
                    $user->setSession('rusak_qty', array_merge($data, [
                        'barang_id' => $b->id,
                        'nama_barang' => $b->nama_barang,
                        'stok' => $b->stok,
                    ]));
                    $this->sendMessage($chatId, "✅ <b>{$b->nama_barang}</b>\nStok saat ini: <b>{$b->stok}</b>\n\nMasukkan <b>jumlah</b> barang rusak:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
                    return;
                }

                $user->setSession('rusak_pilih', array_merge($data, ['keyword' => $text]));
                $buttons = [];
                foreach ($results as $b) {
                    $buttons[] = [['text' => "📦 {$b->nama_barang} (stok: {$b->stok})", 'callback_data' => "pilih_rusak_{$b->id}"]];
                }
                $this->sendMessage($chatId, "🔍 Pilih barang:", $this->inlineKeyboard($buttons));
                break;

            case 'rusak_qty':
                if (!is_numeric($text) || (int)$text <= 0) {
                    $this->sendMessage($chatId, '❌ Masukkan angka valid.');
                    return;
                }
                $user->setSession('rusak_foto', array_merge($data, ['quantity' => (int)$text]));
                $this->sendMessage($chatId, "📸 Kirim <b>foto barang rusak</b> (opsional):\n(Ketik <code>-</code> untuk lewati)",
                    $this->replyKeyboard([[['text' => '-']], [['text' => '❌ Batal']]])
                );
                break;

            case 'rusak_foto':
                $fotoPath = null;
                if (isset($msg['photo'])) {
                    $bestPhoto = end($msg['photo']);
                    $fotoPath  = $this->downloadAndSave($bestPhoto['file_id'], 'barang_rusak');
                }
                $user->setSession('rusak_alasan', array_merge($data, ['foto' => $fotoPath]));
                $this->sendMessage($chatId, "📝 Masukkan <b>alasan/keterangan</b> kerusakan:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
                break;

            case 'rusak_alasan':
                $user->setSession('rusak_tanggal', array_merge($data, ['alasan' => $text]));
                $this->tanyaTanggal($chatId);
                break;

            case 'rusak_tanggal':
                $tanggal = $this->parseTanggal($text);
                if ($tanggal === 'MANUAL_INPUT') {
                    $this->sendMessage($chatId, "📅 Format: <code>DD-MM-YYYY</code>", $this->replyKeyboard([[['text' => '❌ Batal']]]));
                    return;
                }
                if (!$tanggal) { $this->sendMessage($chatId, "❌ Format salah."); return; }
                
                $user->setSession('rusak_konfirmasi', array_merge($data, ['tanggal' => $tanggal]));
                $this->tanyaKonfirmasiRusak($chatId, array_merge($data, ['tanggal' => $tanggal]));
                break;
        }
    }

    protected function tanyaKonfirmasiRusak(int $chatId, array $data): void
    {
        $tgl = \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y');
        $msg  = "🧐 <b>KONFIRMASI BARANG RUSAK</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📦 Barang: <b>{$data['nama_barang']}</b>\n";
        $msg .= "🔢 Jumlah: <b>{$data['quantity']}</b>\n";
        $msg .= "📝 Alasan: <i>{$data['alasan']}</i>\n";
        $msg .= "📅 Tanggal: <b>{$tgl}</b>\n";
        $msg .= ($data['foto'] ?? null) ? "📸 Foto: ✅ Tersedia\n" : "📸 Foto: ❌ Tidak ada\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "Lanjutkan simpan?";

        $this->sendMessage($chatId, $msg, $this->inlineKeyboard([
            [['text' => '✅ SIMPAN', 'callback_data' => 'save_rusak']],
            [['text' => '❌ BATAL', 'callback_data' => 'batal_rusak']],
        ]));
    }

    protected function simpanRusak(int $chatId, TelegramUser $user, array $data): void
    {
        $barang = Barang::find($data['barang_id']);
        if (!$barang) { $this->sendMessage($chatId, '❌ Eror.'); return; }

        $noTrx = BarangRusak::generateNoTransaksi();
        BarangRusak::create([
            'no_transaksi'     => $noTrx,
            'barang_id'        => $barang->id,
            'telegram_user_id' => $user->id,
            'quantity'         => $data['quantity'],
            'foto'             => $data['foto'] ?? null,
            'alasan'           => $data['alasan'],
            'tanggal'          => $data['tanggal'],
            'status'           => 'pending',
        ]);

        $stokLama = $barang->stok;
        $barang->decrement('stok', $data['quantity']);

        $user->clearSession();
        $userName = $user->first_name ?: $user->username;
        $this->sendMessage($chatId, "✅ <b>LAPORAN RUSAK BERHASIL!</b>\nNomor: <code>{$noTrx}</code>\n👤 Oleh: <b>{$userName}</b>\n📉 Stok: {$stokLama} → {$barang->refresh()->stok}", $this->mainMenu());
    }

    // ═══════════════════════════════════════════════════════════
    //  LAPORAN
    // ═══════════════════════════════════════════════════════════

    protected function showLaporanMenu(int $chatId): void
    {
        $this->sendMessage($chatId,
            "📊 <b>LAPORAN</b>\nPilih periode laporan:",
            $this->inlineKeyboard([
                [
                    ['text' => '📅 Hari Ini',   'callback_data' => 'laporan_hari'],
                    ['text' => '📆 Minggu Ini',  'callback_data' => 'laporan_minggu'],
                ],
                [
                    ['text' => '🗓️ Bulan Ini',  'callback_data' => 'laporan_bulan'],
                    ['text' => '📋 Per Barang',  'callback_data' => 'laporan_barang'],
                ],
            ])
        );
    }

    protected function laporanHari(int $chatId): void
    {
        $dari  = now()->startOfDay();
        $sampai = now()->endOfDay();
        $this->kirimLaporan($chatId, $dari, $sampai, '📅 Hari Ini — ' . now()->format('d/m/Y'));
    }

    protected function laporanMinggu(int $chatId): void
    {
        $dari   = now()->startOfWeek();
        $sampai = now()->endOfWeek();
        $this->kirimLaporan($chatId, $dari, $sampai,
            '📆 Minggu Ini (' . $dari->format('d/m') . ' – ' . $sampai->format('d/m/Y') . ')'
        );
    }

    protected function laporanBulan(int $chatId): void
    {
        $dari   = now()->startOfMonth();
        $sampai = now()->endOfMonth();
        $this->kirimLaporan($chatId, $dari, $sampai,
            '🗓️ Bulan ' . now()->translatedFormat('F Y')
        );
    }

    protected function kirimLaporan(int $chatId, $dari, $sampai, string $judul): void
    {
        $masukList  = BarangMasuk::with(['barang', 'telegramUser', 'updater'])
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')->get();

        $keluarList = BarangKeluar::with(['barang', 'telegramUser', 'updater'])
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')->get();

        $rusakList = BarangRusak::with(['barang', 'telegramUser'])
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereNull('deleted_at')
            ->orderBy('tanggal')->get();

        $totalMasukQty  = $masukList->sum('quantity');
        $totalKeluarQty = $keluarList->sum('quantity');
        $totalRusakQty  = $rusakList->sum('quantity');
        $totalMasukRp   = $masukList->sum('total_harga');
        $totalKeluarRp  = $keluarList->sum('total_harga');

        $msg  = "📊 <b>LAPORAN {$judul}</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📥 Masuk : <b>{$masukList->count()} transaksi</b> ({$totalMasukQty} item)\n";
        if ($totalMasukRp > 0) {
            $msg .= "    💰 Rp " . number_format($totalMasukRp, 0, ',', '.') . "\n";
        }
        $msg .= "📤 Keluar: <b>{$keluarList->count()} transaksi</b> ({$totalKeluarQty} item)\n";
        if ($totalKeluarRp > 0) {
            $msg .= "    💰 Rp " . number_format($totalKeluarRp, 0, ',', '.') . "\n";
        }
        if ($rusakList->isNotEmpty()) {
            $msg .= "🛠️ Rusak : <b>{$rusakList->count()} laporan</b> ({$totalRusakQty} item)\n";
        }

        // Detail masuk
        if ($masukList->isNotEmpty()) {
            $msg .= "\n📥 <b>Detail Barang Masuk:</b>\n";
            foreach ($masukList as $bm) {
                $tgl   = \Carbon\Carbon::parse($bm->tanggal)->format('d/m');
                $harga = $bm->total_harga > 0 ? ' — Rp ' . number_format($bm->total_harga, 0, ',', '.') : '';
                $foto  = $bm->foto ? ' 📸' : '';
                $creator = $bm->telegramUser ? ($bm->telegramUser->first_name ?: $bm->telegramUser->username) : 'Sys';
                $updater = $bm->updater ? " (� " . ($bm->updater->first_name ?: $bm->updater->username) . ")" : "";
                
                $msg  .= "  • [{$tgl}] <b>{$bm->barang->nama_barang}</b> +{$bm->quantity}{$harga}{$foto}\n";
                $msg  .= "    👤 {$creator}{$updater}\n";
            }
        }

        // Detail keluar
        if ($keluarList->isNotEmpty()) {
            $msg .= "\n📤 <b>Detail Barang Keluar:</b>\n";
            foreach ($keluarList as $bk) {
                $tgl   = \Carbon\Carbon::parse($bk->tanggal)->format('d/m');
                $harga = $bk->total_harga > 0 ? ' — Rp ' . number_format($bk->total_harga, 0, ',', '.') : '';
                $foto  = $bk->foto ? ' 📸' : '';
                $creator = $bk->telegramUser ? ($bk->telegramUser->first_name ?: $bk->telegramUser->username) : 'Sys';
                $updater = $bk->updater ? " (� " . ($bk->updater->first_name ?: $bk->updater->username) . ")" : "";

                $msg  .= "  • [{$tgl}] <b>{$bk->barang->nama_barang}</b> -{$bk->quantity}{$harga}{$foto}\n";
                $msg  .= "    👤 {$creator}{$updater}\n";
            }
        }

        // Detail rusak
        if ($rusakList->isNotEmpty()) {
            $msg .= "\n�️ <b>Detail Barang Rusak:</b>\n";
            foreach ($rusakList as $br) {
                $tgl   = \Carbon\Carbon::parse($br->tanggal)->format('d/m');
                $foto  = $br->foto ? ' 📸' : '';
                $creator = $br->telegramUser ? ($br->telegramUser->first_name ?: $br->telegramUser->username) : 'Sys';
                $msg  .= "  • [{$tgl}] <b>{$br->barang->nama_barang}</b> rusak {$br->quantity}{$foto}\n";
                $msg  .= "    👤 {$creator}\n";
            }
        }

        if ($masukList->isEmpty() && $keluarList->isEmpty() && $rusakList->isEmpty()) {
            $msg .= "\n📭 Belum ada transaksi pada periode ini.";
        }

        // Tombol aksi
        $buttons = [];
        foreach ($masukList as $bm) {
            $row = [];
            if ($bm->foto) {
                $row[] = ['text' => "📸 In: {$bm->barang->nama_barang}", 'callback_data' => "view_photo_m_{$bm->id}"];
            }
            $row[] = ['text' => "🗑️ Hapus {$bm->barang->nama_barang}", 'callback_data' => "hapus_m_{$bm->id}"];
            $buttons[] = $row;
        }
        foreach ($keluarList as $bk) {
            $row = [];
            if ($bk->foto) {
                $row[] = ['text' => "📸 Out: {$bk->barang->nama_barang}", 'callback_data' => "view_photo_k_{$bk->id}"];
            }
            $row[] = ['text' => "🗑️ Hapus {$bk->barang->nama_barang}", 'callback_data' => "hapus_k_{$bk->id}"];
            $buttons[] = $row;
        }
        foreach ($rusakList as $br) {
            $row = [];
            if ($br->foto) {
                $row[] = ['text' => "📸 Rusak: {$br->barang->nama_barang}", 'callback_data' => "view_photo_r_{$br->id}"];
            }
            $row[] = ['text' => "🗑️ Hapus {$br->barang->nama_barang}", 'callback_data' => "hapus_r_{$br->id}"];
            $buttons[] = $row;
        }

        $extra = $this->mainMenu();
        if (!empty($buttons)) {
            $extra = $this->inlineKeyboard($buttons);
        }

        $this->sendMessage($chatId, $msg, $extra);
    }

    protected function laporanPerBarang(int $chatId): void
    {
        $barangs = Barang::where('is_active', true)->orderBy('nama_barang')->get();

        if ($barangs->isEmpty()) {
            $this->sendMessage($chatId, '📦 Belum ada data barang.', $this->mainMenu());
            return;
        }

        $buttons = [];
        foreach ($barangs->chunk(2) as $chunk) {
            $row = [];
            foreach ($chunk as $b) {
                $row[] = ['text' => "📦 {$b->nama_barang}", 'callback_data' => "laporan_detail_{$b->id}"];
            }
            $buttons[] = $row;
        }

        $this->sendMessage($chatId,
            "📋 <b>LAPORAN PER BARANG</b>\nPilih barang:",
            $this->inlineKeyboard($buttons)
        );
    }

    protected function laporanDetailBarang(int $chatId, int $barangId): void
    {
        $barang = Barang::find($barangId);
        if (!$barang) {
            $this->sendMessage($chatId, '❌ Barang tidak ditemukan.');
            return;
        }

        $masukList  = BarangMasuk::where('barang_id', $barangId)->orderBy('tanggal', 'desc')->limit(10)->get();
        $keluarList = BarangKeluar::where('barang_id', $barangId)->orderBy('tanggal', 'desc')->limit(10)->get();

        $totalMasuk  = BarangMasuk::where('barang_id', $barangId)->sum('quantity');
        $totalKeluar = BarangKeluar::where('barang_id', $barangId)->sum('quantity');

        $msg  = "📦 <b>{$barang->nama_barang}</b>\n";
        $msg .= "🔢 Kode: <code>{$barang->kode_barang}</code>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "📊 Stok Saat Ini: <b>{$barang->stok} {$barang->satuan}</b>\n";
        $msg .= "📈 Total Masuk: <b>{$totalMasuk}</b>\n";
        $msg .= "📉 Total Keluar: <b>{$totalKeluar}</b>\n";

        if ($masukList->isNotEmpty()) {
            $msg .= "\n📥 <b>10 Masuk Terakhir:</b>\n";
            foreach ($masukList as $bm) {
                $tgl   = \Carbon\Carbon::parse($bm->tanggal)->format('d/m/Y');
                $harga = $bm->total_harga > 0 ? ' Rp' . number_format($bm->total_harga, 0, ',', '.') : '';
                $msg  .= "  • {$tgl} +{$bm->quantity}{$harga}\n";
            }
        }

        if ($keluarList->isNotEmpty()) {
            $msg .= "\n📤 <b>10 Keluar Terakhir:</b>\n";
            foreach ($keluarList as $bk) {
                $tgl   = \Carbon\Carbon::parse($bk->tanggal)->format('d/m/Y');
                $harga = $bk->total_harga > 0 ? ' Rp' . number_format($bk->total_harga, 0, ',', '.') : '';
                $msg  .= "  • {$tgl} -{$bk->quantity}{$harga}\n";
            }
        }

        $this->sendMessage($chatId, $msg, $this->mainMenu());
    }

    // ═══════════════════════════════════════════════════════════
    //  CALLBACK QUERY
    // ═══════════════════════════════════════════════════════════

    protected function handleCallback(array $cb): void
    {
        $chatId  = $cb['message']['chat']['id'];
        $data    = $cb['data'];
        $from    = $cb['from'];
        $callId  = $cb['id'];

        $user = TelegramUser::updateOrCreate(
            ['telegram_id' => $from['id']],
            ['username' => $from['username'] ?? null, 'first_name' => $from['first_name'] ?? null]
        );

        // Laporan
        if ($data === 'laporan_hari')    { $this->answerCallbackQuery($callId); $this->laporanHari($chatId); return; }
        if ($data === 'laporan_minggu')  { $this->answerCallbackQuery($callId); $this->laporanMinggu($chatId); return; }
        if ($data === 'laporan_bulan')   { $this->answerCallbackQuery($callId); $this->laporanBulan($chatId); return; }
        if ($data === 'laporan_barang')  { $this->answerCallbackQuery($callId); $this->laporanPerBarang($chatId); return; }

        if (str_starts_with($data, 'view_photo_')) {
            $this->answerCallbackQuery($callId);
            if (str_starts_with($data, 'view_photo_m_')) {
                $id = (int) str_replace('view_photo_m_', '', $data);
                $trx = BarangMasuk::with('barang')->find($id);
                if ($trx && $trx->foto) {
                    $caption = "📥 <b>Foto Barang Masuk</b>\n📦 {$trx->barang->nama_barang}\n📅 " . \Carbon\Carbon::parse($trx->tanggal)->format('d/m/Y');
                    $this->sendPhoto($chatId, $trx->foto, $caption);
                } else {
                    $this->sendMessage($chatId, "❌ Foto tidak ditemukan.");
                }
            } elseif (str_starts_with($data, 'view_photo_k_')) {
                $id = (int) str_replace('view_photo_k_', '', $data);
                $trx = BarangKeluar::with('barang')->find($id);
                if ($trx && $trx->foto) {
                    $caption = "📤 <b>Foto Barang Keluar</b>\n📦 {$trx->barang->nama_barang}\n📅 " . \Carbon\Carbon::parse($trx->tanggal)->format('d/m/Y');
                    $this->sendPhoto($chatId, $trx->foto, $caption);
                } else {
                    $this->sendMessage($chatId, "❌ Foto tidak ditemukan.");
                }
            } elseif (str_starts_with($data, 'view_photo_r_')) {
                $id = (int) str_replace('view_photo_r_', '', $data);
                $trx = BarangRusak::with('barang')->find($id);
                if ($trx && $trx->foto) {
                    $caption = "🛠️ <b>Foto Barang Rusak</b>\n📦 {$trx->barang->nama_barang}\n📅 " . \Carbon\Carbon::parse($trx->tanggal)->format('d/m/Y');
                    $this->sendPhoto($chatId, $trx->foto, $caption);
                } else {
                    $this->sendMessage($chatId, "❌ Foto tidak ditemukan.");
                }
            }
            return;
        }

        // --- HANDLER KONFIRMASI & EDIT ---
        
        // Cek jika sesi sudah tidak ada (misal sudah disave sebelumnya)
        $isKonfirmasiState = in_array($user->session_state, ['masuk_konfirmasi', 'keluar_konfirmasi', 'rusak_konfirmasi']);
        $isEditAction = str_contains($data, 'edit_') || str_contains($data, 'save_') || str_contains($data, 'batal_');

        if ($isEditAction && !$isKonfirmasiState) {
            $this->answerCallbackQuery($callId, "⚠️ Sesi sudah berakhir atau data sudah disimpan.");
            return;
        }

        if ($data === 'save_rusak') {
            if ($user->session_state === 'rusak_konfirmasi') {
                $this->answerCallbackQuery($callId, "Memproses...");
                $this->simpanRusak($chatId, $user, $user->session_data);
            }
            return;
        }

        if ($data === 'batal_rusak') {
            $this->answerCallbackQuery($callId);
            $user->clearSession();
            $this->sendMessage($chatId, "❌ Laporan dibatalkan.", $this->mainMenu());
            return;
        }

        if (str_starts_with($data, 'pilih_rusak_')) {
            $this->answerCallbackQuery($callId);
            $barangId = (int) str_replace('pilih_rusak_', '', $data);
            $barang   = Barang::find($barangId);
            if (!$barang) return;
            $user->setSession('rusak_qty', ['barang_id' => $barang->id, 'nama_barang' => $barang->nama_barang]);
            $this->sendMessage($chatId, "✅ <b>{$barang->nama_barang}</b>\nStok: <b>{$barang->stok}</b>\n\nMasukkan <b>jumlah</b> barang rusak:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
            return;
        }

        if (str_starts_with($data, 'pilih_batch_')) {
            $this->answerCallbackQuery($callId);
            $id = (int) str_replace('pilih_batch_', '', $data);
            $bm = BarangMasuk::with('barang')->find($id);
            if (!$bm) return;

            $user->setSession('keluar_qty', array_merge($user->session_data, [
                'barang_id'   => $bm->barang_id,
                'nama_barang' => $bm->barang->nama_barang,
                'stok'        => $bm->barang->stok,
                'batch_id'    => $bm->id,
                'batch_qty'   => $bm->quantity
            ]));
            
            $this->sendMessage($chatId, 
                "✅ Terpilih: <b>{$bm->barang->nama_barang}</b>\n(Dari masuk qty: +{$bm->quantity})\n\nMasukkan <b>jumlah</b> yang keluar:",
                $this->replyKeyboard([[['text' => '❌ Batal']]])
            );
            return;
        }

        if ($data === 'save_masuk') {
            if ($user->session_state === 'masuk_konfirmasi') {
                $this->answerCallbackQuery($callId, "Memproses penyimpanan...");
                $this->simpanMasuk($chatId, $user, $user->session_data);
            }
            return;
        }
        if ($data === 'edit_masuk_qty') {
            $this->answerCallbackQuery($callId);
            $user->update(['session_state' => 'masuk_qty']);
            $this->sendMessage($chatId, "🔢 Masukkan <b>quantity baru</b>:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
            return;
        }
        if ($data === 'edit_masuk_harga') {
            $this->answerCallbackQuery($callId);
            $user->update(['session_state' => 'masuk_harga']);
            // Pastikan data lama ada
            $user->setSession('masuk_harga', $user->session_data);
            $this->sendMessage($chatId, "💰 Masukkan <b>harga satuan baru</b>:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
            return;
        }
        if ($data === 'edit_masuk_tanggal') {
            $this->answerCallbackQuery($callId);
            $user->update(['session_state' => 'masuk_tanggal']);
            $this->tanyaTanggal($chatId);
            return;
        }
        if ($data === 'batal_masuk' || $data === 'batal_keluar') {
            $this->answerCallbackQuery($callId);
            $user->clearSession();
            $this->sendMessage($chatId, "❌ Transaksi dibatalkan.", $this->mainMenu());
            return;
        }

        if ($data === 'save_keluar') {
            if ($user->session_state === 'keluar_konfirmasi') {
                $this->answerCallbackQuery($callId, "Memproses penyimpanan...");
                $this->simpanKeluar($chatId, $user, $user->session_data);
            }
            return;
        }
        if ($data === 'edit_keluar_qty') {
            $this->answerCallbackQuery($callId);
            $user->update(['session_state' => 'keluar_qty']);
            $this->sendMessage($chatId, "🔢 Masukkan <b>quantity baru</b>:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
            return;
        }
        if ($data === 'edit_keluar_harga') {
            $this->answerCallbackQuery($callId);
            $user->update(['session_state' => 'keluar_harga']);
            $this->sendMessage($chatId, "💰 Masukkan <b>harga satuan baru</b>:", $this->replyKeyboard([[['text' => '❌ Batal']]]));
            return;
        }
        if ($data === 'edit_keluar_tanggal') {
            $this->answerCallbackQuery($callId);
            $user->update(['session_state' => 'keluar_tanggal']);
            $this->tanyaTanggal($chatId);
            return;
        }

        // --- HANDLER HAPUS ---
        if (str_starts_with($data, 'hapus_m_')) {
            $this->answerCallbackQuery($callId, "Menghapus...");
            $id = (int) str_replace('hapus_m_', '', $data);
            $trx = BarangMasuk::find($id);
            if ($trx) {
                // Tracking siapa yang hapus
                $trx->update(['updated_by_id' => $user->id]);
                
                // Kurangi stok kembali
                Barang::find($trx->barang_id)->decrement('stok', $trx->quantity);
                $trx->delete();
                $this->sendMessage($chatId, "🗑️ Transaksi masuk berhasil dihapus & stok dikurangi.");
            }
            return;
        }
        if (str_starts_with($data, 'hapus_k_')) {
            $this->answerCallbackQuery($callId, "Menghapus...");
            $id = (int) str_replace('hapus_k_', '', $data);
            $trx = BarangKeluar::find($id);
            if ($trx) {
                // Tracking siapa yang hapus
                $trx->update(['updated_by_id' => $user->id]);

                // Tambahkan stok fisik kembali
                Barang::find($trx->barang_id)->increment('stok', $trx->quantity);
                
                // Tambahkan kembali ke batch masuk jika ada
                if ($trx->barang_masuk_id) {
                    $bmNode = BarangMasuk::find($trx->barang_masuk_id);
                    if ($bmNode) {
                        $bmNode->increment('quantity', $trx->quantity);
                    }
                }

                $trx->delete();
                $this->sendMessage($chatId, "🗑️ Transaksi keluar berhasil dihapus & stok dikembalikan.");
            }
            return;
        }

        if (str_starts_with($data, 'hapus_r_')) {
            $this->answerCallbackQuery($callId, "Menghapus...");
            $id = (int) str_replace('hapus_r_', '', $data);
            $trx = BarangRusak::find($id);
            if ($trx) {
                // Tambahkan stok kembali
                Barang::find($trx->barang_id)->increment('stok', $trx->quantity);
                $trx->delete();
                $this->sendMessage($chatId, "🗑️ Laporan barang rusak berhasil dihapus & stok dikembalikan.");
            }
            return;
        }

        if (str_starts_with($data, 'laporan_detail_')) {
            $this->answerCallbackQuery($callId);
            $id = (int) str_replace('laporan_detail_', '', $data);
            $this->laporanDetailBarang($chatId, $id);
            return;
        }

        // Pilih barang untuk MASUK
        if (str_starts_with($data, 'pilih_masuk_baru_')) {
            $this->answerCallbackQuery($callId);
            $nama = str_replace('pilih_masuk_baru_', '', $data);
            $user->setSession('masuk_qty', ['nama_barang' => $nama, 'barang_id' => null]);
            $this->sendMessage($chatId,
                "➕ Barang baru: <b>{$nama}</b>\n\nMasukkan <b>quantity / jumlah</b>:",
                $this->replyKeyboard([[['text' => '❌ Batal']]])
            );
            return;
        }

        if (str_starts_with($data, 'pilih_masuk_')) {
            $this->answerCallbackQuery($callId);
            $barangId = (int) str_replace('pilih_masuk_', '', $data);
            $barang   = Barang::find($barangId);
            if (!$barang) return;
            $user->setSession('masuk_qty', ['barang_id' => $barang->id, 'nama_barang' => $barang->nama_barang]);
            $this->sendMessage($chatId,
                "✅ <b>{$barang->nama_barang}</b>\n📦 Stok: <b>{$barang->stok} {$barang->satuan}</b>\n\nMasukkan <b>quantity</b>:",
                $this->replyKeyboard([[['text' => '❌ Batal']]])
            );
            return;
        }

        // Pilih barang untuk KELUAR
        if (str_starts_with($data, 'pilih_keluar_')) {
            $this->answerCallbackQuery($callId);
            $barangId = (int) str_replace('pilih_keluar_', '', $data);
            $barang   = Barang::find($barangId);
            if (!$barang) return;

            if ($barang->stok <= 0) {
                $this->sendMessage($chatId, "❌ Stok <b>{$barang->nama_barang}</b> kosong!");
                $user->clearSession();
                return;
            }

            $user->setSession('keluar_qty', [
                'barang_id'   => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'stok'        => $barang->stok,
                'satuan'      => $barang->satuan,
            ]);
            $this->sendMessage($chatId,
                "✅ <b>{$barang->nama_barang}</b>\n📦 Stok: <b>{$barang->stok} {$barang->satuan}</b>\n\nMasukkan <b>quantity</b> yang keluar:",
                $this->replyKeyboard([[['text' => '❌ Batal']]])
            );
            return;
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  UTILITY
    // ═══════════════════════════════════════════════════════════

    protected function tanyaTanggal(int $chatId): void
    {
        $hari = now()->format('d-m-Y');
        $kemarin = now()->subDay()->format('d-m-Y');
        $this->sendMessage($chatId,
            "📅 <b>Pilih tanggal transaksi:</b>",
            $this->replyKeyboard([
                [['text' => "📅 Hari Ini ({$hari})"], ['text' => "📅 Kemarin ({$kemarin})"]],
                [['text' => '📝 Masukkan tanggal lain']],
                [['text' => '❌ Batal']],
            ])
        );
    }

    protected function parseTanggal(string $text): ?string
    {
        // "Hari Ini (02-03-2026)" → ambil tanggal di dalam kurung
        if (preg_match('/\((\d{2}-\d{2}-\d{4})\)/', $text, $m)) {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $m[1])->toDateString();
        }

        // "Masukkan tanggal lain" → kembalikan sentinel khusus
        if (str_contains($text, 'Masukkan tanggal lain')) {
            return 'MANUAL_INPUT';
        }

        // Format DD-MM-YYYY
        try {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $text)->toDateString();
        } catch (\Exception $e) {}

        // Format YYYY-MM-DD
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $text)->toDateString();
        } catch (\Exception $e) {}

        return null;
    }
}
