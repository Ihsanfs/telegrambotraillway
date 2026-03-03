# ✅ Deployment Checklist - Telegram Bot

> Gunakan checklist ini untuk memastikan semua langkah deployment selesai dengan benar

---

## 🔍 Pre-Deployment

### Code Quality
- [ ] Semua file sudah committed ke Git
- [ ] `.env.local` atau `.env` tidak di-commit
- [ ] `.gitignore` sudah updated
- [ ] Tidak ada debug code (var_dump, dd(), etc)
- [ ] Tidak ada hardcoded credentials

### Environment Setup
- [ ] `.env.example` sudah updated dengan semua variables
- [ ] APP_KEY sudah generate (`php artisan key:generate`)
- [ ] TELEGRAM_BOT_TOKEN sudah copy dari BotFather
- [ ] Database credentials valid di local

### Local Testing
- [ ] `php artisan serve` berjalan tanpa error
- [ ] Database terkoneksi
- [ ] Bot respons di Telegram (local ngrok/webhook)
- [ ] Migrations berjalan: `php artisan migrate`

---

## 📤 GitHub Setup

- [ ] Repository dibuat di GitHub
- [ ] Repository visibility = PUBLIC (agar deployment bisa akses)
- [ ] `.env` file ada di `.gitignore`
- [ ] Project di-push ke `main` branch
- [ ] README.md ada (optional)

**Verify**: Buka GitHub → repository → Anda harus bisa lihat semua project files

---

## 🚀 Railway.app Deployment

### Account & Project
- [ ] Railway account dibuat (https://railway.app)
- [ ] Connected dengan GitHub
- [ ] Project dibuat dari repo `kartu-stok-barang`
- [ ] Billing setup (auto-charge ke kartu jika perlu)

### Environment Variables
- [ ] APP_NAME = "Kartu Stok Barang"
- [ ] APP_ENV = "production"
- [ ] APP_DEBUG = "false"
- [ ] APP_KEY = base64 key (copy dari local .env)
- [ ] APP_URL = Railway provided domain
- [ ] DB_HOST = MySQL host (dari Railway MySQL service)
- [ ] DB_USERNAME = MySQL username
- [ ] DB_PASSWORD = MySQL password
- [ ] DB_DATABASE = "barang_masuk"
- [ ] TELEGRAM_BOT_TOKEN = valid token
- [ ] TELEGRAM_WEBHOOK_URL = https://YOUR_DOMAIN.railway.app/api/telegram/webhook
- [ ] TELEGRAM_ADMIN_IDS = your_telegram_id

### Database Setup
- [ ] MySQL service ditambahkan ke project
- [ ] MySQL credentials di-copy ke variables
- [ ] Database di-import: `mysql ... < barang_masuk.sql`
- [ ] Database migration dijalankan: `php artisan migrate --force`
- [ ] Tables tersedia: `SHOW TABLES;`

### Deployment
- [ ] Procfile ada di root
- [ ] runtime.txt ada (php-8.2)
- [ ] Initial deploy selesai (status = Running)
- [ ] Build logs tidak ada error
- [ ] Application accessible via Railway domain

---

## 🤖 Telegram Webhook

- [ ] Webhook command dijalankan: `php artisan telegram:set-webhook`
- [ ] Tidak ada error di logs
- [ ] Bot @username sudah bisa diakses

**Verify**: Telegram → search bot name → send `/start` → bot must reply

---

## 📊 Database Verification

- [ ] Connect ke cloud database via MySQL client:
  ```bash
  mysql -h host -u user -p -D barang_masuk
  ```
- [ ] List tables: `SHOW TABLES;`
- [ ] Check data: `SELECT COUNT(*) FROM barang;`
- [ ] Verify migrations: `SELECT * FROM migrations;`

### Tables Check
- [ ] `barang` - master data ✅
- [ ] `barang_masuk` - incoming goods ✅
- [ ] `barang_keluar` - outgoing goods ✅
- [ ] `barang_rusak` - damaged goods ✅
- [ ] `telegram_users` - users ✅
- [ ] `users` - Laravel users ✅

---

## 🔒 Security Check

- [ ] `.env` files tidak accessible via web
- [ ] API endpoints protected jika ada
- [ ] TELEGRAM_BOT_TOKEN hanya di Railway variables (tidak di GitHub)
- [ ] Database password tidak di logs
- [ ] HTTPS digunakan (Railway auto-provides)
- [ ] CORS disabled jika tidak diperlukan

---

## 🧪 Bot Functionality Test

### Basic Commands
- [ ] `/start` - Bot respons dengan greeting
- [ ] `/help` - Bot show available commands
- [ ] `/barang_masuk` - Form untuk tambah barang masuk
- [ ] `/barang_keluar` - Form untuk tambah barang keluar
- [ ] `/list_barang` - Bot list semua barang

### Database Integration
- [ ] Barang bisa ditambah via bot
- [ ] Data muncul di database
- [ ] Stok terupdate dengan benar
- [ ] Telegram user terekam di telegram_users table

### Error Handling
- [ ] Invalid input ditolak dengan pesan jelas
- [ ] Database errors tidak tampil ke user
- [ ] Logs menunjukkan error yang valid

---

## 📱 Production Monitoring

### Logs
- [ ] Cek logs regularly: `railway logs`
- [ ] No critical errors dalam 24 jam
- [ ] Response time reasonable (< 2 sec)
- [ ] Database queries efficient

### Database
- [ ] Regular backups (optional)
- [ ] Monitor disk space usage
- [ ] Check slow queries jika ada

### Bot
- [ ] Bot responsive
- [ ] No missed messages
- [ ] Webhook responding

---

## 📋 Documentation

- [ ] README.md updated dengan instruksi
- [ ] DEPLOYMENT_GUIDE.md selesai dibaca
- [ ] Database schema documented
- [ ] API endpoints documented (jika ada)

---

## 🆘 Troubleshooting Done?

### If Issues Found:
- [ ] Check logs di Railway dashboard
- [ ] Verify all environment variables
- [ ] Re-deploy: `git push` (trigger rebuild)
- [ ] Check database connection
- [ ] Run migrations lagi jika perlu
- [ ] Clear cache: `php artisan cache:clear`

### Common Issues Resolved:
- [ ] SQLSTATE error → check DB credentials
- [ ] Webhook invalid → check TELEGRAM_WEBHOOK_URL
- [ ] Bot not responding → check bot token & webhook registration
- [ ] Build failed → check logs & Procfile

---

## ✅ Final Verification (24-48 hours after deploy)

- [ ] Bot jalan 24/7
- [ ] Database responsive
- [ ] No critical errors dalam logs
- [ ] Response time acceptable
- [ ] All features working as expected

---

## 🎁 Post-Deployment

- [ ] Share Railway app URL dengan team
- [ ] Document database credentials secara aman
- [ ] Setup monitoring/alerts (optional)
- [ ] Create backup procedure
- [ ] Document deployment procedure untuk next deployment

---

## 📞 Support Contacts

- Railway Docs: https://docs.railway.app
- Railway Support: https://railway.app/support
- Laravel Docs: https://laravel.com/docs
- Telegram Bot API: https://core.telegram.org/bots/api

---

## 🎯 Status

- [ ] Checklist dimulai tanggal: ________
- [ ] Checklist selesai tanggal: ________
- [ ] All items completed: ☐ Ya ☐ Tidak
- [ ] Ready for production: ☐ Ya ☐ Tidak

---

**Jika semua checklist ✅, bot Anda SIAP for production!** 🚀

**Tidak semua selesai?** Lihat section yang belum selesai dan continue dari sana.
