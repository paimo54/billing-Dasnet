# Cara Upload & Install ke aaPanel - 1 Klik

## 🎯 Metode Tercepat (Recommended)

### Langkah 1: Persiapan Server

1. **Login ke aaPanel**
   - Buka browser: `http://your-server-ip:8888`
   - Login dengan username & password aaPanel

2. **Install Requirements di aaPanel**
   - Go to **App Store**
   - Install:
     - ✅ Nginx (Latest)
     - ✅ MySQL 5.7+ atau MariaDB 10.3+
     - ✅ PHP 8.1 atau 8.2
     - ✅ phpMyAdmin (optional)

3. **Install PHP Extensions**
   - Go to **App Store** → **PHP 8.1** → **Settings** → **Install Extensions**
   - Install:
     - ✅ opcache
     - ✅ fileinfo
     - ✅ mbstring
     - ✅ pdo_mysql
     - ✅ zip
     - ✅ gd
     - ✅ curl
     - ✅ xml
     - ✅ bcmath

---

### Langkah 2: Upload Files

**Opsi A: Via aaPanel File Manager (Paling Mudah)**

1. Di aaPanel, go to **Files**
2. Navigate ke `/root/` atau `/tmp/`
3. Klik **Upload**
4. Upload file ZIP project Anda
5. Klik kanan file ZIP → **Extract**

**Opsi B: Via FTP/SFTP**

1. Gunakan FileZilla atau WinSCP
2. Connect ke server:
   - Host: `your-server-ip`
   - Port: `22` (SFTP) atau `21` (FTP)
   - Username: `root`
   - Password: `your-root-password`
3. Upload semua files ke `/root/billing-install/`

**Opsi C: Via Git (Jika sudah ada repository)**

```bash
# Login via SSH
ssh root@your-server-ip

# Clone repository
cd /root
git clone https://github.com/yourusername/billing-Dasnet.git billing-install
cd billing-install
```

---

### Langkah 3: Jalankan Installer (1 KLIK!)

**Via SSH:**

```bash
# Login ke server
ssh root@your-server-ip

# Masuk ke direktori
cd /root/billing-install

# Jalankan installer
chmod +x install.sh quick-deploy.sh
bash quick-deploy.sh
```

**Via aaPanel Terminal:**

1. Di aaPanel, go to **Terminal**
2. Jalankan:
```bash
cd /root/billing-install
chmod +x install.sh quick-deploy.sh
bash quick-deploy.sh
```

---

### Langkah 4: Ikuti Prompt Installer

Installer akan menanyakan:

```
Domain name (e.g., billing.yourdomain.com): billing.example.com
Database name [billing_dasnet]: [Enter untuk default]
Database user [billing_user]: [Enter untuk default]
PHP version [8.1]: [Enter untuk default]

Continue with installation? (y/n): y
```

**Tunggu proses instalasi selesai (5-10 menit)**

---

### Langkah 5: Selesai! 🎉

Setelah instalasi selesai, Anda akan melihat:

```
==============================================
  Installation Completed Successfully!
==============================================

Application Details:
  URL: https://billing.example.com
  Directory: /www/wwwroot/billing-dasnet

Database Details:
  Database: billing_dasnet
  Username: billing_user
  Password: [auto-generated]

Default Login (if seeded):
  SuperAdmin: superadmin@example.com / password
  Admin: admin@example.com / password
  Technician: technician@example.com / password
```

**SIMPAN INFORMASI INI!**

---

## 🔐 Langkah Setelah Instalasi

### 1. Setup SSL Certificate (WAJIB!)

Di aaPanel:
1. Go to **Website**
2. Klik domain Anda
3. Tab **SSL**
4. Pilih **Let's Encrypt**
5. Klik **Apply**
6. Enable **Force HTTPS**

### 2. Point Domain ke Server

Di DNS Provider (Cloudflare, Namecheap, dll):
```
Type: A
Name: billing (atau @)
Value: your-server-ip
TTL: Auto
```

### 3. Login ke Aplikasi

1. Buka: `https://billing.yourdomain.com`
2. Login dengan:
   - Email: `superadmin@example.com`
   - Password: `password`
3. **GANTI PASSWORD SEGERA!**

### 4. Configure Payment Gateway

1. Login sebagai SuperAdmin
2. Edit file `.env` via aaPanel File Manager:
   - Path: `/www/wwwroot/billing-dasnet/.env`
3. Tambahkan credentials payment gateway
4. Restart PHP-FPM di aaPanel

---

## 📦 Cara Membuat ZIP untuk Upload

Jika Anda ingin upload via aaPanel File Manager:

**Di Windows:**
1. Buka folder project
2. Select semua files & folders
3. Klik kanan → Send to → Compressed (zipped) folder
4. Rename menjadi `billing-dasnet.zip`

