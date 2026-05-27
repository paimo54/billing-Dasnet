# Queue System Documentation

## Overview

Sistem Queue telah diimplementasikan untuk menangani proses-proses berat secara asynchronous, meningkatkan performa aplikasi dan user experience.

## Jobs yang Tersedia

### 1. ProcessMonthlyInvoices
**File**: `app/Jobs/ProcessMonthlyInvoices.php`

**Fungsi**: Generate invoice bulanan untuk semua pelanggan aktif secara batch.

**Features**:
- Process dalam chunks (100 customers per batch) untuk menghindari memory issues
- Automatic invoice number generation
- Tax calculation (PPN 11%)
- Technician fee calculation
- Duplicate invoice prevention
- Automatic notification dispatch
- Error handling dan logging
- Timeout: 1 hour
- Retry: 3 attempts

**Usage**:
```php
// Dispatch job untuk generate invoice bulan ini
ProcessMonthlyInvoices::dispatch();

// Dispatch untuk tanggal billing tertentu
ProcessMonthlyInvoices::dispatch($billingDate, $month, $year);
```

**Command Line**:
```bash
php artisan queue:work
```

---

### 2. SendPaymentReminder
**File**: `app/Jobs/SendPaymentReminder.php`

**Fungsi**: Mengirim reminder pembayaran ke pelanggan via Email/SMS/WhatsApp.

**Reminder Types**:
- `before_due`: Sebelum jatuh tempo (H-7, H-3, H-1)
- `due_date`: Hari jatuh tempo
- `overdue`: Sudah lewat jatuh tempo

**Features**:
- Multi-channel notification (Email, SMS, WhatsApp)
- Skip jika invoice sudah dibayar
- Dynamic message based on reminder type
- Days calculation (until due / overdue)
- Retry: 3 attempts
- Timeout: 120 seconds

**Usage**:
```php
// Send reminder untuk invoice tertentu
SendPaymentReminder::dispatch($invoiceId, 'before_due');
SendPaymentReminder::dispatch($invoiceId, 'due_date');
SendPaymentReminder::dispatch($invoiceId, 'overdue');
```

---

### 3. SendInvoiceNotification
**File**: `app/Jobs/SendInvoiceNotification.php`

**Fungsi**: Mengirim notifikasi invoice baru ke pelanggan.

**Features**:
- Email notification dengan detail invoice
- SMS notification dengan ringkasan
- Payment methods information
- Retry: 3 attempts
- Timeout: 120 seconds

**Usage**:
```php
// Send notification setelah invoice dibuat
SendInvoiceNotification::dispatch($customerId, $invoiceNumber);
```

---

## Console Commands

### 1. billing:send-payment-reminders
**File**: `app/Console/Commands/SendPaymentReminders.php`

**Fungsi**: Mengirim payment reminders secara batch.

**Options**:
- `--type=all|before_due|due_date|overdue`: Tipe reminder
- `--days=7`: Berapa hari sebelum jatuh tempo

**Usage**:
```bash
# Send semua tipe reminder
php artisan billing:send-payment-reminders

# Send hanya before_due reminder (H-7)
php artisan billing:send-payment-reminders --type=before_due --days=7

# Send hanya overdue reminder
php artisan billing:send-payment-reminders --type=overdue
```

**Cron Schedule** (tambahkan di `app/Console/Kernel.php`):
```php
// H-7 sebelum jatuh tempo (jam 9 pagi)
$schedule->command('billing:send-payment-reminders --type=before_due --days=7')
         ->dailyAt('09:00');

// H-3 sebelum jatuh tempo
$schedule->command('billing:send-payment-reminders --type=before_due --days=3')
         ->dailyAt('09:00');

// Hari jatuh tempo
$schedule->command('billing:send-payment-reminders --type=due_date')
         ->dailyAt('09:00');

// Overdue (setiap hari)
$schedule->command('billing:send-payment-reminders --type=overdue')
         ->dailyAt('10:00');
```

---

