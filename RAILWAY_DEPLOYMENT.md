# 🚀 Railway.app - Deployment Step-by-Step

> **Durasi**: ~15 menit | **Cost**: **$0 GRATIS** (pakai $5 free credits/bulan)

---

## ✅ Prerequisites

- [ ] GitHub Account (https://github.com)
- [ ] Railway Account (https://railway.app)
- [ ] Project sudah di-push ke GitHub
- [ ] Telegram Bot Token (dari @BotFather)

---

## 📍 Step 1: Push Project ke GitHub

### 1.1 Jika belum setup Git

```bash
cd C:\laragon\www\kartu_stok_barang

# Inisialisasi Git
git init

# Add semua file
git add .

# Commit
git commit -m "Initial commit - Telegram Bot Inventory System"
```

### 1.2 Buat Repository di GitHub

1. Buka https://github.com/new
2. Repository name: `kartu-stok-barang`
3. Description: `Telegram Bot untuk Manajemen Stok Barang`
4. Pilih **Public** (agar Railway bisa akses)
5. Klik "Create Repository"

### 1.3 Push ke GitHub

```bash
# Tambah remote
git remote add origin https://github.com/YOUR_USERNAME/kartu-stok-barang.git

# Rename branch jika perlu
git branch -M main

# Push
git push -u origin main
```

✅ **Repository Anda sekarang di GitHub!**

---

## 🔑 Step 2: Setup Railway.app

### 2.1 Sign In / Register

1. Buka https://railway.app
2. Click "Login" → Select "Login with GitHub"
3. Authorize Railway untuk akses GitHub Anda

### 2.2 Buat Project Baru

1. Click "New Project"
2. Select "Deploy from GitHub repo"
3. Pilih repository: `kartu-stok-barang`
4. Konfirm build settings

### 2.3 Tambah Service

Railway akan auto-detect Dockerfile atau mendeteksi PHP.

```bash
# Railway akan menjalankan:
php artisan migrate --force
vendor/bin/heroku-php-apache2 public/
```

---

## 🗄️ Step 3: Setup Database MySQL

### 3.1 Tambah MySQL Service

1. Di Railway Dashboard
2. Click "Add Service"
3. Pilih "MySQL"
4. Tunggu sampai ready ✅

### 3.2 Copy Database Credentials

Railway akan auto-generate credentials. Di Railway Variables:

```
DATABASE_URL=mysql://username:password@host:3306/railway
MYSQL_URL=mysql://username:password@host:3306/railway
DB_HOST=host
DB_USERNAME=username
DB_PASSWORD=password
DB_DATABASE=barang_masuk
```

---

## ⚙️ Step 4: Setup Environment Variables

### 4.1 Buka Railway Variables

Railway Dashboard → Your Project → Variables Tab

### 4.2 Tambah Variables

```
# Application
APP_NAME=Kartu Stok Barang
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:vZX7a2FZ8XktMeWQEQuDw4NXbYzVTSyQAWqeTj811uo=
APP_URL=https://YOUR_APP_NAME.railway.app

# Database (dari Railway MySQL service)
DB_CONNECTION=mysql
DB_HOST=<dari DATABASE_URL>
DB_PORT=3306
DB_DATABASE=barang_masuk
DB_USERNAME=<dari DATABASE_URL>
DB_PASSWORD=<dari DATABASE_URL>

# Telegram Bot
TELEGRAM_BOT_TOKEN=8549996721:AAHnj2svLA9aeZnwnmLuZPKUzAMctGmRxHY
TELEGRAM_WEBHOOK_URL=https://YOUR_APP_NAME.railway.app/api/telegram/webhook
TELEGRAM_ADMIN_IDS=5092772533

# Logging
LOG_CHANNEL=single

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
```

**Cara dapat `APP_URL`**:
1. Di Railway Dashboard, lihat "Deployment" section
2. Cari "Railway Provided Domain" → gunakan itu

---

## 📊 Step 5: Setup Database

### 5.1 Jalankan Migrations

1. Di Railway Dashboard
2. Klik "Deployments"
3. Klik tombol "..." pada deployment terbaru
4. Select "Run Command"
5. Jalankan:

```bash
php artisan migrate:fresh --force
```

### 5.2 Import SQL Database

Jika ingin import dari SQL file:

```bash
# Dari lokal (jika ada Railway CLI):
railway connect mysql

# Paste ini di prompt:
source barang_masuk.sql
```

**Atau via Laravel Console:**

```bash
# Di Railway Command Prompt:
php artisan tinker

# Copy-paste setiap table creation dari SQL
# Atau import via MySQL client langsung
```

---

## 🤖 Step 6: Register Telegram Webhook

### 6.1 Jalankan Command

Di Railway Dashboard → Deployments → Run Command:

```bash
php artisan telegram:set-webhook
```

Atau manual di `routes/api.php`:

```php
// Cek kode yang ada
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);
```

### 6.2 Test Webhook

```bash
curl -X POST https://YOUR_APP_NAME.railway.app/api/telegram/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "update_id": 1,
    "message": {
      "message_id": 1,
      "chat": {"id": 5092772533},
      "text": "/start"
    }
  }'
```

---

## ✅ Step 7: Test Bot

### 7.1 Buka Telegram

1. Cari bot Anda: `@YOUR_BOT_NAME`
2. Kirim: `/start`
3. Bot harus response

### 7.2 Test Inventory Commands

```
/barang_masuk - Tambah barang masuk
/barang_keluar - Tambah barang keluar
/list_barang - List semua barang
/stok - Cek stok barang
```

---

## 🔍 Step 8: Monitoring & Troubleshooting

### 8.1 Lihat Logs

Railway Dashboard → Deployments → Logs

Cari error patterns:
```
SQLSTATE[HY000] → Database connection error
Class 'App\Models\Barang' not found → Autoloader issue
Webhook URL invalid → TELEGRAM_WEBHOOK_URL salah
```

### 8.2 Common Issues & Solutions

**Problem**: `SQLSTATE[HY000]: General error: 2006 MySQL server has gone away`
**Solution**:
```bash
# Reconnect database di ENV variables
# Pastikan DB_HOST, DB_USERNAME, DB_PASSWORD benar
```

**Problem**: Bot tidak reply
**Solution**:
```bash
# 1. Check logs untuk error
# 2. Pastikan TELEGRAM_BOT_TOKEN valid
# 3. Pastikan TELEGRAM_WEBHOOK_URL = Railway domain
# 4. Jalankan ulang set-webhook command
```

**Problem**: "No application configured to handle the request"
**Solution**:
```bash
# Railway belum selesai build, tunggu 2-3 menit
# Atau trigger rebuild dengan git push baru
```

---

## 💰 Cost Summary

| Item | Cost |
|------|------|
| Railway Credits/month | $5 FREE |
| MySQL Database | Included |
| Bandwidth | Included |
| Storage | 10GB included |
| **Total Cost/year** | **$0** ✅ |

> Railway memberikan $5 free credits per month, yang **lebih dari cukup** untuk bot kecil

---

## 🎯 Final Checklist

- [ ] Repository di-push ke GitHub
- [ ] Railway project dibuat
- [ ] MySQL service ditambahkan
- [ ] Environment variables lengkap
- [ ] Database di-import/di-migrate
- [ ] Webhook sudah di-register
- [ ] Bot respons ke Telegram
- [ ] Logs bersih (tidak ada error)
- [ ] APP_URL menggunakan Railway domain

---

## 📱 Hasil Akhir

Setelah semua step selesai:
- ✅ Bot jalan 24/7
- ✅ Database online
- ✅ Uptime 99.9%
- ✅ Gratis untuk 1 tahun (bahkan lebih!)
- ✅ Auto-deploy ketika push ke GitHub

---

## 🆘 Bantuan Tambahan

**Hubungi Railway Support**: https://railway.app/support
**Cek Docs**: https://docs.railway.app

**Next Steps**:
1. Push ke GitHub
2. Setup Railway (5 menit)
3. Setup Database (5 menit)
4. Test (5 menit)
5. Done! ✅

**Total waktu: ~20 menit**
