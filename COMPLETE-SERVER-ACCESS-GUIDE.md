# 🔐 Complete Server Access Info - bils.dasnet.my.id

## 📋 Server Credentials

```
Server IP: 172.13.13.5
SSH Port: 22
SSH User: root
SSH Password: madiun12

aaPanel URL: http://172.13.13.5:8811
aaPanel User: root
aaPanel Password: madiun12

Domain: bils.dasnet.my.id
Website Path: /www/wwwroot/bils.dasnet.my.id
```

⚠️ **SECURITY WARNING**: Ganti password setelah fix error 403!

---

## 🚀 Quick Fix Error 403 - Complete Guide

### METHOD 1: Via aaPanel (EASIEST - 5 minutes)

#### Step 1: Login aaPanel
```
1. Open browser
2. Go to: http://172.13.13.5:8811
3. Username: root
4. Password: madiun12
5. Click "Login"
```

#### Step 2: Fix Permissions
```
1. Click "Website" in left sidebar
2. Find and click "bils.dasnet.my.id"
3. Click "Site directory" tab
4. Scroll down and click "Fix permissions" button
5. Wait for process to complete (10-30 seconds)
```

#### Step 3: Set Document Root
```
Still on the same page:
1. Find "Running directory"
2. Change from "/" to "/public"
3. Click "Save"
```

#### Step 4: Restart Nginx
```
1. Click "App Store" in sidebar
2. Search for "Nginx"
3. Click "Restart" button
4. Wait until complete
```

#### Step 5: Test Website
```
Open new browser tab:
https://bils.dasnet.my.id

Should work now! ✅
```

---

### METHOD 2: Via SSH (ALTERNATIVE - 3 minutes)

#### Step 1: Connect SSH
```bash
# From your computer/terminal
ssh root@172.13.13.5 -p 22

# Or if using PuTTY:
# Host: 172.13.13.5
# Port: 22
# Username: root
# Password: madiun12
```

#### Step 2: Run Fix Commands
```bash
# Navigate to website folder
cd /www/wwwroot/bils.dasnet.my.id

# Fix ownership
chown -R www:www .

# Fix folder permissions
find . -type d -exec chmod 755 {} \;

# Fix file permissions
find . -type f -exec chmod 644 {} \;

# Fix Laravel storage & cache
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

#### Step 3: Verify
```bash
# Check if website returns 200 OK
curl -I https://bils.dasnet.my.id

# Should show: HTTP/1.1 200 OK
```

---

## 🔧 One-Line Fix Command

If you want super quick fix via SSH:

```bash
ssh root@172.13.13.5 -p 22 "cd /www/wwwroot/bils.dasnet.my.id && chown -R www:www . && chmod -R 755 . && chmod -R 777 storage bootstrap/cache && php artisan cache:clear && php artisan config:clear && systemctl restart nginx"
```

Then test: https://bils.dasnet.my.id

---

## 📝 Auto Fix Script

Save this as `fix-bils-403.sh`:

```bash
#!/bin/bash

echo "=========================================="
echo "  Auto Fix 403 for bils.dasnet.my.id"
echo "=========================================="
echo ""

SITE_PATH="/www/wwwroot/bils.dasnet.my.id"

# Check if site exists
if [ ! -d "$SITE_PATH" ]; then
    echo "❌ Error: Website path not found!"
    echo "   Path: $SITE_PATH"
    exit 1
fi

echo "✓ Website path found"
echo ""

# Fix ownership
echo "[1/8] Fixing ownership..."
chown -R www:www $SITE_PATH
echo "✓ Ownership fixed"

# Fix folder permissions
echo "[2/8] Fixing folder permissions..."
find $SITE_PATH -type d -exec chmod 755 {} \;
echo "✓ Folder permissions fixed"

# Fix file permissions
echo "[3/8] Fixing file permissions..."
find $SITE_PATH -type f -exec chmod 644 {} \;
echo "✓ File permissions fixed"

