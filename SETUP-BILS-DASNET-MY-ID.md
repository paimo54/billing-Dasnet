# 🚀 Setup Cloudflare Tunnel untuk bils.dasnet.my.id

## 📋 Informasi Domain Anda

```
Domain: bils.dasnet.my.id
Subdomain: bils
Root Domain: dasnet.my.id
TLD: .my.id (Indonesia)
```

---

## ⚡ Quick Setup untuk bils.dasnet.my.id

### STEP 1: Setup Website di aaPanel

```bash
# 1. Login aaPanel
http://your-server-ip:7800

# 2. Website → Add site
Domain: bils.dasnet.my.id
PHP Version: 8.1
Database: MySQL (jika perlu)

# 3. Upload Laravel ke:
/www/wwwroot/bils.dasnet.my.id

# 4. SSH dan setup Laravel
ssh root@your-server-ip

cd /www/wwwroot/bils.dasnet.my.id

# Install dependencies
composer install

# Setup .env
cp .env.example .env
nano .env

# Update .env:
APP_URL=https://bils.dasnet.my.id
APP_ENV=production
APP_DEBUG=false

DB_DATABASE=billing_dasnet
DB_USERNAME=root
DB_PASSWORD=your_password

# Generate key & migrate
php artisan key:generate
php artisan migrate

# Fix permission
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache

# 5. Set document root ke /public
# aaPanel → Website → Site directory → Running directory: /public

# 6. Test local
curl http://localhost
```

---

### STEP 2: Install Cloudflare Tunnel

```bash
# Upload script install-with-token.sh ke server

# Jalankan installer
chmod +x install-with-token.sh
bash install-with-token.sh

# Saat ditanya, input:
Tunnel ID: [paste dari Cloudflare dashboard]
Domain: bils.dasnet.my.id
Port: 80
```

**Cara dapat Tunnel ID:**
```
1. https://one.dash.cloudflare.com/
2. Zero Trust → Networks → Tunnels
3. Copy Tunnel ID
```

---

### STEP 3: Setup DNS di Cloudflare

#### A. Tambah Domain ke Cloudflare (Jika Belum)

```
1. https://dash.cloudflare.com
2. Add a Site
3. Domain: dasnet.my.id
4. Plan: Free
5. Continue
6. Copy nameservers yang diberikan
```

#### B. Update Nameservers di Registrar

Jika domain **dasnet.my.id** terdaftar di:

**Rumahweb / IDCloudHost / Niagahoster:**
```
1. Login ke panel domain
2. Kelola Domain → dasnet.my.id
3. Nameservers → Custom
4. Ganti dengan nameservers Cloudflare:
   - ns1.cloudflare.com
   - ns2.cloudflare.com
5. Save
6. Tunggu propagasi (1-24 jam)
```

**Cloudflare Registrar:**
```
Sudah otomatis menggunakan Cloudflare nameservers
```

#### C. Tambah CNAME Record

```
1. Cloudflare dashboard → dasnet.my.id
2. DNS → Add record
3. Isi:
   Type: CNAME
   Name: bils
   Target: [TUNNEL_ID].cfargotunnel.com
   Proxy: Proxied (🟠 ON)
   TTL: Auto
4. Save
```

**Contoh:**
```
Type: CNAME
Name: bils
Target: 6580ce19-27d3-4916-a79d-b3c541469a53.cfargotunnel.com
Proxy: Proxied (🟠)
```

#### D. Setup SSL/TLS

```
1. Cloudflare → SSL/TLS
2. Overview → Encryption mode: Full
3. Save
```

---

### STEP 4: Verifikasi

```bash
# 1. Check service
systemctl status cloudflared

# 2. Check logs
journalctl -u cloudflared -f

# 3. Test local
curl http://localhost

# 4. Test domain (tunggu 5-10 menit)
curl https://bils.dasnet.my.id

# 5. Buka di browser
https://bils.dasnet.my.id
```

---

## 🔧 Konfigurasi File

### Config Cloudflared

File: `~/.cloudflared/config.yml`

```yaml
tunnel: YOUR_TUNNEL_ID
credentials-file: /root/.cloudflared/YOUR_TUNNEL_ID.json

ingress:
  - hostname: bils.dasnet.my.id
    service: http://localhost:80
    originRequest:
      noTLSVerify: true
  - service: http_status:404
```