**Di Linux/Mac:**
```bash
cd /path/to/billing-Dasnet
zip -r billing-dasnet.zip . -x "*.git*" "node_modules/*" "vendor/*"
```

**Files yang HARUS ada dalam ZIP:**
- ✅ `install.sh`
- ✅ `quick-deploy.sh`
- ✅ `composer.json`
- ✅ `artisan`
- ✅ `app/`
- ✅ `config/`
- ✅ `database/`
- ✅ `routes/`
- ✅ `.env.example`
- ✅ Semua folder & file Laravel lainnya

**Files yang TIDAK perlu:**
- ❌ `vendor/` (akan di-install otomatis)
- ❌ `node_modules/` (tidak perlu)
- ❌ `.git/` (tidak perlu)
- ❌ `.env` (akan di-generate otomatis)

---

## 🎬 Video Tutorial (Step by Step)

### 1. Upload via aaPanel File Manager

```
1. Login aaPanel → Files
2. Navigate to /root/
3. Click Upload button
4. Select billing-dasnet.zip
5. Wait upload complete
6. Right click → Extract
7. Rename folder to "billing-install"
```

### 2. Run Installer via Terminal

```
1. aaPanel → Terminal
2. Type: cd /root/billing-install
3. Type: chmod +x quick-deploy.sh
4. Type: bash quick-deploy.sh
5. Follow prompts
6. Wait 5-10 minutes
7. Done!
```

---

## ⚡ One-Line Install (Untuk Advanced User)

Jika files sudah di server:

```bash
cd /root/billing-install && chmod +x quick-deploy.sh && bash quick-deploy.sh
```

Atau download & install langsung dari Git:

```bash
cd /root && git clone https://github.com/yourusername/billing-Dasnet.git billing-install && cd billing-install && chmod +x quick-deploy.sh && bash quick-deploy.sh
```

---

## 🔍 Troubleshooting Upload

### Issue: Upload Gagal (File Terlalu Besar)

**Solusi 1: Increase Upload Limit di aaPanel**
1. aaPanel → App Store → PHP 8.1 → Settings
2. Configuration File
3. Cari dan ubah:
   ```ini
   upload_max_filesize = 500M
   post_max_size = 500M
   max_execution_time = 300
   ```
4. Save & Restart PHP

**Solusi 2: Upload via SFTP**
- Gunakan FileZilla atau WinSCP
- Tidak ada batasan ukuran file

**Solusi 3: Upload Tanpa vendor/**
- Hapus folder `vendor/` sebelum ZIP
- Installer akan download otomatis via Composer

### Issue: Permission Denied saat Extract

```bash
# Via SSH
cd /root
chmod 755 billing-dasnet.zip
unzip billing-dasnet.zip -d billing-install
```

### Issue: Installer Not Found

```bash
# Check if files exist
ls -la /root/billing-install/

# If install.sh not found, re-upload
```

---

## 📋 Checklist Sebelum Install

- [ ] aaPanel sudah terinstall
- [ ] Nginx sudah terinstall
- [ ] MySQL/MariaDB sudah terinstall
- [ ] PHP 8.1+ sudah terinstall dengan extensions
- [ ] Domain sudah pointing ke server IP
- [ ] Port 80 & 443 terbuka di firewall
- [ ] Files sudah di-upload ke server
- [ ] Login sebagai root

---

## 🎯 Quick Reference

| Langkah | Command | Lokasi |
|---------|---------|--------|
| 1. Upload | Via aaPanel File Manager | `/root/` |
| 2. Extract | Right click → Extract | `/root/billing-install/` |
| 3. Install | `bash quick-deploy.sh` | Terminal |
| 4. Access | `https://your-domain.com` | Browser |

---

## 💡 Tips

1. **Gunakan domain yang sudah pointing** sebelum install untuk SSL otomatis
2. **Backup aaPanel** sebelum install (Settings → Backup)
3. **Catat semua credentials** yang ditampilkan setelah install
4. **Test di staging server** dulu sebelum production
5. **Setup monitoring** setelah install (Supervisor, Logs)

---

## 📞 Butuh Bantuan?

Jika mengalami masalah:

1. **Check logs:**
   ```bash
   tail -f /www/wwwroot/billing-dasnet/storage/logs/laravel.log
   ```

2. **Check installation info:**
   ```bash
   cat /www/wwwroot/billing-dasnet/INSTALLATION_INFO.txt
   ```

3. **Re-run installer:**
   ```bash
   cd /root/billing-install
   bash quick-deploy.sh
   ```

4. **Manual installation:** Lihat `AAPANEL-INSTALL.md` untuk langkah manual

---

**Estimasi Waktu:**
- Upload files: 2-5 menit
- Run installer: 5-10 menit
- Configure SSL: 2 menit
- **Total: ~15 menit** ⚡

**Selamat! Aplikasi Anda siap digunakan!** 🎉
