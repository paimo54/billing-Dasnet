# 🚀 Panduan Install Cloudflare Tunnel dengan Token Anda

Token Anda: `eyJhIjoiMDg3NjU1NzRhMGM5MDAxZGJlZTBlYTEwMGJjODk2ZGQiLCJ0IjoiNjU4MGNlMTktMjdkMy00OTE2LWE3OWQtYjNjNTQxNDY5YTUzIiwicyI6IllXVXdPR1UxTWpBdFltSTNNaTAwTkRsaExUZzFNelV0WlRKaE0yWmtObVJsT0RGaSJ9`

⚠️ **PENTING**: Token ini adalah credentials tunnel Anda. Jangan share ke orang lain!

---

## 📋 Persiapan

### Yang Anda Butuhkan:
1. ✅ Token Cloudflare (sudah ada)
2. ✅ Tunnel ID (dari Cloudflare dashboard)
3. ✅ Domain yang sudah terdaftar di Cloudflare
4. ✅ Port website di aaPanel (biasanya 80)

### Cara Mendapatkan Tunnel ID:
1. Login ke: https://one.dash.cloudflare.com/
2. Pilih **Zero Trust** di sidebar
3. Klik **Networks** → **Tunnels**
4. Lihat tunnel yang sudah dibuat
5. Copy **Tunnel ID** (format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)

---

## 🐧 Cara Install di Linux/aaPanel

### Metode 1: Menggunakan Script Auto Installer (RECOMMENDED)

```bash
# 1. Upload file install-with-token.sh ke server via aaPanel File Manager
#    atau via SCP/SFTP

# 2. Login ke SSH
ssh root@your-server-ip

# 3. Masuk ke folder tempat file di-upload
cd /root  # atau folder lain tempat Anda upload

# 4. Berikan permission execute
chmod +x install-with-token.sh

# 5. Jalankan installer
sudo bash install-with-token.sh
```

### Apa yang Akan Ditanyakan:
1. **Tunnel ID**: Masukkan Tunnel ID dari Cloudflare dashboard
2. **Domain**: Masukkan domain Anda (contoh: billing.dasnet.com)
3. **Port**: Masukkan port website di aaPanel (default: 80)

### Script Akan Otomatis:
- ✅ Download & install cloudflared
- ✅ Setup credentials dengan token Anda
- ✅ Buat config file
- ✅ Install sebagai service
- ✅ Start service
- ✅ Test connection

---

## 🪟 Cara Install di Windows

### Langkah-langkah:

#### 1. Download Cloudflared
```
https://github.com/cloudflare/cloudflared/releases/latest
```
- Pilih: **cloudflared-windows-amd64.exe**
- Rename menjadi: **cloudflared.exe**

#### 2. Persiapkan File
```
C:\cloudflared\
├── cloudflared.exe
└── install-windows-with-token.bat
```

#### 3. Jalankan Installer
- Klik kanan pada **install-windows-with-token.bat**
- Pilih **"Run as administrator"**

#### 4. Masukkan Informasi
Script akan menanyakan:
- **Tunnel ID**: (dari Cloudflare dashboard)
- **Domain**: billing.dasnet.com
- **Port**: 80 (atau port lain)

#### 5. Konfigurasi DNS
Ikuti instruksi di layar untuk setup DNS di Cloudflare

---

## 🌐 Konfigurasi DNS di Cloudflare

Setelah install, Anda HARUS setup DNS:

### Langkah-langkah:

1. **Login ke Cloudflare Dashboard**
   ```
   https://dash.cloudflare.com
   ```

2. **Pilih Domain Anda**

3. **Masuk ke DNS Settings**
   - Klik menu **DNS** di sidebar

4. **Tambah CNAME Record**
   - Klik **Add record**
   - Type: **CNAME**
   - Name: **billing** (atau subdomain lain)
   - Target: **[TUNNEL_ID].cfargotunnel.com**
   - Proxy status: **Proxied** (orange cloud) ✅
   - TTL: **Auto**
   - Klik **Save**

### Contoh:
```
Type: CNAME
Name: billing
Target: 6580ce19-27d3-4916-a79d-b3c541469a53.cfargotunnel.com
Proxy: Enabled (🟠)
```

---

## ✅ Verifikasi Installation

### Di Linux:
```bash
# Check service status
systemctl status cloudflared

# View logs
journalctl -u cloudflared -f

# Test local
curl http://localhost:80

# Test domain
curl https://billing.dasnet.com
```

### Di Windows:
```powershell
# Check service status
sc query cloudflared

# Test local
curl http://localhost:80

# Test domain
curl https://billing.dasnet.com
```

---

## 🔧 Troubleshooting

### Issue 1: Service tidak start

**Linux:**
```bash
# Check logs
journalctl -u cloudflared -n 50

# Check config
cat ~/.cloudflared/config.yml

# Restart service
systemctl restart cloudflared
```

