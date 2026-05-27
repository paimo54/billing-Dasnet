# 🔧 Fix Error 403 Forbidden Nginx di aaPanel

## 🔍 Penyebab Error 403

Error 403 Forbidden biasanya disebabkan oleh:
1. ❌ Permission file/folder salah
2. ❌ Ownership file salah
3. ❌ Index file tidak ditemukan
4. ❌ Directory listing disabled
5. ❌ SELinux blocking (CentOS/RHEL)
6. ❌ Nginx config salah

---

## ⚡ Solusi Cepat (Pilih salah satu)

### Solusi 1: Fix Permission & Ownership (PALING SERING)

```bash
# SSH ke server
ssh root@your-server-ip

# Masuk ke folder website
cd /www/wwwroot/billing.dasnet.com

# Fix ownership
chown -R www:www .

# Fix permission folder
find . -type d -exec chmod 755 {} \;

# Fix permission file
find . -type f -exec chmod 644 {} \;

# Fix permission storage & cache (Laravel)
chmod -R 777 storage
chmod -R 777 bootstrap/cache

# Restart Nginx
systemctl restart nginx
```

### Solusi 2: Via aaPanel (Lebih Mudah)

```
1. Login aaPanel
2. Klik "Website"
3. Klik website Anda (billing.dasnet.com)
4. Klik tab "Site directory"
5. Klik "Fix permissions"
6. Tunggu proses selesai
7. Test lagi
```

---

## 🔧 Solusi Detail (Step by Step)

### STEP 1: Check Permission File

```bash
# Check permission
ls -la /www/wwwroot/billing.dasnet.com

# Seharusnya:
# drwxr-xr-x (755) untuk folder
# -rw-r--r-- (644) untuk file
# Owner: www:www
```

**Jika salah, fix dengan:**
```bash
cd /www/wwwroot/billing.dasnet.com
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache
```

### STEP 2: Check Index File

```bash
# Check apakah ada index.php di folder public
ls -la /www/wwwroot/billing.dasnet.com/public/

# Harus ada file:
# index.php
```

**Jika tidak ada:**
```bash
# Pastikan Laravel sudah di-upload dengan benar
# Dan document root sudah diset ke /public
```

### STEP 3: Check Nginx Config

```bash
# Check config Nginx
cat /www/server/panel/vhost/nginx/billing.dasnet.com.conf
```

**Config yang benar untuk Laravel:**
```nginx
server {
    listen 80;
    server_name billing.dasnet.com;
    
    root /www/wwwroot/billing.dasnet.com/public;
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

**Jika config salah, edit via aaPanel:**
```
1. aaPanel → Website
2. Klik website Anda
3. Klik "Config file"
4. Edit sesuai config di atas
5. Save
6. Restart Nginx
```

### STEP 4: Set Document Root ke /public

```
1. aaPanel → Website
2. Klik website Anda
3. Klik "Site directory"
4. Set "Running directory" ke: /public
5. Klik "Save"
```

### STEP 5: Disable SELinux (Jika CentOS/RHEL)

```bash
# Check SELinux status
getenforce

# Jika "Enforcing", disable:
setenforce 0

# Permanent disable:
nano /etc/selinux/config
# Ubah: SELINUX=disabled

# Reboot
reboot
```

### STEP 6: Check PHP-FPM

```bash
# Check PHP-FPM status
systemctl status php-fpm-81

# Jika tidak running:
systemctl start php-fpm-81
systemctl enable php-fpm-81
```

### STEP 7: Clear Laravel Cache

```bash
cd /www/wwwroot/billing.dasnet.com

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan optimize
```

### STEP 8: Check Nginx Error Log

```bash
# View error log
tail -f /www/wwwroot/billing.dasnet.com/log/nginx_error.log

# Atau
tail -f /www/server/nginx/logs/error.log
```

---

## 🎯 Solusi Berdasarkan Error Log

### Error: "Permission denied"
```bash
chown -R www:www /www/wwwroot/billing.dasnet.com
chmod -R 755 /www/wwwroot/billing.dasnet.com
chmod -R 777 /www/wwwroot/billing.dasnet.com/storage
```

### Error: "index.php not found"
```bash
# Set document root ke /public
# Via aaPanel → Website → Site directory → /public
```

### Error: "Access forbidden by rule"
```bash
# Check .htaccess
cat /www/wwwroot/billing.dasnet.com/public/.htaccess

