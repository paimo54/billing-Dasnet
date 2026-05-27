# 🤖 Auto Invoice Generation System

## 📋 Overview

Sistem auto invoice sudah **AKTIF** dan berjalan otomatis setiap hari untuk generate invoice bulanan pelanggan.

---

## ✅ Status Sistem

```
✅ Command: invoices:generate-monthly (SUDAH ADA)
✅ Schedule: Daily at 00:10 (SUDAH DIKONFIGURASI)
✅ Cron Job: SUDAH DISETUP di server
✅ Website: https://bils.dasnet.my.id (ONLINE)
```

---

## 🔧 Cara Kerja Auto Invoice

### 1. **Trigger Otomatis**
```
Setiap hari jam 00:10 WIB, sistem akan:
1. Cek semua customer yang aktif (is_active = true)
2. Filter customer yang billing_date-nya = hari ini
3. Generate invoice untuk customer tersebut
4. Skip jika sudah ada invoice bulan ini
```

### 2. **Logika Generate Invoice**

```php
// File: app/Console/Commands/GenerateMonthlyInvoices.php

- Ambil customer aktif dengan billing_date = hari ini
- Cek apakah sudah ada invoice bulan ini (skip jika ada)
- Ambil data package customer
- Hitung:
  * Base Price (dari package)
  * Tax 11% (PPN)
  * Technician Fee (dari package percentage)
  * Total Amount
- Create invoice dengan status "unpaid"
- Due date = 30 hari dari invoice date
```

### 3. **Invoice Number Format**
```
INV-00001
INV-00002
INV-00003
...
(Auto increment, 5 digit dengan leading zero)
```

---

## 📅 Schedule Configuration

**File:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('invoices:generate-monthly')->dailyAt('00:10');
}
```

**Cron Job di Server:**
```bash
* * * * * cd /www/wwwroot/bils.dasnet.my.id && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Testing Auto Invoice

### Test Manual (Tanpa Tunggu Cron)

```bash
# SSH ke server
ssh root@172.13.13.5 -p 22

# Masuk ke folder website
cd /www/wwwroot/bils.dasnet.my.id

# Run command manual
php artisan invoices:generate-monthly

# Output:
# X invoice(s) generated for date 2026-05-25.
```

### Test dengan Tanggal Spesifik

Jika ingin test untuk customer dengan billing_date tertentu, ubah tanggal server sementara atau edit command untuk test.

---

## 📊 Database Structure

### Table: invoices

```sql
- id (primary key)
- invoice_number (INV-00001)
- customer_id (foreign key)
- package_id (foreign key)
- invoice_date (tanggal invoice dibuat)
- due_date (tanggal jatuh tempo, +30 hari)
- amount (harga base dari package)
- tax_percentage (11%)
- tax_amount (amount * 11%)
- technician_fee_percentage (dari package)
- technician_fee_amount (amount * technician_fee_percentage)
- total_amount (amount + tax_amount)
- status (unpaid/paid/cancelled)
- notes (catatan)
- created_by (user yang create)
- is_printed (sudah dicetak?)
- printed_at (waktu cetak)
- printed_by (user yang cetak)
- timestamps
```

---

## 🎯 Contoh Perhitungan Invoice

```
Customer: John Doe
Package: Premium (Rp 500,000)
Billing Date: 25 (setiap tanggal 25)
Technician Fee: 10%

Perhitungan:
- Base Price: Rp 500,000
- Tax (11%): Rp 55,000
- Total Amount: Rp 555,000
- Technician Fee (10%): Rp 50,000

Invoice:
- Invoice Number: INV-00123
- Invoice Date: 2026-05-25
- Due Date: 2026-06-24
- Amount: Rp 500,000
- Tax: Rp 55,000
- Total: Rp 555,000
- Status: unpaid
```

---

## 🔍 Monitoring & Logs

### Check Cron Job Status

```bash
# SSH ke server
ssh root@172.13.13.5 -p 22

# Check crontab
crontab -l

# Harus ada:
# * * * * * cd /www/wwwroot/bils.dasnet.my.id && php artisan schedule:run >> /dev/null 2>&1
```

### Check Laravel Scheduler

```bash
cd /www/wwwroot/bils.dasnet.my.id

# List scheduled commands
php artisan schedule:list

# Output:
# 0 10 * * * invoices:generate-monthly .... Next Due: ...
```

### Check Generated Invoices

```bash
# Via artisan tinker
php artisan tinker

# Check invoice hari ini
Invoice::whereDate('invoice_date', today())->get();

# Check invoice bulan ini
Invoice::whereMonth('invoice_date', now()->month)->count();
```

---

## 🛠️ Troubleshooting

### Issue 1: Invoice Tidak Generate Otomatis

