# 🚀 Quick Start: aaPanel + Cloudflare Tunnel

## 📋 Persiapan (5 menit)

### 1. Info yang Dibutuhkan:
```
✅ Server IP: ___________________
✅ aaPanel Port: 7800
✅ aaPanel User: ___________________
✅ aaPanel Pass: ___________________
✅ Domain: billing.dasnet.com
✅ Tunnel ID: ___________________
✅ Token: eyJhIjoiMDg3NjU1NzRhMGM5MDAxZGJlZTBlYTEwMGJjODk2ZGQi...
```

### 2. Dapatkan Tunnel ID:
```
https://one.dash.cloudflare.com/
→ Zero Trust → Networks → Tunnels
→ Copy Tunnel ID
```

---

## ⚡ Setup Cepat (15 menit)

### STEP 1: Setup Website di aaPanel (5 menit)

```
1. Login aaPanel: http://SERVER_IP:7800
2. Website → Add site
   - Domain: billing.dasnet.com
   - PHP: 8.1
   - Database: MySQL
3. File → Upload Laravel app ke /www/wwwroot/billing.dasnet.com
4. Terminal:
```

```bash
cd /www/wwwroot/billing.dasnet.com
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
chown -R www:www .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache
```

```
5. Website → Site directory → Running directory: /public
6. Test: curl http://localhost
```

### STEP 2: Install Cloudflare Tunnel (5 menit)

```bash
# Upload install-with-token.sh ke server

# SSH ke server
ssh root@SERVER_IP

# Install
chmod +x install-with-token.sh
bash install-with-token.sh

# Input saat ditanya:
# - Tunnel ID: [paste dari Cloudflare]
# - Domain: billing.dasnet.com
# - Port: 80
```

### STEP 3: Setup DNS di Cloudflare (5 menit)

```
1. https://dash.cloudflare.com
2. Pilih domain: dasnet.com
3. DNS → Add record:
   - Type: CNAME
   - Name: billing
   - Target: [TUNNEL_ID].cfargotunnel.com
   - Proxy: ON (🟠)
4. SSL/TLS → Full
5. Tunggu 5-10 menit
6. Test: https://billing.dasnet.com
```

---

## ✅ Verifikasi

```bash
# 1. Check service
systemctl status cloudflared

# 2. Check local
curl http://localhost:80

# 3. Check domain
curl https://billing.dasnet.com

# 4. Check logs
journalctl -u cloudflared -f
```

---

## 🔧 Troubleshooting Cepat

### Domain tidak bisa diakses?
```bash
# Check service
systemctl restart cloudflared

# Check logs
journalctl -u cloudflared -n 50

# Test local
curl http://localhost:80
```

### Error 502?
```bash
# Restart web server
systemctl restart nginx

# Check website di aaPanel
```

### Error 521?
```bash
# Check config
cat ~/.cloudflared/config.yml

# Pastikan port benar (80)
# Restart
systemctl restart cloudflared
```

---

## 📞 Command Penting

```bash
# Service
systemctl status cloudflared
systemctl restart cloudflared

# Logs
journalctl -u cloudflared -f

# Test
curl http://localhost:80
curl https://billing.dasnet.com

# Config
nano ~/.cloudflared/config.yml
```

---

## 🎯 Checklist

Setup aaPanel:
- [ ] Website created
- [ ] Laravel uploaded
- [ ] Composer install
- [ ] .env configured
- [ ] Database migrated
- [ ] Permissions set
- [ ] Test local OK

Setup Tunnel:
- [ ] Tunnel ID obtained
- [ ] Script uploaded
- [ ] Cloudflared installed
- [ ] Service running
- [ ] No errors in logs

Setup DNS:
- [ ] CNAME added
- [ ] Proxy enabled (🟠)
- [ ] SSL/TLS: Full
- [ ] DNS propagated
- [ ] Domain accessible

---

## 📁 File Locations

```
aaPanel Website:
/www/wwwroot/billing.dasnet.com/

Cloudflare Config:
~/.cloudflared/config.yml
~/.cloudflared/[TUNNEL_ID].json

Logs:
journalctl -u cloudflared
```

---

## 🆘 Need Help?

1. Check logs: `journalctl -u cloudflared -f`
2. Test local: `curl http://localhost:80`
3. Wait 5-10 min for DNS
4. Read full guide: `AAPANEL-CLOUDFLARE-SETUP.md`

---

**Total Time: ~15 menit**
**Difficulty: ⭐⭐☆☆☆ (Easy)**

Good luck! 🚀
