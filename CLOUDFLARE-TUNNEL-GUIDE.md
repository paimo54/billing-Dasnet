# 🚀 Panduan Lengkap Cloudflare Tunnel

## 📦 Script yang Tersedia

Saya sudah membuatkan 3 script untuk Anda:

### 1. **install-cloudflare-tunnel.sh** - Auto Installer
Script untuk install cloudflared secara otomatis di Linux/aaPanel

### 2. **uninstall-cloudflare-tunnel.sh** - Uninstaller
Script untuk uninstall cloudflared secara lengkap

### 3. **manage-cloudflare-tunnel.sh** - Management Panel
Script untuk manage tunnel dengan menu interaktif

---

## 🐧 Cara Pakai di Linux/aaPanel

### Install Cloudflare Tunnel

```bash
# 1. Upload file install-cloudflare-tunnel.sh ke server
# 2. Berikan permission execute
chmod +x install-cloudflare-tunnel.sh

# 3. Jalankan installer
sudo bash install-cloudflare-tunnel.sh
```

Script akan otomatis:
- ✅ Detect architecture (x86_64/ARM)
- ✅ Download cloudflared
- ✅ Install cloudflared
- ✅ Login ke Cloudflare
- ✅ Create tunnel
- ✅ Configure tunnel
- ✅ Route DNS
- ✅ Install sebagai service
- ✅ Test connection

### Manage Tunnel

```bash
# Berikan permission
chmod +x manage-cloudflare-tunnel.sh

# Jalankan management panel
sudo bash manage-cloudflare-tunnel.sh
```

Menu yang tersedia:
1. Show Tunnel Status
2. List All Tunnels
3. Show Tunnel Info
4. View Service Logs
5. Start Service
6. Stop Service
7. Restart Service
8. Create New Tunnel
9. Delete Tunnel
10. Update Configuration
11. Test Connection
12. Show Configuration

### Uninstall

```bash
# Berikan permission
chmod +x uninstall-cloudflare-tunnel.sh

# Jalankan uninstaller
sudo bash uninstall-cloudflare-tunnel.sh
```

---

## 🪟 Cara Install di Windows (Yang Anda Coba)

Saya lihat Anda sudah punya token Cloudflare. Berikut langkah lengkapnya:

### 1. Download Cloudflared untuk Windows

```powershell
# Download dari GitHub
# https://github.com/cloudflare/cloudflared/releases/latest
# Pilih: cloudflared-windows-amd64.exe
```

### 2. Install sebagai Service dengan Token

```powershell
# Buka PowerShell/CMD sebagai Administrator
cd C:\cloudflared

# Install service dengan token Anda
cloudflared.exe service install eyJhIjoiMDg3NjU1NzRhMGM5MDAxZGJlZTBlYTEwMGJjODk2ZGQiLCJ0IjoiNjU4MGNlMTktMjdkMy00OTE2LWE3OWQtYjNjNTQxNDY5YTUzIiwicyI6IllXVXdPR1UxTWpBdFltSTNNaTAwTkRsaExUZzFNelV0WlRKaE0yWmtObVJsT0RGaSJ9
```

### 3. Buat Config File

Buat file `config.yml` di folder yang sama dengan cloudflared.exe:

```yaml
tunnel: YOUR_TUNNEL_ID
credentials-file: C:\cloudflared\YOUR_TUNNEL_ID.json

ingress:
  - hostname: billing.yourdomain.com
    service: http://localhost:80
    originRequest:
      noTLSVerify: true
  - service: http_status:404
```

### 4. Start Service

```powershell
# Start service
net start cloudflared

# Atau via Services.msc
# Cari "Cloudflare Tunnel" dan klik Start
```

### 5. Check Status

```powershell
# Check service status
sc query cloudflared

# View logs
cloudflared.exe tunnel info YOUR_TUNNEL_ID
```

---

## 🔧 Troubleshooting Windows

### Error: "Service already exists"
```powershell
# Uninstall dulu
cloudflared.exe service uninstall

# Install ulang
cloudflared.exe service install YOUR_TOKEN
```

### Error: "Access denied"
```powershell
# Pastikan run sebagai Administrator
# Klik kanan PowerShell/CMD -> Run as Administrator
```

### Error: "Config file not found"
```powershell
# Pastikan config.yml ada di folder yang sama
# Atau specify path:
cloudflared.exe --config C:\cloudflared\config.yml tunnel run
```

---

## 📋 Informasi Token Anda

Token yang Anda gunakan:
```
eyJhIjoiMDg3NjU1NzRhMGM5MDAxZGJlZTBlYTEwMGJjODk2ZGQiLCJ0IjoiNjU4MGNlMTktMjdkMy00OTE2LWE3OWQtYjNjNTQxNDY5YTUzIiwicyI6IllXVXdPR1UxTWpBdFltSTNNaTAwTkRsaExUZzFNelV0WlRKaE0yWmtObVJsT0RGaSJ9
```

⚠️ **PENTING**: Jangan share token ini ke orang lain!

---

## 🎯 Rekomendasi Setup

### Untuk Production (aaPanel):
✅ Gunakan **Linux script** (install-cloudflare-tunnel.sh)
- Lebih stabil
- Auto restart on boot
- Mudah di-manage

### Untuk Development (Local Windows):
✅ Gunakan **Windows manual install**
- Cocok untuk testing
- Mudah start/stop

---

## 📞 Command Reference

### Linux Commands
```bash
# Status
systemctl status cloudflared

# Start/Stop/Restart
systemctl start cloudflared
systemctl stop cloudflared
systemctl restart cloudflared

# Logs
journalctl -u cloudflared -f

# List tunnels
cloudflared tunnel list

# Delete tunnel
cloudflared tunnel delete TUNNEL_NAME
```

### Windows Commands
```powershell
# Status
sc query cloudflared

# Start/Stop
net start cloudflared
net stop cloudflared

# Uninstall service
cloudflared.exe service uninstall

# List tunnels
cloudflared.exe tunnel list
```

---

## ✅ Checklist Setup

### Untuk aaPanel (Linux):
- [ ] Upload script ke server
- [ ] Jalankan `bash install-cloudflare-tunnel.sh`
- [ ] Login ke Cloudflare (browser akan terbuka)
- [ ] Input tunnel name
- [ ] Input domain
- [ ] Input port website (biasanya 80)
- [ ] Tunggu DNS propagation (5-10 menit)
- [ ] Test akses domain

### Untuk Windows:
- [ ] Download cloudflared.exe
- [ ] Buat folder C:\cloudflared
- [ ] Copy cloudflared.exe ke folder
- [ ] Buat config.yml
- [ ] Install service dengan token
- [ ] Start service
- [ ] Test akses domain

---

## 🆘 Butuh Bantuan?

Jika ada error atau pertanyaan:

1. **Check logs**:
   - Linux: `journalctl -u cloudflared -f`
   - Windows: Event Viewer -> Windows Logs -> Application

2. **Check service status**:
   - Linux: `systemctl status cloudflared`
   - Windows: `sc query cloudflared`

3. **Test local website**:
   ```bash
   curl http://localhost:PORT
   ```

4. **Test tunnel**:
   ```bash
   curl https://your-domain.com
   ```

---

## 📝 Notes

- DNS propagation bisa memakan waktu 5-10 menit
- Pastikan port website sudah benar
- Pastikan website sudah running di aaPanel
- SSL/TLS otomatis dari Cloudflare (gratis)
- Tidak perlu buka port 80/443 di firewall

---

Apakah Anda ingin setup di **Windows** atau **Linux/aaPanel**?
