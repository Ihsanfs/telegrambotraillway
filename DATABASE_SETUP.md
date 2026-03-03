# 🗄️ Database Setup Guide

Database yang sudah di-sediakan: `barang_masuk.sql`

---

## 📍 Opsi 1: Import SQL File (Recommended)

### Jika menggunakan Railway.app

**Step 1: Connect ke Database**

```bash
# Dari lokal, gunakan MySQL client:
mysql -h <railway-mysql-host> \
       -u <username> \
       -p<password> \
       barang_masuk < C:\laragon\www\kartu_stok_barang\barang_masuk.sql
```

**Step 2: Verify**

```bash
# Login ke database
mysql -h <railway-mysql-host> -u <username> -p barang_masuk

# Cek tables
SHOW TABLES;
```

---

## 📍 Opsi 2: Gunakan Laravel Migration

### Step 1: Dari Railway Shell

```bash
php artisan migrate:fresh --force
```

### Step 2: Seed Data (jika ada seeder)

```bash
php artisan db:seed
```

---

## 📍 Opsi 3: Manual via PhpMyAdmin

Jika cloud provider menyediakan phpMyAdmin:

1. Buka phpMyAdmin portal
2. Create database: `barang_masuk`
3. Import file: `barang_masuk.sql`
4. Click execute

---

## 🔍 Database Structure

### Tables:

1. **barang** - Master data barang/items
   - id, kode_barang, nama_barang, kategori, satuan, stok, etc.

2. **barang_masuk** - Incoming goods transaction
   - id, no_transaksi, barang_id, quantity, harga_satuan, supplier, tanggal, etc.

3. **barang_keluar** - Outgoing goods transaction
   - id, no_transaksi, barang_id, quantity, penerima, divisi, keperluan, status, etc.

4. **barang_rusak** - Damaged goods tracking
   - id, no_transaksi, barang_id, quantity, alasan, tanggal, status, etc.

5. **telegram_users** - Telegram user data
   - id, telegram_id, username, role (admin/operator/viewer), is_active, etc.

6. **users** - Laravel standard users table
   - id, name, email, password, etc.

7. **migrations** - Laravel migration tracking
8. **failed_jobs** - Job queue tracking
9. **personal_access_tokens** - API token tracking

---

## ✅ Verify Database Connected

### Method 1: Laravel Artisan

```bash
php artisan tinker

# Di tinker prompt:
DB::connection()->getPDO();
// Jika tidak error = berhasil!
```

### Method 2: Check Logs

```bash
# Railway/Koyeb logs:
Log::info('Database connected: ' . DB::connection()->getName());
```

---

## 🔑 Get Database Credentials

### Dari Railway:
1. Dashboard → Your App
2. MySQL Service → Copy credentials
3. Format: `mysql://user:pass@host:port/db`

### Dari Koyeb:
1. Dashboard → Your App
2. PostgreSQL/MySQL Service
3. Credentials di service details

---

## 📝 Connection String

### MySQL (Railway)
```
DB_CONNECTION=mysql
DB_HOST=<from railway>
DB_PORT=3306
DB_DATABASE=barang_masuk
DB_USERNAME=<from railway>
DB_PASSWORD=<from railway>
```

### PostgreSQL (Koyeb)
```
DB_CONNECTION=pgsql
DB_HOST=<from koyeb>
DB_PORT=5432
DB_DATABASE=barang_masuk
DB_USERNAME=<from koyeb>
DB_PASSWORD=<from koyeb>
```

---

## 🆘 Troubleshooting

### Error: "SQLSTATE[HY000]: General error"
**Cause**: Database credentials salah atau host tidak bisa diakses
**Fix**:
- Verify DB_HOST, DB_USERNAME, DB_PASSWORD di .env
- Cek network connection

### Error: "Unknown column"
**Cause**: Table structure tidak match
**Fix**:
- Drop table dan re-import: `DROP TABLE barang; SOURCE barang_masuk.sql;`

### Error: "No database selected"
**Cause**: DB_DATABASE tidak set
**Fix**:
- Set `DB_DATABASE=barang_masuk` di .env

---

## 📊 Backup Database

### Export dari cloud:
```bash
# Railway MySQL
mysqldump -h <host> -u <user> -p <password> barang_masuk > backup.sql

# Koyeb PostgreSQL
pg_dump -h <host> -U <user> -d barang_masuk > backup.sql
```

### Upload ke S3/Cloud Storage (optional):
```bash
aws s3 cp backup.sql s3://your-bucket/backup.sql
```

---

## ✅ Database Ready!

Setelah setup selesai, database Anda siap digunakan oleh bot Telegram.

**Next**: Setup Telegram webhook → Bot akan siap 24/7
