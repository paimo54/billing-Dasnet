# 🌐 Panduan Koneksi Domain aaPanel dengan Cloudflare Tunnel

## 📋 Overview

Untuk menghubungkan website di aaPanel dengan Cloudflare Tunnel, ada beberapa langkah:

1. ✅ Setup website di aaPanel
2. ✅ Install Cloudflare Tunnel
3. ✅ Konfigurasi DNS di Cloudflare
4. ✅ Test koneksi

---

## 🔧 LANGKAH 1: Setup Website di aaPanel

### 1.1 Buat Website di aaPanel

```
1. Login ke aaPanel: http://your-server-ip:7800
2. Klik "Website" di sidebar
3. Klik "Add site"
4. Isi form:
   - Domain: billing.dasnet.com (atau domain Anda)
   - PHP Version: 8.1 atau 8.2
   - Database: MySQL (jika perlu)
5. Klik "Submit"
```

### 1.2 Cek Port Website

```
1. Klik website yang baru dibuat
2. Lihat bagian "Port"
3. Catat port-nya (biasanya 80)
```

**Catatan:** Jika port bukan 80, catat port yang tertera!

### 1.3 Upload Aplikasi Laravel

```
1. Klik "File" di sidebar aaPanel
2. Masuk ke folder: /www/wwwroot/billing.dasnet.com
3. Upload file aplikasi Laravel Anda
4. Extract jika dalam bentuk zip
```

### 1.4 Setup Laravel di aaPanel

```bash
# SSH ke server
ssh root@your-server-ip

# Masuk ke folder website
cd /www/wwwroot/billing.dasnet.com

# Install dependencies
composer install

# Setup .env
cp .env.example .env
nano .env

# Generate key
php artisan key:generate

# Migrate database
php artisan migrate

# Set permission
chown -R www:www /www/wwwroot/billing.dasnet.com
chmod -R 755 /www/wwwroot/billing.dasnet.com
chmod -R 777 storage bootstrap/cache
```

### 1.5 Konfigurasi Nginx/Apache di aaPanel

**Untuk Laravel, pastikan document root mengarah ke folder `public`:**

```
1. Klik website di aaPanel
2. Klik "Site directory"
3. Set "Running directory" ke: /public
4. Enable "Prevent cross-site access"
5. Klik "Save"
```

### 1.6 Test Website Local

```bash
# Test dari server
curl http://localhost:80

# Atau test dengan IP
curl http://your-server-ip
```

Jika muncul halaman Laravel, berarti website sudah jalan! ✅

---

## 🚇 LANGKAH 2: Install Cloudflare Tunnel

### 2.1 Dapatkan Tunnel ID

```
1. Login: https://one.dash.cloudflare.com/
2. Pilih account Anda
3. Klik "Zero Trust" di sidebar
4. Klik "Networks" → "Tunnels"
5. Jika belum ada tunnel, klik "Create a tunnel"
   - Name: billing-dasnet
   - Klik "Save tunnel"
6. Copy Tunnel ID (format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
```

### 2.2 Install Cloudflare Tunnel di Server

```bash
# Upload script ke server
# Gunakan FileZilla, WinSCP, atau aaPanel File Manager

# SSH ke server
ssh root@your-server-ip

# Masuk ke folder script
cd /root

# Berikan permission
chmod +x install-with-token.sh

# Jalankan installer
bash install-with-token.sh
```

### 2.3 Input Konfigurasi

Script akan menanyakan:

```
Enter your Tunnel ID: [paste Tunnel ID dari step 2.1]
Enter your domain: billing.dasnet.com
Enter website port: 80
```

**PENTING:** 
- Tunnel ID: dari Cloudflare dashboard
- Domain: domain lengkap Anda
- Port: port website di aaPanel (biasanya 80)

---

## 🌐 LANGKAH 3: Konfigurasi DNS di Cloudflare

### 3.1 Tambah Domain ke Cloudflare (Jika Belum)

```
1. Login: https://dash.cloudflare.com
2. Klik "Add a Site"
3. Masukkan domain: dasnet.com
4. Pilih plan: Free
5. Klik "Continue"
6. Cloudflare akan scan DNS records
7. Klik "Continue"
8. Copy nameservers yang diberikan
9. Update nameservers di domain registrar Anda
10. Tunggu propagasi (bisa 24 jam)
```

