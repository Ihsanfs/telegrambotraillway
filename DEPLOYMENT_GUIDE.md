# 📋 Panduan Deploy Telegram Bot - Gratis 1 Tahun

> **Rekomendasi**: Gunakan **Railway.app** atau **Koyeb.com** untuk hasil terbaik

---

## 🎯 Opsi Deployment Gratis

| Platform | Database | Uptime | Storage | Max Duration | Monthly Cost |
|----------|----------|--------|---------|--------------|--------------|
| **Railway.app** ⭐ | MySQL/PostgreSQL | 99.9% | 10GB | Unlimited | $0 (5 krdt/bulan) |
| **Koyeb.com** | PostgreSQL | 99.95% | Unlimited | Unlimited | $0 (App terbatas) |
| **Render.com** | PostgreSQL | 99.95% | 256MB | Unlimited | $0 (Auto sleep) |
| **Vercel** | - | 99.95% | API only | Unlimited | $0 |
| Google Cloud | MySQL | 99.95% | Limited | 12 bulan | $0 (Trial) |

---

## 🚀 METODE 1: Railway.app (RECOMMENDED ⭐)

### Step 1: Persiapan Repository GitHub

**1a. Push project ke GitHub**
```bash
cd C:\laragon\www\kartu_stok_barang

# Initialize git (jika belum)
git init
git add .
git commit -m "Initial commit - Telegram Bot"

# Buat repository di GitHub (https://github.com/new)
# Lalu push:
git remote add origin https://github.com/YOUR_USERNAME/kartu-stok-barang.git
git branch -M main
git push -u origin main
```

**1b. Buat file `.env.railway`**
```bash
# Copy .env.example jika belum ada
cp .env.example .env.railway
```

**1c. Update `.env` untuk produksi**
```ini
# .env.railway
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_APP_NAME.railway.app

DB_CONNECTION=mysql
DB_HOST=${DATABASE_URL}
DB_PORT=3306
DB_DATABASE=barang_masuk
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_WEBHOOK_URL=https://YOUR_APP_NAME.railway.app/api/telegram/webhook
TELEGRAM_ADMIN_IDS=your_telegram_ids
```

### Step 2: Setup Railway.app

**2a. Kunjungi https://railway.app**
- Sign up dengan GitHub
- Klik "New Project"
- Pilih "Deploy from GitHub repo"
- Select repository: `kartu-stok-barang`

**2b. Konfigurasi Environment Variables**
Railway Dashboard → Variables
```
APP_NAME=Kartu Stok Barang
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:vZX7a2FZ8XktMeWQEQuDw4NXbYzVTSyQAWqeTj811uo=
APP_URL=https://YOUR_APP.railway.app

TELEGRAM_BOT_TOKEN=8549996721:AAHnj2svLA9aeZnwnmLuZPKUzAMctGmRxHY
TELEGRAM_WEBHOOK_URL=https://YOUR_APP.railway.app/api/telegram/webhook
TELEGRAM_ADMIN_IDS=5092772533,1942575425,7993265470

LOG_CHANNEL=single
```

**2c. Tambah MySQL Database**
- Railway Dashboard → "Add Service"
- Pilih "MySQL"
- Generate credentials otomatis
- Copy `DATABASE_URL` ke Railway variables

### Step 3: Deploy & Setup Database

**3a. Konfigurasi PHP**
Buat file `Procfile` di root project:
```
web: vendor/bin/heroku-php-apache2 public/
```

Buat file `runtime.txt`:
```
php-8.2
```

**3b. Jalankan Migrations**
Railway → Deployments → "Run Command"
```bash
php artisan migrate --force
php artisan db:seed
# Or import database:
mysql -u root -p barang_masuk < barang_masuk.sql
```

**3c. Set Webhook Telegram**
Railway → Deployments → "Run Command"
```bash
php artisan telegram:set-webhook
```

---

## 🚀 METODE 2: Koyeb.com (Alternative ⭐⭐)

### Step 1: Persiapan (sama dengan Railway)

### Step 2: Setup Koyeb.com

**2a. Kunjungi https://www.koyeb.com**
- Sign up dengan GitHub
- Klik "Create App"
- Select repository
- Builder: Buildpack
- Ports: 8080 (jika HTTP) atau 443 (HTTPS)

**2b. Environment Setup**
```
PORT=8080
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=<postgresql-host>
DB_PORT=5432
DB_DATABASE=barang_masuk

TELEGRAM_BOT_TOKEN=xxx
TELEGRAM_WEBHOOK_URL=https://YOUR_APP.koyeb.run/api/telegram/webhook
```

**2c. Build & Deploy**
- Koyeb akan auto-detect PHP dan Composer
- Setup PostgreSQL di Koyeb
- Import database

---

## 🚀 METODE 3: Render.com (Alternatif)

**Advantages**:
- Free tier yang lebih generous
- Auto-deploy dari GitHub
- PostgreSQL included

**Steps**:
1. Visit https://render.com
2. "New" → "Web Service"
3. Select GitHub repo
4. Runtime: PHP
5. Setup PostgreSQL service
6. Deploy

---

## ⚙️ SETUP DATABASE DI CLOUD

### Cara 1: Import via Railway/Koyeb Dashboard

```bash
# Connect ke cloud database
mysql -h <cloud-host> -u <user> -p <password> barang_masuk < barang_masuk.sql
```

### Cara 2: Laravel Migration
```bash
php artisan migrate:fresh --force
php artisan db:seed
```

---

## 🔧 POST-DEPLOYMENT CHECKLIST

- [ ] Database berhasil di-import
- [ ] Environment variables sudah set
- [ ] APP_KEY sudah di-generate
- [ ] Webhook Telegram sudah didaftar
- [ ] Bot respons ke Telegram
- [ ] Database terkoneksi dengan baik
- [ ] Logs bersih (tidak ada error)

**Cek Logs**:
```bash
# Railway
railway logs

# Koyeb
koyeb logs

# Render
render logs
```

---

## 📱 Test Telegram Bot

1. Buka Telegram
2. Cari bot Anda: `@your_bot_name`
3. Kirim `/start`
4. Bot harus response

---

## 💡 Troubleshooting

### Error: "SQLSTATE[HY000]"
→ Database credentials salah di `.env`

### Error: "Webhook URL invalid"
→ TELEGRAM_WEBHOOK_URL harus HTTPS & public

### Error: "Class 'PDO' not found"
→ Extension PHP MySQL tidak aktif (update buildpack)

### Bot tidak reply
→ Check logs dengan `railway logs` atau `koyeb logs`

---

## 🎁 Bonus: Keep App Running 24/7

Railway & Koyeb **sudah 24/7 by default** ✅

Untuk monitoring:
```bash
# Railway CLI
npm install -g @railway/cli
railway login
railway logs -p <project-id> --follow
```

---

## 💰 Cost Breakdown (1 Tahun)

| Service | Free Credits | Duration | Total Cost |
|---------|-------------|----------|-----------|
| Railway | $5/bulan × 12 | 12 bulan | **$0** ✅ |
| Koyeb | Unlimited | 12 bulan | **$0** ✅ |
| Render | Limited tier | 12 bulan | **$0** ✅ |

---

## 📞 Next Steps

1. **Push ke GitHub** (Step 1)
2. **Pilih Platform** (Railway/Koyeb/Render)
3. **Setup di Platform** (Step 2)
4. **Import Database** (Step 3)
5. **Test Bot**
6. **Monitor Logs**

**Siap deploy? Mari kita mulai! 🚀**