### 2. billing:auto-suspend
**File**: `app/Console/Commands/AutoSuspendOverdueCustomers.php`

**Fungsi**: Suspend pelanggan yang telat bayar secara otomatis.

**Options**:
- `--grace-days=7`: Grace period setelah jatuh tempo
- `--dry-run`: Test mode tanpa suspend

**Features**:
- Grace period configuration
- Dry-run mode untuk testing
- Detailed summary report
- Error handling
- Logging semua aktivitas
- TODO: Mikrotik integration untuk disable service

**Usage**:
```bash
# Suspend dengan grace period 7 hari
php artisan billing:auto-suspend --grace-days=7

# Test mode (tidak suspend)
php artisan billing:auto-suspend --dry-run

# Suspend dengan grace period 14 hari
php artisan billing:auto-suspend --grace-days=14
```

**Cron Schedule**:
```php
// Jalankan setiap hari jam 11 malam
$schedule->command('billing:auto-suspend --grace-days=7')
         ->dailyAt('23:00');
```

---

## Setup Queue System

### 1. Konfigurasi Queue Driver

Edit `.env`:
```env
QUEUE_CONNECTION=database
```

Atau gunakan Redis untuk performa lebih baik:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. Migrate Queue Tables

```bash
php artisan queue:table
php artisan migrate
```

### 3. Jalankan Queue Worker

**Development**:
```bash
php artisan queue:work
```

**Production** (dengan Supervisor):

Buat file `/etc/supervisor/conf.d/billing-worker.conf`:
```ini
[program:billing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/billing-Dasnet/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/billing-Dasnet/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start billing-worker:*
```

### 4. Setup Cron Jobs

Edit crontab:
```bash
crontab -e
```

Tambahkan:
```cron
# Laravel Scheduler
* * * * * cd /path/to/billing-Dasnet && php artisan schedule:run >> /dev/null 2>&1
```

---

## Monitoring Queue

### Check Queue Status
```bash
# Lihat failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job-id}

# Retry semua failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Queue Monitoring Dashboard
Install Laravel Horizon (optional):
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

---

## Best Practices

1. **Always use Queue untuk proses berat**:
   - Generate invoice massal
   - Send email/SMS blast
   - Export data besar
   - API calls ke external services

2. **Set proper timeout dan retry**:
   - Timeout sesuai dengan estimasi waktu proses
   - Retry 3x untuk handle temporary failures

3. **Logging**:
   - Log semua aktivitas penting
   - Log errors untuk debugging

4. **Error Handling**:
   - Implement `failed()` method di setiap job
   - Handle exceptions dengan graceful

5. **Testing**:
   - Test dengan dry-run mode dulu
   - Monitor logs saat production

---

## TODO: Future Enhancements

1. **Email Templates**:
   - Buat Mailable classes untuk invoice dan reminders
   - Design HTML email templates

2. **SMS Gateway Integration**:
   - Integrate dengan Twilio, Nexmo, atau local SMS gateway
   - Implement SMS sending logic

3. **WhatsApp Integration**:
   - Integrate dengan WhatsApp Business API
   - Implement WhatsApp notification

4. **Mikrotik Integration**:
   - Implement Mikrotik API untuk auto-suspend
   - Auto-unsuspend setelah pembayaran

5. **Queue Monitoring**:
   - Setup Laravel Horizon
   - Real-time queue monitoring dashboard

6. **Rate Limiting**:
   - Implement rate limiting untuk SMS/Email
   - Prevent spam dan abuse

---

## Troubleshooting

### Queue tidak berjalan
```bash
# Check queue worker status
ps aux | grep "queue:work"

# Restart queue worker
php artisan queue:restart
```

### Failed jobs terus bertambah
```bash
# Check error logs
tail -f storage/logs/laravel.log

# Check failed jobs detail
php artisan queue:failed
```

### Memory issues
```bash
# Increase memory limit di queue worker
php -d memory_limit=512M artisan queue:work
```

---

**Version**: 2.0.0  
**Last Updated**: 2026-05-27