# Pastikan tidak ada "Deny from all"
```

### Error: "SELinux is preventing"
```bash
setenforce 0
```

---

## ✅ Checklist Troubleshooting

### Permission & Ownership:
- [ ] Owner: www:www
- [ ] Folder permission: 755
- [ ] File permission: 644
- [ ] storage permission: 777
- [ ] bootstrap/cache permission: 777

### Nginx Config:
- [ ] Document root: /www/wwwroot/billing.dasnet.com/public
- [ ] Index: index.php
- [ ] try_files configured
- [ ] PHP-FPM configured

### Laravel:
- [ ] index.php exists in public/
- [ ] .env configured
- [ ] APP_KEY generated
- [ ] Cache cleared
- [ ] Storage linked

### Services:
- [ ] Nginx running
- [ ] PHP-FPM running
- [ ] No errors in logs

---

## 🔍 Diagnostic Commands

```bash
# 1. Check permission
ls -la /www/wwwroot/billing.dasnet.com/public/

# 2. Check ownership
stat /www/wwwroot/billing.dasnet.com/public/index.php

# 3. Check Nginx config
nginx -t

# 4. Check PHP-FPM
systemctl status php-fpm-81

# 5. Check error logs
tail -f /www/server/nginx/logs/error.log

# 6. Test PHP
echo "<?php phpinfo(); ?>" > /www/wwwroot/billing.dasnet.com/public/test.php
curl http://localhost/test.php

# 7. Check SELinux
getenforce
```

---

## 🚀 Script Auto Fix

Saya buatkan script untuk auto fix error 403:

```bash
#!/bin/bash

echo "=== Auto Fix 403 Forbidden ==="
echo ""

SITE_PATH="/www/wwwroot/billing.dasnet.com"

# Check if path exists
if [ ! -d "$SITE_PATH" ]; then
    echo "Error: $SITE_PATH not found!"
    exit 1
fi

echo "[1/7] Fixing ownership..."
chown -R www:www $SITE_PATH

echo "[2/7] Fixing folder permissions..."
find $SITE_PATH -type d -exec chmod 755 {} \;

echo "[3/7] Fixing file permissions..."
find $SITE_PATH -type f -exec chmod 644 {} \;

echo "[4/7] Fixing storage permissions..."
chmod -R 777 $SITE_PATH/storage
chmod -R 777 $SITE_PATH/bootstrap/cache

echo "[5/7] Clearing Laravel cache..."
cd $SITE_PATH
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "[6/7] Restarting Nginx..."
systemctl restart nginx

echo "[7/7] Restarting PHP-FPM..."
systemctl restart php-fpm-81

echo ""
echo "=== Fix Complete! ==="
echo ""
echo "Test your website now:"
echo "curl http://localhost"
echo ""
```

**Cara pakai:**
```bash
# Save script
nano fix-403.sh

# Paste script di atas
# Save (Ctrl+X, Y, Enter)

# Jalankan
chmod +x fix-403.sh
bash fix-403.sh
```

---

## 🎯 Solusi Khusus Laravel

### Laravel Specific Fix:

```bash
cd /www/wwwroot/billing.dasnet.com

# 1. Fix permission
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache

# 2. Create storage link
php artisan storage:link

# 3. Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# 4. Optimize
php artisan optimize

# 5. Check .env
cat .env | grep APP_KEY
# Jika kosong:
php artisan key:generate

# 6. Restart services
systemctl restart nginx
systemctl restart php-fpm-81
```

---

## 📞 Quick Commands

```bash
# Fix permission (paling sering berhasil)
cd /www/wwwroot/billing.dasnet.com
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache
systemctl restart nginx

# Check logs
tail -f /www/server/nginx/logs/error.log

# Test
curl http://localhost
```

---

## 🆘 Masih Error?

Jika masih error 403 setelah semua solusi di atas:

1. **Screenshot error** dan kirim ke saya
2. **Check error log:**
   ```bash
   tail -n 50 /www/server/nginx/logs/error.log
   ```
3. **Check permission:**
   ```bash
   ls -la /www/wwwroot/billing.dasnet.com/public/
   ```
4. **Check Nginx config:**
   ```bash
   cat /www/server/panel/vhost/nginx/billing.dasnet.com.conf
   ```

---

## ✅ Setelah Fix

Setelah error 403 teratasi:

```bash
# 1. Test local
curl http://localhost

# 2. Test via IP
curl http://your-server-ip

# 3. Test via domain (jika sudah setup Cloudflare)
curl https://billing.dasnet.com

# 4. Buka di browser
```

---

**Solusi paling sering berhasil: Fix permission & ownership!**

Coba jalankan ini dulu:
```bash
cd /www/wwwroot/billing.dasnet.com
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache
systemctl restart nginx
```

Good luck! 🚀