### Laravel .env

File: `/www/wwwroot/bils.dasnet.my.id/.env`

```env
APP_NAME="Billing Dasnet"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://bils.dasnet.my.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=billing_dasnet
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Nginx Config

File: `/www/server/panel/vhost/nginx/bils.dasnet.my.id.conf`

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

---

## 🐛 Troubleshooting

### Issue 1: Domain tidak bisa diakses

**Kemungkinan:**
- Nameservers belum diupdate
- DNS belum propagasi
- CNAME belum ditambahkan

**Solusi:**
```bash
# Check nameservers
nslookup dasnet.my.id

# Check DNS
nslookup bils.dasnet.my.id

# Check online
https://dnschecker.org
```

### Issue 2: Error 403 Forbidden

**Solusi:**
```bash
cd /www/wwwroot/bils.dasnet.my.id
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache
systemctl restart nginx
```

### Issue 3: Error 502 Bad Gateway

**Solusi:**
```bash
# Restart services
systemctl restart nginx
systemctl restart php-fpm-81
systemctl restart cloudflared

# Check logs
journalctl -u cloudflared -f
```

### Issue 4: Error 521 Web Server Down

**Solusi:**
```bash
# Check config
cat ~/.cloudflared/config.yml

# Pastikan port 80
# Test local
curl http://localhost:80

# Restart cloudflared
systemctl restart cloudflared
```

---

## ✅ Checklist Setup

### aaPanel:
- [ ] Website created: bils.dasnet.my.id
- [ ] Laravel uploaded
- [ ] Composer install done
- [ ] .env configured
- [ ] Database migrated
- [ ] Permissions fixed
- [ ] Document root: /public
- [ ] Test local OK

### Cloudflare Tunnel:
- [ ] Tunnel ID obtained
- [ ] Script uploaded
- [ ] Cloudflared installed
- [ ] Config created
- [ ] Service running
- [ ] No errors in logs

### DNS Cloudflare:
- [ ] Domain added to Cloudflare
- [ ] Nameservers updated
- [ ] CNAME record added (bils)
- [ ] Proxy enabled (🟠)
- [ ] SSL/TLS: Full
- [ ] DNS propagated
- [ ] Domain accessible

---

## 📞 Command Reference

```bash
# Service Management
systemctl status cloudflared
systemctl restart cloudflared
journalctl -u cloudflared -f

# Test Connection
curl http://localhost:80
curl https://bils.dasnet.my.id

# Fix Permission
cd /www/wwwroot/bils.dasnet.my.id
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache

# Laravel Commands
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check DNS
nslookup bils.dasnet.my.id
dig bils.dasnet.my.id
```

---

## 🎯 Quick Commands untuk bils.dasnet.my.id

```bash
# Setup Laravel
cd /www/wwwroot/bils.dasnet.my.id
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache

# Install Cloudflare Tunnel
bash install-with-token.sh
# Input: Tunnel ID, bils.dasnet.my.id, 80

# Test
curl http://localhost
curl https://bils.dasnet.my.id
```

---

## 🌐 DNS Records di Cloudflare

Setelah setup, DNS records Anda akan seperti ini:

```
Type    Name    Content                                 Proxy
CNAME   bils    [TUNNEL_ID].cfargotunnel.com           Proxied (🟠)
```

**Full domain:** bils.dasnet.my.id → [TUNNEL_ID].cfargotunnel.com

---

## 🆘 Need Help?

Jika ada masalah:

1. **Check logs:**
   ```bash
   journalctl -u cloudflared -f
   tail -f /www/server/nginx/logs/error.log
   ```

2. **Check DNS:**
   ```bash
   nslookup bils.dasnet.my.id
   ```

3. **Test local:**
   ```bash
   curl http://localhost:80
   ```

4. **Check config:**
   ```bash
   cat ~/.cloudflared/config.yml
   ```

---

## ✨ Setelah Setup Berhasil

Aplikasi Billing Dasnet Anda akan bisa diakses di:

🌐 **https://bils.dasnet.my.id**

Dengan fitur:
- ✅ HTTPS otomatis (SSL gratis dari Cloudflare)
- ✅ DDoS protection
- ✅ CDN global
- ✅ Fast & secure

---

**Total waktu setup: ~20 menit**

Good luck! 🚀
