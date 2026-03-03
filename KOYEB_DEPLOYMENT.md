# 🚀 Koyeb.com - Deployment Step-by-Step

> **Durasi**: ~15 menit | **Cost**: **$0 GRATIS SEPENUHNYA**

---

## ✅ Prerequisites

- [ ] GitHub Account
- [ ] Koyeb Account (https://www.koyeb.com)
- [ ] Project di-push ke GitHub

---

## 📍 Step 1: Persiapan (Sama seperti Railway)

```bash
cd C:\laragon\www\kartu_stok_barang

# Setup Git & push ke GitHub
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/kartu-stok-barang.git
git branch -M main
git push -u origin main
```

---

## 🔑 Step 2: Setup Koyeb.com

### 2.1 Sign In / Register

1. Buka https://www.koyeb.com
2. Click "Sign up"
3. Pilih "Continue with GitHub"
4. Authorize Koyeb

### 2.2 Buat App Pertama

1. Dashboard → "Create an App"
2. Select "GitHub"
3. Repository: `kartu-stok-barang`
4. Branch: `main`

### 2.3 Configure App

**Builder**: Buildpack (auto-detect)
**Port**: 8000 (Koyeb default)
**Start Command**:
```bash
vendor/bin/heroku-php-apache2 public/
```

---

## 🗄️ Step 3: Setup PostgreSQL (Recommended)

### 3.1 Tambah Database Service

1. Koyeb Dashboard → "Create a Service"
2. Pilih "PostgreSQL"
3. Koyeb akan generate credentials

### 3.2 Konfigurasi Database

Jika ingin MySQL, gunakan cloud provider lain atau setup manual.

**Untuk PostgreSQL** di Laravel:

1. Install driver:
```bash
composer require illuminate/database
```

2. Update `.env`:
```
DB_CONNECTION=pgsql
DB_HOST=<koyeb-postgres-host>
DB_PORT=5432
DB_DATABASE=barang_masuk
DB_USERNAME=<user>
DB_PASSWORD=<password>
```

---

## ⚙️ Step 4: Environment Variables

Koyeb Dashboard → Your App → Settings → Environment Variables

```
APP_NAME=Kartu Stok Barang
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:vZX7a2FZ8XktMeWQEQuDw4NXbYzVTSyQAWqeTj811uo=
APP_URL=https://YOUR_APP.koyeb.run

DB_CONNECTION=pgsql
DB_HOST=<postgres-host>
DB_PORT=5432
DB_DATABASE=barang_masuk
DB_USERNAME=<user>
DB_PASSWORD=<password>

TELEGRAM_BOT_TOKEN=8549996721:AAHnj2svLA9aeZnwnmLuZPKUzAMctGmRxHY
TELEGRAM_WEBHOOK_URL=https://YOUR_APP.koyeb.run/api/telegram/webhook
TELEGRAM_ADMIN_IDS=5092772533

LOG_CHANNEL=single
CACHE_DRIVER=file
```

---

## 📊 Step 5: Deploy

### 5.1 Auto-Build & Deploy

Koyeb akan otomatis:
1. Pull dari GitHub
2. Run `composer install`
3. Build PHP environment
4. Start app

Tunggu hingga status = **Running** ✅

### 5.2 Jalankan Migrations

Koyeb Dashboard → Your App → Shell

```bash
php artisan migrate:fresh --force
```

---

## 🤖 Step 6: Register Webhook

```bash
php artisan telegram:set-webhook
```

---

## ✅ Step 7: Test Bot

Buka Telegram → cari bot → kirim `/start`

---

## 🔍 Logs & Monitoring

Koyeb Dashboard → App → Logs tab

---

## 💡 Koyeb vs Railway

| Feature | Koyeb | Railway |
|---------|-------|---------|
| Free Tier | ✅ Unlimited | ✅ $5/bulan |
| PostgreSQL | ✅ Yes | ❌ No (MySQL only) |
| MySQL | ❌ No | ✅ Yes |
| Uptime | 99.95% | 99.9% |
| Support | Community | Premium |
| Best For | Beginners | Production |

---

## 🎯 Rekomendasi

- **Gunakan Railway** jika sudah punya MySQL database
- **Gunakan Koyeb** jika mau fully free tanpa time limit

Kedua platform sama-sama **gratis selamanya!** ✅
