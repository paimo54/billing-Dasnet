# 🔐 Server Info & Fix Guide untuk bils.dasnet.my.id

## 📋 Informasi Server Anda

```
Server IP: 172.13.13.5
aaPanel Port: 8811
aaPanel URL: http://172.13.13.5:8811
Username: root
Password: madiun12

Domain: bils.dasnet.my.id
Website Path: /www/wwwroot/bils.dasnet.my.id
```

⚠️ **PENTING**: Ganti password default setelah fix error 403!

---

## 🚀 Quick Fix Error 403 - Step by Step

### STEP 1: Login ke aaPanel

```
1. Buka browser
2. Akses: http://172.13.13.5:8811
3. Username: root
4. Password: madiun12
5. Login
```

### STEP 2: Fix Permission via aaPanel (TERMUDAH)

```
1. Setelah login, klik menu "Website" di sidebar kiri
2. Cari website: bils.dasnet.my.id
3. Klik nama website tersebut
4. Klik tab "Site directory"
5. Scroll ke bawah, klik tombol "Fix permissions"
6. Tunggu proses selesai (biasanya 10-30 detik)
7. Klik "Save"
```

### STEP 3: Set Document Root ke /public

```
Masih di halaman yang sama:
1. Lihat bagian "Running directory"
2. Ubah dari "/" menjadi "/public"
3. Klik "Save"
```

### STEP 4: Restart Nginx

```
1. Klik menu "App Store" di sidebar
2. Cari "Nginx"
3. Klik tombol "Restart"
4. Tunggu sampai selesai
```

### STEP 5: Test Website

```
Buka browser baru:
https://bils.dasnet.my.id

Seharusnya sudah bisa diakses! ✅
```

---

## 🔧 Alternatif: Fix via SSH

Jika cara di atas tidak berhasil, gunakan SSH:

### Login SSH

```bash
# Dari komputer Anda
ssh root@172.13.13.5

# Masukkan password: madiun12
```

### Jalankan Fix Script

```bash
# Masuk ke folder website
cd /www/wwwroot/bils.dasnet.my.id

# Fix ownership
chown -R www:www .

# Fix permission folder
find . -type d -exec chmod 755 {} \;

# Fix permission file
find . -type f -exec chmod 644 {} \;

# Fix storage & cache
chmod -R 777 storage
chmod -R 777 bootstrap/cache

# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart Nginx
systemctl restart nginx

# Test local
curl http://localhost

# Test domain
curl https://bils.dasnet.my.id
```

---

## 📊 Diagnostic Commands

Jika masih error, jalankan ini untuk diagnostic:

```bash
# 1. Check permission
ls -la /www/wwwroot/bils.dasnet.my.id/public/

# Output seharusnya:
# drwxr-xr-x www www (untuk folder)
# -rw-r--r-- www www (untuk file)

# 2. Check index.php
ls -la /www/wwwroot/bils.dasnet.my.id/public/index.php

# Harus ada file ini!

# 3. Check Nginx error log
tail -n 50 /www/server/nginx/logs/error.log

# 4. Check Nginx config
cat /www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

# 5. Check PHP-FPM
systemctl status php-fpm-81

# 6. Test local website
curl -I http://localhost

# 7. Check Cloudflare Tunnel
systemctl status cloudflared
journalctl -u cloudflared -n 50
```

---

## 🔍 Check Nginx Config

Config yang benar untuk Laravel:

```bash
# View config
cat /www/server/panel/vhost/nginx/bils.dasnet.my.id.conf
```

**Seharusnya seperti ini:**

```nginx
server {
    listen 80;
    server_name bils.dasnet.my.id;
    
    root /www/wwwroot/bils.dasnet.my.id/public;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-81.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

**Jika berbeda, edit via aaPanel:**
```
1. Website → bils.dasnet.my.id
2. Klik "Config file"
3. Edit sesuai config di atas
4. Save
5. Restart Nginx
```

---

## 🔐 Security: Ganti Password aaPanel

Setelah website jalan, SEGERA ganti password:

### Via aaPanel Web:

```
1. Login: http://172.13.13.5:8811
2. Klik icon user di pojok kanan atas
3. Klik "Panel settings"
4. Ubah password
5. Save
```

### Via SSH:

```bash
# Login SSH
ssh root@172.13.13.5