**Windows:**
```powershell
# Check Event Viewer
eventvwr.msc
# Windows Logs → Application → Filter by "cloudflared"

# Restart service
net stop cloudflared
net start cloudflared
```

### Issue 2: Domain tidak bisa diakses

**Kemungkinan penyebab:**
1. ❌ DNS belum dikonfigurasi di Cloudflare
2. ❌ DNS masih propagating (tunggu 5-10 menit)
3. ❌ Port salah (cek port website di aaPanel)
4. ❌ Website tidak running di aaPanel

**Solusi:**
```bash
# 1. Cek website local
curl http://localhost:80

# 2. Cek port yang benar
netstat -tulpn | grep :80

# 3. Cek DNS
nslookup billing.dasnet.com

# 4. Cek tunnel status
cloudflared tunnel info [TUNNEL_ID]
```

### Issue 3: "Tunnel credentials file not found"

**Solusi:**
```bash
# Linux
ls -la ~/.cloudflared/

# Pastikan ada file:
# - config.yml
# - [TUNNEL_ID].json

# Jika tidak ada, jalankan ulang installer
```

### Issue 4: Port 80 sudah digunakan

**Cek port website di aaPanel:**
1. Login ke aaPanel
2. Klik **Website**
3. Klik website Anda
4. Lihat **Port** yang digunakan
5. Update config dengan port yang benar

**Edit config:**
```bash
# Linux
nano ~/.cloudflared/config.yml

# Ubah port:
service: http://localhost:8080  # ganti dengan port yang benar

# Restart service
systemctl restart cloudflared
```

---

## 📊 Informasi Token Anda

### Token Details:
```json
{
  "AccountTag": "087655740c9001dbee0ea100bc896dd",
  "TunnelSecret": "YWUwOGU1MjAtYmI3Mi00NDlhLTg1MzUtZTJhM2ZkNmRlODFi",
  "TunnelID": "[YOUR_TUNNEL_ID]"
}
```

### Token ini berisi:
- ✅ Account ID Cloudflare Anda
- ✅ Tunnel Secret (credentials)
- ✅ Tunnel ID reference

---

## 🎯 Checklist Setup

### Sebelum Install:
- [ ] Punya Tunnel ID dari Cloudflare
- [ ] Domain sudah terdaftar di Cloudflare
- [ ] Tahu port website di aaPanel
- [ ] Punya akses SSH (Linux) atau Admin (Windows)

### Setelah Install:
- [ ] Service cloudflared running
- [ ] Config file sudah benar
- [ ] DNS CNAME sudah ditambahkan di Cloudflare
- [ ] Website bisa diakses via domain
- [ ] SSL/HTTPS otomatis aktif

---

## 📞 Command Reference

### Service Management (Linux):
```bash
# Status
systemctl status cloudflared

# Start
systemctl start cloudflared

# Stop
systemctl stop cloudflared

# Restart
systemctl restart cloudflared

# Enable auto-start
systemctl enable cloudflared

# Disable auto-start
systemctl disable cloudflared

# View logs
journalctl -u cloudflared -f
```

### Service Management (Windows):
```powershell
# Status
sc query cloudflared

# Start
net start cloudflared

# Stop
net stop cloudflared

# Restart
net stop cloudflared && net start cloudflared

# Uninstall service
cloudflared.exe service uninstall
```

### Tunnel Commands:
```bash
# List tunnels
cloudflared tunnel list

# Info tunnel
cloudflared tunnel info [TUNNEL_ID]

# Delete tunnel
cloudflared tunnel delete [TUNNEL_ID]

# Run tunnel manually (for testing)
cloudflared tunnel run [TUNNEL_ID]
```

---

## 🆘 Butuh Bantuan?

### Jika masih ada masalah:

1. **Check logs** untuk error message
2. **Verify** semua konfigurasi sudah benar
3. **Test** local website dulu (http://localhost:PORT)
4. **Wait** 5-10 menit untuk DNS propagation
5. **Contact** support jika masih error

### Useful Links:
- Cloudflare Dashboard: https://dash.cloudflare.com
- Cloudflare Zero Trust: https://one.dash.cloudflare.com
- Cloudflare Docs: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/

---

## ✨ Keuntungan Menggunakan Cloudflare Tunnel

✅ **Gratis SSL/TLS** - HTTPS otomatis  
✅ **DDoS Protection** - Dari Cloudflare  
✅ **No Port Forwarding** - Tidak perlu buka port 80/443  
✅ **Behind NAT/Firewall** - Tetap bisa diakses  
✅ **IP Private** - Tidak perlu IP public  
✅ **Fast & Secure** - CDN Cloudflare global  

---

Selamat mencoba! Jika ada pertanyaan, silakan tanya. 🚀
