# Network Integration Documentation - FreeRADIUS + Mikrotik Hybrid

## Overview

Sistem network management telah diimplementasikan dengan **Hybrid Architecture**: FreeRADIUS sebagai primary method dan Mikrotik API sebagai fallback/backup method.

---

## Architecture

```
┌─────────────────────────────────────────────────────┐
│           Laravel Billing System                     │
│  - Manage customers, invoices, payments             │
│  - Auto-suspend/unsuspend logic                     │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│            NetworkService (Hybrid)                   │
│  - Primary: FreeRADIUS (instant, scalable)          │
│  - Fallback: Mikrotik API (backup)                  │
└────────┬───────────────────────┬────────────────────┘
         │                       │
         ▼                       ▼
┌──────────────────┐    ┌──────────────────┐
│  FreeRADIUS      │    │  Mikrotik API    │
│  - radcheck      │    │  - PPPoE secrets │
│  - radreply      │    │  - Active        │
│  - radacct       │    │    sessions      │
└────────┬─────────┘    └────────┬─────────┘
         │                       │
         │ RADIUS Protocol       │ API
         │                       │
    ┌────┴────────┬──────────────┴───┬──────────┐
    ▼             ▼                  ▼          ▼
┌─────────┐  ┌─────────┐      ┌─────────┐  ...
│Mikrotik1│  │Mikrotik2│      │Mikrotik3│
│ (NAS)   │  │ (NAS)   │      │ (NAS)   │
└─────────┘  └─────────┘      └─────────┘
    │             │                │
    ▼             ▼                ▼
Customers    Customers        Customers
```

---

## Features

### ✅ FreeRADIUS Integration
- **Instant suspend/unsuspend** (1 SQL query)
- **Scalable** untuk 100,000+ pelanggan
- **Centralized** database management
- **Real-time accounting** (bandwidth usage tracking)
- **Session management**
- **Bandwidth control** per user
- **CoA (Change of Authorization)** support

### ✅ Mikrotik API Integration
- **Direct control** via API
- **PPPoE user management**
- **Active session monitoring**
- **Disconnect active sessions**
- **Fallback method** jika RADIUS gagal

### ✅ Hybrid NetworkService
- **Auto-failover** antara RADIUS dan Mikrotik
- **Configurable primary method**
- **Batch operations** untuk ribuan pelanggan
- **Auto-unsuspend** setelah payment
- **Comprehensive logging**

---

## Database Schema

### FreeRADIUS Tables

#### radcheck - Authentication
```sql
id, username, attribute, op, value
```

#### radreply - Authorization
```sql
id, username, attribute, op, value
```

#### radacct - Accounting (Session Tracking)
```sql
radacctid, acctsessionid, username, nasipaddress,
acctstarttime, acctstoptime, acctinputoctets, acctoutputoctets
```

#### nas - Network Access Servers (Mikrotik Routers)
```sql
id, nasname, shortname, type, secret, description
```

---

## Configuration

### Environment Variables

Add to `.env`:

```env
# Network Primary Method
NETWORK_PRIMARY_METHOD=radius  # radius or mikrotik

# FreeRADIUS Configuration
RADIUS_ENABLED=true
RADIUS_DB_CONNECTION=mysql
RADIUS_COA_ENABLED=false
RADIUS_COA_HOST=127.0.0.1
RADIUS_COA_PORT=3799
RADIUS_COA_SECRET=testing123
RADIUS_DEFAULT_DOWNLOAD=10M
RADIUS_DEFAULT_UPLOAD=10M
RADIUS_SESSION_TIMEOUT=0
RADIUS_IDLE_TIMEOUT=0
RADIUS_CONCURRENT_SESSIONS=1

# Mikrotik Configuration
MIKROTIK_ENABLED=true
MIKROTIK_HOST=192.168.1.1
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=your_password
MIKROTIK_PORT=8728
MIKROTIK_TIMEOUT=5
MIKROTIK_CREATE_USER=false
MIKROTIK_DEFAULT_PROFILE=default

# Network Settings
NETWORK_AUTO_UNSUSPEND=true
NETWORK_SUSPEND_GRACE_PERIOD=7
NETWORK_DISCONNECT_ON_SUSPEND=true
NETWORK_USERNAME_FORMAT=customer_{id}
NETWORK_AUTO_GENERATE_PASSWORD=true
NETWORK_PASSWORD_LENGTH=8
```

---

## API Endpoints

### 1. Suspend Customer

**Endpoint**: `POST /api/network/customer/{customer_id}/suspend`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "Customer suspended successfully",
  "method_used": "radius"
}
```

---

### 2. Unsuspend Customer

**Endpoint**: `POST /api/network/customer/{customer_id}/unsuspend`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "Customer unsuspended successfully",
  "method_used": "radius"
}
```

---

### 3. Get Customer Status

**Endpoint**: `GET /api/network/customer/{customer_id}/status`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "status": {
    "customer_id": 123,
    "is_active": true,
    "radius": {
      "username": "customer_123",
      "status": "active",
      "active_sessions": 1,
      "total_download": 1073741824,
      "total_upload": 536870912,
      "total_usage": 1610612736
    },
    "mikrotik_sessions": 1,
    "sessions": [...]
  }
}
```

---

### 4. Batch Suspend

**Endpoint**: `POST /api/network/batch/suspend`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "customer_ids": [123, 456, 789]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Batch suspend completed",
  "result": {
    "total": 3,
    "suspended": 3,
    "failed": 0,
    "errors": []
  }
}
```

---

## Console Commands

### 1. Auto-Suspend Overdue Customers

