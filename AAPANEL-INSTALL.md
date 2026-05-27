# Panduan Instalasi di aaPanel - 1 Klik

## 📋 Persiapan

### 1. Requirements Server
- **OS**: CentOS 7+, Ubuntu 18.04+, atau Debian 9+
- **RAM**: Minimum 2GB (Recommended 4GB+)
- **Storage**: Minimum 20GB
- **aaPanel**: Sudah terinstall
- **PHP**: 8.1 atau 8.2
- **MySQL**: 5.7+ atau MariaDB 10.3+
- **Nginx**: Latest version

### 2. Install aaPanel (Jika Belum)

**CentOS:**
```bash
yum install -y wget && wget -O install.sh http://www.aapanel.com/script/install_6.0_en.sh && bash install.sh aapanel
```

**Ubuntu/Debian:**
```bash
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && sudo bash install.sh aapanel
```

Setelah instalasi, catat:
- aaPanel URL
- Username
- Password

---

## 🚀 Instalasi Otomatis (1 Klik)

### Metode 1: Upload & Install

1. **Upload File ke Server**
   ```bash
   # Login ke server via SSH
   ssh root@your-server-ip
   
   # Buat direktori temporary
   mkdir -p /tmp/billing-install
   cd /tmp/billing-install
   ```

2. **Upload Files**
   - Upload semua file project ke `/tmp/billing-install/`
   - Atau clone dari Git:
   ```bash
   git clone https://github.com/yourusername/billing-Dasnet.git /tmp/billing-install/
   cd /tmp/billing-install
   ```

3. **Jalankan Installer**
   ```bash
   chmod +x install.sh
   sudo ./install.sh
   ```

4. **Ikuti Prompt**
   - Masukkan domain name (e.g., billing.yourdomain.com)
   - Masukkan database name (default: billing_dasnet)
   - Masukkan database user (default: billing_user)
   - Pilih PHP version (default: 8.1)
   - Konfirmasi instalasi

5. **Tunggu Proses Selesai**
   - Installer akan otomatis:
     - Install dependencies
     - Create database
     - Setup website
     - Install Composer packages
     - Run migrations
     - Configure Nginx
     - Setup queue worker (Supervisor)
     - Setup cron job

---

### Metode 2: One-Line Install (Dari Git)

```bash
wget -O - https://raw.githubusercontent.com/yourusername/billing-Dasnet/master/install.sh | sudo bash
```

---

## 📝 Yang Dilakukan Installer

### 1. System Setup
- ✅ Install system dependencies (git, unzip, curl, wget)
- ✅ Check aaPanel installation
- ✅ Verify PHP version

### 2. Database Setup
- ✅ Create MySQL database
- ✅ Create database user with secure password
- ✅ Grant privileges

### 3. Application Setup
- ✅ Create website directory
- ✅ Copy application files
- ✅ Install Composer dependencies
- ✅ Configure .env file
- ✅ Generate application key
- ✅ Run database migrations
- ✅ Seed default users (optional)

### 4. Web Server Setup
- ✅ Configure Nginx virtual host
- ✅ Setup SSL-ready configuration
- ✅ Configure PHP-FPM
- ✅ Set security headers

### 5. Queue Worker Setup
- ✅ Install Supervisor
- ✅ Configure queue worker (4 processes)
- ✅ Auto-restart on failure

### 6. Cron Job Setup
- ✅ Setup Laravel scheduler
- ✅ Auto-run every minute

### 7. Optimization
- ✅ Cache configuration
- ✅ Cache routes
- ✅ Cache views
- ✅ Set proper permissions

---

## 🔐 Setelah Instalasi

### 1. Akses Aplikasi

URL: `https://your-domain.com`

**Default Login:**
- **SuperAdmin**: superadmin@example.com / password
- **Admin**: admin@example.com / password
- **Technician**: technician@example.com / password

⚠️ **PENTING**: Ganti semua password default segera!

### 2. Configure SSL Certificate

Di aaPanel:
1. Login ke aaPanel
2. Go to **Website** → Pilih domain Anda
3. Klik **SSL** tab
4. Pilih **Let's Encrypt** atau upload certificate
5. Enable **Force HTTPS**

### 3. Configure Payment Gateway

Edit file `.env`:
```bash
nano /www/wwwroot/billing-dasnet/.env
```

Tambahkan:
```env
# Duitku Configuration
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_api_key
DUITKU_CALLBACK_URL=https://yourdomain.com/api/payment/duitku/callback

# QRIS Configuration
QRIS_MERCHANT_ID=your_merchant_id
QRIS_API_KEY=your_api_key
QRIS_CALLBACK_URL=https://yourdomain.com/api/payment/qris/callback
```

Restart PHP-FPM:
```bash
systemctl restart php-fpm-81
```

### 4. Configure Network Management (Optional)

Jika menggunakan FreeRADIUS:

```bash
# Install FreeRADIUS
yum install -y freeradius freeradius-mysql  # CentOS
# atau
apt-get install -y freeradius freeradius-mysql  # Ubuntu/Debian

# Configure FreeRADIUS
nano /etc/raddb/mods-available/sql  # CentOS
# atau
nano /etc/freeradius/3.0/mods-available/sql  # Ubuntu/Debian
```

