# ⚡ Quick Start Guide - Deployment Gratis 1 Tahun

## 🎯 Pilihan Cepat

### Opsi 1: Railway.app ⭐ (RECOMMENDED)
- Free $5/bulan × 12 bulan = **$0**
- MySQL included
- 15 menit setup
- [Detail Guide](./RAILWAY_DEPLOYMENT.md)

### Opsi 2: Koyeb.com ⭐⭐
- 100% free selamanya
- PostgreSQL included
- 15 menit setup
- [Detail Guide](./KOYEB_DEPLOYMENT.md)

### Opsi 3: Render.com
- 100% free selamanya
- PostgreSQL included
- [Pilih opsi ini jika ingin auto-sleep mode]

---

## ⚡ 5 Langkah Cepat

### 1️⃣ Push ke GitHub (5 menit)
```bash
cd C:\laragon\www\kartu_stok_barang
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/kartu-stok-barang.git
git branch -M main
git push -u origin main
```

### 2️⃣ Daftar di Railway.app (1 menit)
- Buka https://railway.app
- Sign up dengan GitHub

### 3️⃣ Buat Project (3 menit)
- Click "New Project"
- Select repo: `kartu-stok-barang`
- Railway auto-build

### 4️⃣ Setup Database (3 menit)
- Add MySQL service
- Copy credentials ke Variables

### 5️⃣ Setup Telegram (3 menit)
```bash
# Di Railway Shell:
php artisan migrate --force
php artisan telegram:set-webhook
```

**Total: ~15 menit ⏱️**

---

## ✅ Success Checklist

- [ ] Repository di GitHub
- [ ] Railway project dibuat
- [ ] MySQL service aktif
- [ ] Env variables set
- [ ] Database migrated
- [ ] Webhook registered
- [ ] Bot respond di Telegram

---

## 📱 Test Bot

1. Buka Telegram
2. Cari bot: `@YOUR_BOT_NAME`
3. Kirim: `/start`
4. Bot harus reply!

---

## 🆘 Trouble?

| Error | Solution |
|-------|----------|
| `SQLSTATE[HY000]` | Database credentials salah |
| Bot tidak reply | TELEGRAM_WEBHOOK_URL salah |
| Build failed | Check logs, push ulang |

Lihat [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) untuk troubleshooting lengkap.

---

## 💰 Cost

| Item | Cost |
|------|------|
| Railway/Koyeb | $0 |
| Database | $0 |
| Bandwidth | $0 |
| **Total/tahun** | **$0** ✅ |

---

## 📖 Dokumentasi Lengkap

- [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) - Panduan umum semua platform
- [RAILWAY_DEPLOYMENT.md](./RAILWAY_DEPLOYMENT.md) - Detailed Railway guide
- [KOYEB_DEPLOYMENT.md](./KOYEB_DEPLOYMENT.md) - Detailed Koyeb guide

---

## 🚀 Ready?

**Railway.app**: [Step 1](./RAILWAY_DEPLOYMENT.md#-step-1-push-project-ke-github)

**Koyeb.com**: [Step 1](./KOYEB_DEPLOYMENT.md#-step-1-persiapan-sama-seperti-railway)

**Pilih satu dan mulai! 🎉**