```bash
# Suspend dengan grace period 7 hari
php artisan billing:auto-suspend --grace-days=7

# Dry-run mode (test tanpa suspend)
php artisan billing:auto-suspend --dry-run

# Specify method
php artisan billing:auto-suspend --method=radius
php artisan billing:auto-suspend --method=mikrotik
```

**Cron Schedule**:
```php
// Run setiap hari jam 11 malam
$schedule->command('billing:auto-suspend --grace-days=7')
         ->dailyAt('23:00');
```

---

## Usage Examples

### Suspend Customer (Code)

```php
use App\Services\NetworkService;
use App\Models\Customer;

$networkService = app(NetworkService::class);
$customer = Customer::find(123);

$result = $networkService->suspendCustomer($customer);

if ($result['success']) {
    echo "Suspended via: " . $result['method_used'];
}
```

### Unsuspend Customer (Code)

```php
$result = $networkService->unsuspendCustomer($customer);

if ($result['success']) {
    echo "Unsuspended via: " . $result['method_used'];
}
```

### Batch Suspend (Code)

```php
$customerIds = [123, 456, 789];

$result = $networkService->batchSuspend($customerIds);

echo "Suspended: " . $result['suspended'];
echo "Failed: " . $result['failed'];
```

### Auto-Unsuspend After Payment

```php
// Automatically called after payment success
$networkService->autoUnsuspendAfterPayment($customer);
```

---

## FreeRADIUS Setup

### 1. Install FreeRADIUS

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install freeradius freeradius-mysql

# CentOS/RHEL
sudo yum install freeradius freeradius-mysql
```

### 2. Configure MySQL Connection

Edit `/etc/freeradius/3.0/mods-available/sql`:

```
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"

    server = "localhost"
    port = 3306
    login = "radius"
    password = "radiuspassword"
    radius_db = "billing_dasnet"
}
```

Enable SQL module:
```bash
sudo ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Add NAS (Mikrotik Routers)

```sql
INSERT INTO nas (nasname, shortname, type, ports, secret, description)
VALUES ('192.168.1.1', 'Mikrotik1', 'mikrotik', 1812, 'testing123', 'Main Router');
```

### 5. Configure Mikrotik

Di Mikrotik, tambahkan RADIUS server:

```
/radius
add address=192.168.100.1 secret=testing123 service=ppp

/ppp aaa
set use-radius=yes
```

### 6. Start FreeRADIUS

```bash
sudo systemctl start freeradius
sudo systemctl enable freeradius
sudo systemctl status freeradius
```

---

## Mikrotik Setup

### 1. Enable API

```
/ip service
set api address=0.0.0.0/0 disabled=no port=8728
```

### 2. Create API User

```
/user
add name=api_user password=api_password group=full
```

### 3. Test Connection

```bash
php artisan tinker
>>> $mikrotik = new \App\Services\MikrotikService();
>>> $mikrotik->connect();
```

---

## Performance Comparison

### Suspend 10,000 Customers

| Method | Time | Notes |
|--------|------|-------|
| **FreeRADIUS** | **< 1 second** | 1 SQL query |
| **Mikrotik API** | **2-5 hours** | Loop 10,000x |

### Recommendation

- **< 1,000 customers**: Mikrotik API OK
- **1,000 - 5,000 customers**: FreeRADIUS RECOMMENDED
- **5,000+ customers**: FreeRADIUS MANDATORY

---

## Monitoring

### Check RADIUS Status

```bash
# Check FreeRADIUS status
sudo systemctl status freeradius

# Test authentication
radtest customer_123 password123 localhost 0 testing123

# View logs
sudo tail -f /var/log/freeradius/radius.log
```

### Check Active Sessions

```sql
SELECT username, nasipaddress, acctstarttime, 
       acctinputoctets, acctoutputoctets
FROM radacct
WHERE acctstoptime IS NULL;
```

### Check Bandwidth Usage

```sql
SELECT username,
       SUM(acctinputoctets) as download,
       SUM(acctoutputoctets) as upload,
       SUM(acctinputoctets + acctoutputoctets) as total
FROM radacct
WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY username;
```

---

## Troubleshooting

### RADIUS Not Working

**Check**:
1. FreeRADIUS service running
2. MySQL connection configured
3. NAS (Mikrotik) added to `nas` table
4. Secret matches between RADIUS and Mikrotik
5. Firewall allows port 1812/1813

**Debug**:
```bash
sudo freeradius -X
```

### Mikrotik API Not Working

**Check**:
1. API service enabled
2. Firewall allows port 8728
3. Credentials correct
4. Network connectivity

**Test**:
```bash
telnet 192.168.1.1 8728
```

### Suspend Not Working

**Check**:
1. Customer exists in radcheck table
2. Auth-Type attribute present
3. NetworkService configured correctly
4. Logs in `storage/logs/laravel.log`

---

## Security

### FreeRADIUS
- Use strong RADIUS secret
- Restrict NAS IP addresses
- Enable SQL connection encryption
- Regular backup of radacct data

### Mikrotik API
- Use strong API password
- Restrict API access by IP
- Use SSL/TLS if available
- Disable API when not needed

---

## Production Checklist

- [ ] FreeRADIUS installed and configured
- [ ] MySQL database migrated
- [ ] NAS (Mikrotik routers) added
- [ ] RADIUS secret configured
- [ ] Mikrotik RADIUS client configured
- [ ] Test authentication working
- [ ] Test suspend/unsuspend
- [ ] Cron job configured
- [ ] Monitoring setup
- [ ] Backup configured

---

**Version**: 2.3.0  
**Last Updated**: 2026-05-27  
**Status**: Production Ready