Update database connection:
```
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"
    server = "localhost"
    port = 3306
    login = "billing_user"
    password = "your_db_password"
    radius_db = "billing_dasnet"
}
```

Enable SQL module:
```bash
ln -s /etc/raddb/mods-available/sql /etc/raddb/mods-enabled/  # CentOS
# atau
ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/  # Ubuntu

# Start FreeRADIUS
systemctl start radiusd  # CentOS
# atau
systemctl start freeradius  # Ubuntu
systemctl enable freeradius
```

### 5. Add Mikrotik Router

Login ke aplikasi → Admin → Routers → Add New Router

Atau via database:
```sql
INSERT INTO routers (name, identity, host, port, username, password, region, max_capacity, radius_secret, use_radius, priority, auto_assign, status, created_at, updated_at)
VALUES ('Router 1', 'MT-ROUTER-01', '192.168.1.1', 8728, 'admin', 'your_password', 'Jakarta', 1000, 'testing123', 1, 10, 1, 'active', NOW(), NOW());
```

### 6. Add Coverage Area

Login ke aplikasi → Admin → Coverage Areas → Add New Coverage Area

### 7. Monitor Queue Worker

```bash
# Check status
supervisorctl status billing-dasnet-worker:*

# View logs
tail -f /www/wwwroot/billing-dasnet/storage/logs/worker.log

# Restart worker
supervisorctl restart billing-dasnet-worker:*
```

### 8. Monitor Application Logs

```bash
# Application logs
tail -f /www/wwwroot/billing-dasnet/storage/logs/laravel.log

# Nginx access logs
tail -f /www/wwwlogs/your-domain.com.log

# Nginx error logs
tail -f /www/wwwlogs/your-domain.com.error.log
```

---

## 🔧 Troubleshooting

### Issue: Permission Denied

```bash
cd /www/wwwroot/billing-dasnet
chown -R www:www .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### Issue: 500 Internal Server Error

```bash
# Check logs
tail -f /www/wwwroot/billing-dasnet/storage/logs/laravel.log

# Clear cache
cd /www/wwwroot/billing-dasnet
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Issue: Database Connection Error

```bash
# Check .env file
nano /www/wwwroot/billing-dasnet/.env

# Test database connection
mysql -u billing_user -p billing_dasnet
```

### Issue: Queue Not Working

```bash
# Check supervisor status
supervisorctl status

# Restart supervisor
supervisorctl restart billing-dasnet-worker:*

# Check worker logs
tail -f /www/wwwroot/billing-dasnet/storage/logs/worker.log
```

### Issue: Cron Not Running

```bash
# Check crontab
crontab -l

# Add manually if missing
crontab -e
# Add this line:
* * * * * cd /www/wwwroot/billing-dasnet && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Performance Optimization

### 1. Enable OPcache

Di aaPanel:
1. Go to **App Store** → **PHP 8.1** → **Settings**
2. Enable **opcache** extension
3. Configure opcache:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.interned_strings_buffer=16
   opcache.max_accelerated_files=10000
   opcache.revalidate_freq=2
   ```

### 2. Enable Redis (Optional)

```bash
# Install Redis
yum install -y redis  # CentOS
# atau
apt-get install -y redis-server  # Ubuntu

# Start Redis
systemctl start redis
systemctl enable redis

# Install PHP Redis extension via aaPanel
# App Store → PHP 8.1 → Install Extensions → redis
```

Update `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Optimize MySQL

```sql
-- Add to MySQL config
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 500
query_cache_size = 64M
```

---

## 🔒 Security Checklist

- [ ] Change all default passwords
- [ ] Enable SSL certificate
- [ ] Configure firewall (allow only 80, 443, 22, 8888)
- [ ] Disable root SSH login
- [ ] Enable fail2ban
- [ ] Regular backups (database + files)
- [ ] Update .env with strong APP_KEY
- [ ] Restrict database access to localhost only
- [ ] Enable aaPanel two-factor authentication

---

## 📦 Backup & Restore

### Backup

```bash
# Backup database
mysqldump -u billing_user -p billing_dasnet > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf billing_files_$(date +%Y%m%d).tar.gz /www/wwwroot/billing-dasnet

# Backup .env
cp /www/wwwroot/billing-dasnet/.env /root/env_backup_$(date +%Y%m%d)
```

### Restore

```bash
# Restore database
mysql -u billing_user -p billing_dasnet < backup_20260527.sql

# Restore files
tar -xzf billing_files_20260527.tar.gz -C /
```

---

## 📞 Support

Jika mengalami masalah:
1. Check logs: `/www/wwwroot/billing-dasnet/storage/logs/`
2. Check Nginx logs: `/www/wwwlogs/`
3. Check installation info: `/www/wwwroot/billing-dasnet/INSTALLATION_INFO.txt`
4. Create issue on GitHub

---

## 📝 Notes

- Installer akan menyimpan informasi instalasi di: `/www/wwwroot/billing-dasnet/INSTALLATION_INFO.txt`
- Database password di-generate secara otomatis untuk keamanan
- Queue worker berjalan dengan 4 processes
- Cron job berjalan setiap menit untuk Laravel scheduler
- Semua logs tersimpan di `storage/logs/`

---

**Version**: 2.4.0  
**Last Updated**: 2026-05-27  
**Tested on**: aaPanel 6.8.x, CentOS 7/8, Ubuntu 20.04/22.04