### 3.2 Tambah CNAME Record untuk Tunnel

```
1. Login: https://dash.cloudflare.com
2. Pilih domain: dasnet.com
3. Klik "DNS" di menu atas
4. Klik "Add record"
5. Isi form:
   - Type: CNAME
   - Name: billing (atau subdomain lain)
   - Target: [TUNNEL_ID].cfargotunnel.com
   - Proxy status: Proxied (🟠 orange cloud)
   - TTL: Auto
6. Klik "Save"
```

**Contoh:**
```
Type: CNAME
Name: billing
Target: 6580ce19-27d3-4916-a79d-b3c541469a53.cfargotunnel.com
Proxy: Proxied (🟠)
```

### 3.3 Konfigurasi SSL/TLS

```
1. Masih di Cloudflare dashboard
2. Klik "SSL/TLS" di menu atas
3. Pilih mode: "Full" atau "Full (strict)"
4. Klik "Save"
```

**Rekomendasi:** Gunakan "Full" untuk aaPanel

---

## ✅ LANGKAH 4: Verifikasi & Testing

### 4.1 Check Service Status

```bash
# Check cloudflared service
systemctl status cloudflared

# Jika tidak running, start:
systemctl start cloudflared

# View logs
journalctl -u cloudflared -f
```

### 4.2 Test Local Connection

```bash
# Test website local
curl http://localhost:80

# Harus return HTML Laravel
```

### 4.3 Test Domain Connection

```bash
# Test domain (tunggu 5-10 menit untuk DNS propagation)
curl https://billing.dasnet.com

# Atau buka di browser
```

### 4.4 Check DNS Propagation

```bash
# Check DNS
nslookup billing.dasnet.com

# Atau gunakan online tool:
# https://dnschecker.org
```

---

## 🔧 Konfigurasi Lanjutan

### Update Laravel .env untuk Domain

```bash
# Edit .env
nano /www/wwwroot/billing.dasnet.com/.env

# Update:
APP_URL=https://billing.dasnet.com
APP_ENV=production
APP_DEBUG=false

# Save dan exit (Ctrl+X, Y, Enter)

# Clear cache
cd /www/wwwroot/billing.dasnet.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Konfigurasi Multiple Domains

Jika Anda punya beberapa subdomain:

```yaml
# Edit config cloudflared
nano ~/.cloudflared/config.yml

# Tambahkan multiple hostname:
tunnel: YOUR_TUNNEL_ID
credentials-file: /root/.cloudflared/YOUR_TUNNEL_ID.json

ingress:
  - hostname: billing.dasnet.com
    service: http://localhost:80
  - hostname: api.dasnet.com
    service: http://localhost:8080
  - hostname: admin.dasnet.com
    service: http://localhost:8081
  - service: http_status:404

# Save dan restart
systemctl restart cloudflared
```

Lalu tambahkan CNAME untuk setiap subdomain di Cloudflare DNS.

---

## 🐛 Troubleshooting

### Issue 1: Domain tidak bisa diakses

**Kemungkinan:**
1. DNS belum propagasi (tunggu 5-10 menit)
2. Cloudflared service tidak running
3. Port salah
4. Website tidak running di aaPanel

**Solusi:**
```bash
# 1. Check service
systemctl status cloudflared

# 2. Check logs
journalctl -u cloudflared -n 50

# 3. Test local
curl http://localhost:80

# 4. Check port
netstat -tulpn | grep :80

# 5. Restart service
systemctl restart cloudflared
```

### Issue 2: Error 502 Bad Gateway

**Penyebab:** Website tidak running atau port salah

**Solusi:**
```bash
# Check website status di aaPanel
# Pastikan PHP-FPM running

# Check Nginx/Apache
systemctl status nginx
# atau
systemctl status httpd

# Restart web server
systemctl restart nginx
```

### Issue 3: Error 521 Web Server is Down

**Penyebab:** Cloudflared tidak bisa connect ke local website

**Solusi:**
```bash
# Check config
cat ~/.cloudflared/config.yml

# Pastikan port benar
# Test local
curl http://localhost:80

# Check firewall
iptables -L -n | grep 80