**Cek:**
```bash
# 1. Cek cron job
crontab -l | grep artisan

# 2. Cek scheduler
cd /www/wwwroot/bils.dasnet.my.id
php artisan schedule:list

# 3. Test manual
php artisan invoices:generate-monthly

# 4. Check logs
tail -f storage/logs/laravel.log
```

**Solusi:**
```bash
# Re-add cron job
(crontab -l 2>/dev/null; echo '* * * * * cd /www/wwwroot/bils.dasnet.my.id && php artisan schedule:run >> /dev/null 2>&1') | crontab -
```

### Issue 2: Invoice Duplicate

**Penyebab:**
- Command dijalankan manual berkali-kali
- Cron job duplicate

**Solusi:**
Sistem sudah ada proteksi:
```php
// Skip kalau sudah ada invoice bulan ini
$alreadyHasInvoice = Invoice::where('customer_id', $customer->id)
    ->whereMonth('invoice_date', $today->month)
    ->whereYear('invoice_date', $today->year)
    ->exists();

if ($alreadyHasInvoice) {
    continue;
}
```

### Issue 3: Customer Tidak Dapat Invoice

**Cek:**
```bash
php artisan tinker

# Check customer
$customer = Customer::find(1);
$customer->is_active; // harus true
$customer->billing_date; // tanggal berapa?
$customer->package; // ada package?
```

**Syarat Customer Dapat Invoice:**
- `is_active = true`
- `billing_date` = tanggal hari ini
- Punya `package_id` yang valid
- Belum ada invoice bulan ini

---

## 📝 Customization

### Ubah Waktu Generate

Edit `app/Console/Kernel.php`:

```php
// Dari jam 00:10
$schedule->command('invoices:generate-monthly')->dailyAt('00:10');

// Ke jam lain, misal 01:00
$schedule->command('invoices:generate-monthly')->dailyAt('01:00');

// Atau setiap jam
$schedule->command('invoices:generate-monthly')->hourly();

// Atau setiap 6 jam
$schedule->command('invoices:generate-monthly')->everySixHours();
```

### Ubah Tax Percentage

Edit `app/Console/Commands/GenerateMonthlyInvoices.php`:

```php
// Dari 11%
$taxPercentage = 11;

// Ke percentage lain
$taxPercentage = 12; // atau ambil dari settings
```

### Ubah Due Date

Edit `app/Console/Commands/GenerateMonthlyInvoices.php`:

```php
// Dari 30 hari
'due_date' => $today->copy()->addDays(30),

// Ke 7 hari
'due_date' => $today->copy()->addDays(7),

// Atau 1 bulan
'due_date' => $today->copy()->addMonth(),
```

---

## 🚀 Advanced Features

### 1. Send Email Notification

Tambahkan di `GenerateMonthlyInvoices.php`:

```php
use Illuminate\Support\Facades\Mail;

// Setelah create invoice
Mail::to($customer->email)->send(new InvoiceCreated($invoice));
```

### 2. WhatsApp Notification

Integrate dengan WhatsApp API:

```php
// Setelah create invoice
$this->sendWhatsAppNotification($customer, $invoice);
```

### 3. Generate PDF Otomatis

```php
use Barryvdh\DomPDF\Facade\Pdf;

// Generate PDF
$pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
$pdf->save(storage_path("app/invoices/{$invoice->invoice_number}.pdf"));
```

---

## 📞 Quick Commands

```bash
# Test generate invoice
php artisan invoices:generate-monthly

# Check schedule
php artisan schedule:list

# Run scheduler manually
php artisan schedule:run

# Check cron
crontab -l

# View logs
tail -f storage/logs/laravel.log

# Tinker (interactive)
php artisan tinker
```

---

## ✅ Checklist Setup

- [x] Command `invoices:generate-monthly` created
- [x] Schedule configured in `Kernel.php`
- [x] Cron job added to server
- [x] Invoice model configured
- [x] Database migrated
- [x] Website online and accessible
- [x] Permissions fixed
- [x] Auto invoice READY TO USE

---

## 🎉 Summary

**Auto Invoice System Status: ✅ AKTIF**

- **Berjalan otomatis** setiap hari jam 00:10 WIB
- **Generate invoice** untuk customer yang billing_date-nya hari ini
- **Tidak ada duplicate** (ada proteksi)
- **Siap digunakan** tanpa perlu action manual

**Test Manual:**
```bash
ssh root@172.13.13.5 -p 22
cd /www/wwwroot/bils.dasnet.my.id
php artisan invoices:generate-monthly
```

**Monitor:**
- Check di dashboard admin
- Check database table `invoices`
- Check logs: `storage/logs/laravel.log`

---

**Setup Date:** 2026-05-25
**Status:** ✅ PRODUCTION READY
**Next Action:** Monitor hasil generate invoice besok jam 00:10

Good luck! 🚀