# Fix storage
echo "[4/8] Fixing storage permissions..."
chmod -R 777 $SITE_PATH/storage
echo "✓ Storage permissions fixed"

# Fix bootstrap/cache
echo "[5/8] Fixing cache permissions..."
chmod -R 777 $SITE_PATH/bootstrap/cache
echo "✓ Cache permissions fixed"

# Clear Laravel cache
echo "[6/8] Clearing Laravel cache..."
cd $SITE_PATH
php artisan cache:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
echo "✓ Laravel cache cleared"

# Restart Nginx
echo "[7/8] Restarting Nginx..."
systemctl restart nginx
echo "✓ Nginx restarted"

# Test
echo "[8/8] Testing website..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✓ Local test passed (HTTP $HTTP_CODE)"
else
    echo "⚠ Local test returned HTTP $HTTP_CODE"
fi

echo ""
echo "=========================================="
echo "  Fix Complete!"
echo "=========================================="
echo ""
echo "Test your website:"
echo "  Local: curl http://localhost"
echo "  Domain: https://bils.dasnet.my.id"
echo ""
```

**How to use:**

```bash
# SSH to server
ssh root@172.13.13.5 -p 22

# Create script
nano fix-bils-403.sh

# Paste the script above
# Save: Ctrl+X, Y, Enter

# Make executable
chmod +x fix-bils-403.sh

# Run
./fix-bils-403.sh
```

---

## 🔍 Diagnostic Commands

If still getting 403, run these diagnostics:

```bash
# Connect SSH
ssh root@172.13.13.5 -p 22

# 1. Check if Laravel files exist
ls -la /www/wwwroot/bils.dasnet.my.id/

# Should see: app, bootstrap, config, public, storage, vendor, artisan

# 2. Check public folder
ls -la /www/wwwroot/bils.dasnet.my.id/public/

# Should see: index.php with proper permissions

# 3. Check permissions
stat /www/wwwroot/bils.dasnet.my.id/public/index.php

# Should show: Access: (0644/-rw-r--r--) Uid: (www) Gid: (www)

# 4. Check Nginx error log
tail -n 50 /www/server/nginx/logs/error.log

# 5. Check Nginx config
cat /www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

# 6. Test PHP-FPM
systemctl status php-fpm-81

# 7. Test local website
curl -I http://localhost

# 8. Test domain
curl -I https://bils.dasnet.my.id

# 9. Check Cloudflare Tunnel
systemctl status cloudflared
journalctl -u cloudflared -n 50
```

---

## 🔐 Security: Change Password After Fix

### Change aaPanel Password

**Via Web Interface:**
```
1. Login: http://172.13.13.5:8811
2. Click user icon (top right)
3. Click "Panel settings"
4. Change password
5. Save
```

**Via SSH:**
```bash
ssh root@172.13.13.5 -p 22

# Run aaPanel command
bt 6

# Select option to change password
# Enter new strong password
```

### Change SSH Root Password

```bash
ssh root@172.13.13.5 -p 22

# Change password
passwd

# Enter new password twice
```

### Recommended Strong Password Format:
```
Minimum 12 characters
Mix of: uppercase, lowercase, numbers, symbols
Example: Billing@Dasnet2026!
```

---

## 📁 Important File Locations

```
Website Files:
/www/wwwroot/bils.dasnet.my.id/
/www/wwwroot/bils.dasnet.my.id/public/
/www/wwwroot/bils.dasnet.my.id/.env

Nginx Config:
/www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

Nginx Logs:
/www/server/nginx/logs/error.log
/www/server/nginx/logs/access.log
/www/wwwroot/bils.dasnet.my.id/log/nginx_error.log

PHP-FPM:
/tmp/php-cgi-81.sock
/www/server/php/81/etc/php-fpm.conf

Cloudflare Tunnel:
/root/.cloudflared/config.yml
/root/.cloudflared/[TUNNEL_ID].json