# Restart cloudflared
systemctl restart cloudflared
```

### Issue 4: SSL/TLS Error

**Solusi:**
```
1. Buka Cloudflare dashboard
2. SSL/TLS → Overview
3. Ubah mode ke "Full" (bukan Full strict)
4. Tunggu beberapa menit
5. Test lagi
```

### Issue 5: Redirect Loop

**Penyebab:** SSL mode tidak cocok

**Solusi:**
```
1. Cloudflare dashboard → SSL/TLS
2. Ubah ke "Full" atau "Flexible"
3. Clear browser cache
4. Test lagi
```

---

## 📊 Diagram Koneksi

```
User Browser
    ↓
    ↓ HTTPS (443)
    ↓
Cloudflare CDN (DNS: billing.dasnet.com)
    ↓
    ↓ Cloudflare Tunnel
    ↓
Cloudflared Service (Server)
    ↓
    ↓ HTTP (localhost:80)
    ↓
Nginx/Apache (aaPanel)
    ↓
    ↓
Laravel Application (/www/wwwroot/billing.dasnet.com/public)
```

---

## ✅ Checklist Lengkap

### Setup aaPanel:
- [ ] Website sudah dibuat di aaPanel
- [ ] Domain sudah diset
- [ ] Port sudah dicatat
- [ ] Laravel sudah di-upload
- [ ] Composer install sudah dijalankan
- [ ] .env sudah dikonfigurasi
- [ ] Database sudah di-migrate
- [ ] Permission sudah diset
- [ ] Document root sudah ke /public
- [ ] Website bisa diakses via IP

### Setup Cloudflare Tunnel:
- [ ] Tunnel ID sudah didapat
- [ ] Script install-with-token.sh sudah di-upload
- [ ] Cloudflared sudah terinstall
- [ ] Config file sudah dibuat
- [ ] Service sudah running
- [ ] Logs tidak ada error

### Setup DNS Cloudflare:
- [ ] Domain sudah ditambahkan ke Cloudflare
- [ ] Nameservers sudah diupdate
- [ ] CNAME record sudah ditambahkan
- [ ] Proxy status: Proxied (🟠)
- [ ] SSL/TLS mode: Full
- [ ] DNS sudah propagasi

### Testing:
- [ ] curl http://localhost:80 → OK
- [ ] curl https://billing.dasnet.com → OK
- [ ] Browser bisa akses domain
- [ ] HTTPS otomatis aktif
- [ ] No SSL errors
- [ ] Laravel app berjalan normal

---

## 🎯 Tips & Best Practices

### 1. Gunakan Subdomain
```
✅ billing.dasnet.com
✅ app.dasnet.com
❌ dasnet.com (root domain lebih ribet)
```

### 2. Set APP_URL di Laravel
```env
APP_URL=https://billing.dasnet.com
```

### 3. Enable Cloudflare Features
```
- Auto Minify (CSS, JS, HTML)
- Brotli compression
- HTTP/2
- HTTP/3 (QUIC)
```

### 4. Monitor Logs
```bash
# Real-time logs
journalctl -u cloudflared -f

# Last 100 lines
journalctl -u cloudflared -n 100
```

### 5. Backup Config
```bash
# Backup config files
cp ~/.cloudflared/config.yml ~/.cloudflared/config.yml.backup
cp ~/.cloudflared/*.json ~/.cloudflared/backup/
```

---

## 📞 Command Reference

```bash
# Service Management
systemctl status cloudflared
systemctl start cloudflared
systemctl stop cloudflared
systemctl restart cloudflared
systemctl enable cloudflared

# View Logs
journalctl -u cloudflared -f
journalctl -u cloudflared -n 100

# Test Connection
curl http://localhost:80
curl https://billing.dasnet.com

# Check Config
cat ~/.cloudflared/config.yml

# Check DNS
nslookup billing.dasnet.com
dig billing.dasnet.com

# Tunnel Info
cloudflared tunnel list
cloudflared tunnel info [TUNNEL_ID]
```

---

## 🆘 Butuh Bantuan?

Jika masih ada masalah:

1. **Check logs** untuk error message
2. **Verify** semua checklist sudah ✅
3. **Test** step by step dari local ke domain
4. **Wait** untuk DNS propagation (bisa 5-10 menit)
5. **Contact** jika masih stuck

---

Selamat mencoba! Domain aaPanel Anda akan segera terhubung dengan Cloudflare! 🚀