# Ganti password aaPanel
bt 6

# Pilih option untuk change password
# Masukkan password baru yang kuat
```

**Password baru yang disarankan:**
- Minimal 12 karakter
- Kombinasi huruf besar, kecil, angka, simbol
- Contoh: `Billing@Dasnet2026!`

---

## 📁 File Locations

```
Website Root:
/www/wwwroot/bils.dasnet.my.id/

Laravel Public:
/www/wwwroot/bils.dasnet.my.id/public/

Laravel .env:
/www/wwwroot/bils.dasnet.my.id/.env

Nginx Config:
/www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

Nginx Error Log:
/www/server/nginx/logs/error.log
/www/wwwroot/bils.dasnet.my.id/log/nginx_error.log

Cloudflare Config:
/root/.cloudflared/config.yml

PHP-FPM:
/tmp/php-cgi-81.sock
```

---

## ✅ Checklist Fix Error 403

### Via aaPanel (Recommended):
- [ ] Login: http://172.13.13.5:8811
- [ ] Website → bils.dasnet.my.id
- [ ] Site directory → Fix permissions
- [ ] Running directory → /public
- [ ] Save
- [ ] App Store → Nginx → Restart
- [ ] Test: https://bils.dasnet.my.id

### Via SSH (Alternative):
- [ ] SSH: ssh root@172.13.13.5
- [ ] cd /www/wwwroot/bils.dasnet.my.id
- [ ] chown -R www:www .
- [ ] chmod -R 755 .
- [ ] chmod -R 777 storage bootstrap/cache
- [ ] php artisan cache:clear
- [ ] systemctl restart nginx
- [ ] Test: curl https://bils.dasnet.my.id

### After Fix:
- [ ] Website accessible
- [ ] Change aaPanel password
- [ ] Setup Laravel .env properly
- [ ] Test all features

---

## 🎯 Expected Result

Setelah fix, website Anda akan:

✅ Accessible di: https://bils.dasnet.my.id
✅ HTTPS otomatis (Cloudflare SSL)
✅ Fast loading (Cloudflare CDN)
✅ Laravel app running
✅ No 403 error

---

## 🐛 Troubleshooting

### Masih Error 403?

```bash
# Check apakah Laravel sudah di-upload
ls -la /www/wwwroot/bils.dasnet.my.id/

# Harus ada folder:
# - app/
# - bootstrap/
# - config/
# - public/
# - storage/
# - vendor/
# - artisan
# - composer.json

# Check index.php
cat /www/wwwroot/bils.dasnet.my.id/public/index.php

# Harus ada isi (Laravel bootstrap code)
```

### Error 502 Bad Gateway?

```bash
# Restart PHP-FPM
systemctl restart php-fpm-81

# Restart Nginx
systemctl restart nginx

# Check logs
tail -f /www/server/nginx/logs/error.log
```

### Error 521 Web Server Down?

```bash
# Check Cloudflare Tunnel
systemctl status cloudflared

# Restart if needed
systemctl restart cloudflared

# Check logs
journalctl -u cloudflared -f
```

---

## 📞 Quick Commands

```bash
# Login SSH
ssh root@172.13.13.5

# Fix permission
cd /www/wwwroot/bils.dasnet.my.id && chown -R www:www . && chmod -R 755 . && chmod -R 777 storage bootstrap/cache

# Restart services
systemctl restart nginx && systemctl restart php-fpm-81 && systemctl restart cloudflared

# Test
curl https://bils.dasnet.my.id

# View logs
tail -f /www/server/nginx/logs/error.log
```

---

## 🆘 Need More Help?

Jika masih ada masalah, kirim hasil dari:

```bash
# 1. Permission check
ls -la /www/wwwroot/bils.dasnet.my.id/public/

# 2. Error log
tail -n 50 /www/server/nginx/logs/error.log

# 3. Nginx config
cat /www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

# 4. Test local
curl -I http://localhost
```

---

## 🎉 Summary

**Server Info:**
- IP: 172.13.13.5:8811
- User: root
- Pass: madiun12 (GANTI SETELAH FIX!)

**Problem:**
- Error 403 Forbidden

**Solution:**
1. Login aaPanel
2. Fix permissions
3. Set document root: /public
4. Restart Nginx
5. Test website

**Expected Time:** 5 menit

---

Good luck! 🚀