Laravel Logs:
/www/wwwroot/bils.dasnet.my.id/storage/logs/laravel.log
```

---

## ✅ Complete Checklist

### Initial Access:
- [ ] SSH: ssh root@172.13.13.5 -p 22
- [ ] aaPanel: http://172.13.13.5:8811
- [ ] Both use: root / madiun12

### Fix Error 403:
- [ ] Method 1: aaPanel → Fix permissions
- [ ] OR Method 2: SSH → Run fix commands
- [ ] Set document root: /public
- [ ] Restart Nginx
- [ ] Clear Laravel cache

### Verification:
- [ ] curl http://localhost → 200 OK
- [ ] curl https://bils.dasnet.my.id → 200 OK
- [ ] Browser: https://bils.dasnet.my.id → Works!

### Security:
- [ ] Change aaPanel password
- [ ] Change SSH root password
- [ ] Use strong passwords (12+ chars)

### Final:
- [ ] Website accessible
- [ ] Laravel app running
- [ ] All features working
- [ ] Passwords changed ✅

---

## 🎯 Expected Results

After fix, you should see:

```bash
# Test local
$ curl -I http://localhost
HTTP/1.1 200 OK
Server: nginx
Content-Type: text/html; charset=UTF-8

# Test domain
$ curl -I https://bils.dasnet.my.id
HTTP/1.1 200 OK
Server: cloudflare
Content-Type: text/html; charset=UTF-8
```

Website accessible at: **https://bils.dasnet.my.id** ✅

---

## 🐛 Common Issues & Solutions

### Issue 1: SSH Connection Refused
```bash
# Check if SSH is running
systemctl status sshd

# Check firewall
iptables -L -n | grep 22

# Try with explicit port
ssh root@172.13.13.5 -p 22 -v
```

### Issue 2: Permission Denied (SSH)
```bash
# Make sure using correct credentials
# Username: root
# Password: madiun12
# Port: 22
```

### Issue 3: Still 403 After Fix
```bash
# Check if index.php exists
ls -la /www/wwwroot/bils.dasnet.my.id/public/index.php

# Check Nginx config
cat /www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

# Check error log
tail -f /www/server/nginx/logs/error.log
```

### Issue 4: 502 Bad Gateway
```bash
# Restart PHP-FPM
systemctl restart php-fpm-81

# Check PHP-FPM status
systemctl status php-fpm-81

# Check socket
ls -la /tmp/php-cgi-81.sock
```

---

## 📞 Quick Reference Commands

```bash
# SSH Login
ssh root@172.13.13.5 -p 22

# Quick Fix (one-liner)
cd /www/wwwroot/bils.dasnet.my.id && chown -R www:www . && chmod -R 755 . && chmod -R 777 storage bootstrap/cache && systemctl restart nginx

# Test
curl https://bils.dasnet.my.id

# View Logs
tail -f /www/server/nginx/logs/error.log

# Restart Services
systemctl restart nginx
systemctl restart php-fpm-81
systemctl restart cloudflared

# Check Status
systemctl status nginx
systemctl status php-fpm-81
systemctl status cloudflared
```

---

## 🆘 Need Help?

If still having issues, send me:

```bash
# 1. Permission check
ls -la /www/wwwroot/bils.dasnet.my.id/public/

# 2. Error log (last 20 lines)
tail -n 20 /www/server/nginx/logs/error.log

# 3. Nginx config
cat /www/server/panel/vhost/nginx/bils.dasnet.my.id.conf

# 4. Test results
curl -I http://localhost
curl -I https://bils.dasnet.my.id
```

---

## 🎉 Summary

**Access Info:**
- SSH: `ssh root@172.13.13.5 -p 22`
- aaPanel: `http://172.13.13.5:8811`
- Credentials: root / madiun12

**Quick Fix:**
1. Login aaPanel or SSH
2. Fix permissions
3. Set document root: /public
4. Restart Nginx
5. Test: https://bils.dasnet.my.id

**Time:** 3-5 minutes
**Difficulty:** ⭐⭐☆☆☆ Easy

---

Good luck! 🚀
