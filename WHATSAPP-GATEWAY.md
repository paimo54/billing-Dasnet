# WhatsApp Gateway Integration - Complete Documentation

## 📋 Overview

WhatsApp Gateway Integration untuk sistem billing ISP dengan fitur lengkap untuk mengirim notifikasi otomatis ke pelanggan menggunakan template yang dapat dikustomisasi.

---

## 🎯 Features

### Core Features
- ✅ **Template Management** - CRUD template dengan variable replacement
- ✅ **Multi-Provider Support** - Fonnte, Wablas, Woowa, Twilio
- ✅ **Message Tracking** - Track status (pending, sent, delivered, read)
- ✅ **Bulk Sending** - Kirim ke multiple customers sekaligus
- ✅ **Daily Quota Management** - Auto-reset daily counter
- ✅ **Failed Message Retry** - Automatic retry untuk failed messages
- ✅ **Customer History** - Riwayat pesan per customer
- ✅ **Statistics Dashboard** - Real-time statistics
- ✅ **Provider Testing** - Test connection dengan real message
- ✅ **Auto-Send Events** - Trigger otomatis untuk events tertentu

---

## 📦 Installation

### 1. Run Migrations

```bash
php artisan migrate
```

Tables created:
- `whatsapp_templates` - Template pesan
- `whatsapp_messages` - History pesan terkirim
- `whatsapp_providers` - Konfigurasi provider

### 2. Run Seeder

```bash
php artisan db:seed --class=WhatsappSeeder
```

Seeder akan membuat:
- 1 default provider (Fonnte)
- 8 default templates

### 3. Configure Environment

Edit `.env`:

```env
# WhatsApp Gateway
WHATSAPP_ENABLED=true
WHATSAPP_DEFAULT_PROVIDER=fonnte

# Fonnte Configuration
FONNTE_API_KEY=your-api-key-here
FONNTE_API_URL=https://api.fonnte.com/send
FONNTE_SENDER_NUMBER=
FONNTE_DAILY_LIMIT=1000

# Wablas Configuration (Optional)
WABLAS_API_KEY=
WABLAS_API_URL=https://console.wablas.com/api/send-message
WABLAS_SENDER_NUMBER=
WABLAS_DAILY_LIMIT=1000

# Woowa Configuration (Optional)
WOOWA_API_KEY=
WOOWA_API_URL=https://api.woowa.id/v1/send
WOOWA_SENDER_NUMBER=
WOOWA_DAILY_LIMIT=1000

# Auto Send Settings
WHATSAPP_AUTO_INVOICE_CREATED=true
WHATSAPP_AUTO_INVOICE_REMINDER=true
WHATSAPP_AUTO_PAYMENT_SUCCESS=true
WHATSAPP_AUTO_CUSTOMER_REGISTERED=true
WHATSAPP_AUTO_CUSTOMER_APPROVED=true

# Queue Settings
WHATSAPP_USE_QUEUE=true
WHATSAPP_QUEUE_NAME=whatsapp

# Rate Limiting
WHATSAPP_RATE_LIMIT=60

# Retry Settings
WHATSAPP_RETRY_FAILED=true
WHATSAPP_MAX_RETRIES=3
WHATSAPP_RETRY_DELAY=300
```

### 4. Get API Key

**Fonnte:**
1. Register di https://fonnte.com
2. Beli paket sesuai kebutuhan
3. Copy API Key dari dashboard
4. Paste ke `.env`

**Wablas:**
1. Register di https://wablas.com
2. Beli paket sesuai kebutuhan
3. Copy API Key dari dashboard
4. Paste ke `.env`

**Woowa:**
1. Register di https://woowa.id
2. Beli paket sesuai kebutuhan
3. Copy API Key dari dashboard
4. Paste ke `.env`

---

## 🎨 Usage

### 1. Via Code (Service)

```php
use App\Services\WhatsappService;
use App\Models\Customer;

$whatsappService = new WhatsappService();

// Send using template
$customer = Customer::find(1);
$result = $whatsappService->sendFromTemplate('invoice-reminder', $customer, [
    'invoiceNumber' => 'INV-001',
    'amount' => 'Rp 150.000',
    'dueDate' => '31 Jan 2026',
    'daysRemaining' => '3',
    'paymentLink' => 'https://yourdomain.com/pay/xxx',
]);

// Send direct message
$result = $whatsappService->send(
    '08123456789',
    'Halo, ini pesan test',
    $customerId
);

// Get customer history
$history = $whatsappService->getCustomerHistory($customerId);

// Get statistics
$stats = $whatsappService->getStatistics(30); // Last 30 days
```

### 2. Via API

**Send using template:**
```bash
POST /api/whatsapp/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_id": 1,
  "template_type": "invoice-reminder",
  "data": {
    "invoiceNumber": "INV-001",
    "amount": "Rp 150.000",
    "dueDate": "31 Jan 2026",
    "daysRemaining": "3",
    "paymentLink": "https://yourdomain.com/pay/xxx"
  }
}
```

**Send direct message:**
```bash
POST /api/whatsapp/send-direct
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone": "08123456789",
  "message": "Halo, ini pesan test",
  "customer_id": 1
}
```

**Bulk send:**
```bash
POST /api/whatsapp/bulk-send
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_ids": [1, 2, 3, 4, 5],
  "template_type": "general-broadcast",
  "data": {
    "message": "Promo spesial bulan ini!"
  }
}
```

**Get statistics:**
```bash
GET /api/whatsapp/statistics?days=30
Authorization: Bearer {token}
```

### 3. Via Admin Panel

**Access:**
- URL: `https://yourdomain.com/admin/whatsapp/templates`
- Login sebagai Admin atau SuperAdmin

**Menu:**
1. **Templates** - Manage message templates
   - Create new template
   - Edit existing template
   - Toggle active/inactive
   - Preview with sample data

2. **Messages** - View message history
   - Filter by status, customer, template, date
   - View message details
   - Resend failed messages
   - Delete messages

3. **Providers** - Manage WhatsApp providers
   - Add new provider
   - Edit provider configuration
   - Set default provider
   - Test connection
   - Reset daily counter
   - View quota usage

---

## 📝 Template Variables

### Customer Variables
```
{{customerId}} - Customer ID
{{customerName}} - Nama customer
{{phone}} - Nomor telepon
{{email}} - Email
{{address}} - Alamat
{{username}} - Username PPPoE
{{password}} - Password PPPoE
{{region}} - Region/Area
{{area}} - Area (alias untuk region)
```

### Package Variables
```
{{packageName}} - Nama paket
{{profileName}} - Nama profile (alias untuk packageName)
```

### Invoice Variables
```
{{invoiceNumber}} - Nomor invoice
{{amount}} - Jumlah tagihan
{{dueDate}} - Tanggal jatuh tempo
{{daysRemaining}} - Sisa hari sebelum jatuh tempo
{{daysOverdue}} - Jumlah hari terlambat
{{paymentLink}} - Link pembayaran
{{paymentToken}} - Token pembayaran
```

### Payment Variables
```
{{paymentDate}} - Tanggal pembayaran
{{paymentMethod}} - Metode pembayaran
{{receiptNumber}} - Nomor bukti pembayaran
```

### Company Variables
```
{{companyName}} - Nama perusahaan
{{companyPhone}} - Telepon perusahaan
{{companyEmail}} - Email perusahaan
{{companyAddress}} - Alamat perusahaan
{{baseUrl}} - Base URL aplikasi
```

### Maintenance Variables
```
{{maintenanceDate}} - Tanggal maintenance
{{maintenanceTime}} - Waktu maintenance
{{duration}} - Durasi maintenance
{{affectedArea}} - Area yang terdampak
{{description}} - Deskripsi maintenance
```

### Custom Variables
```
{{message}} - Custom message (untuk broadcast)
```

---

## 🔧 Default Templates

### 1. invoice-created
Dikirim saat invoice baru dibuat.

### 2. invoice-reminder
Dikirim sebagai reminder sebelum jatuh tempo (H-7, H-3, H-1, H-0).

### 3. payment-success
Dikirim saat pembayaran berhasil.

### 4. customer-registered
Dikirim saat customer submit form registrasi.

### 5. customer-approved
Dikirim saat admin approve registrasi customer.

### 6. maintenance-notification
Template untuk broadcast maintenance.

### 7. invoice-overdue
Dikirim saat invoice sudah lewat jatuh tempo.

### 8. general-broadcast
Template untuk broadcast umum.

---

## 🔄 Auto-Send Integration

### Invoice Created
```php
// In InvoiceController@store or wherever invoice is created
use App\Services\WhatsappService;

if (config('whatsapp.auto_send.invoice_created')) {
    $whatsappService = new WhatsappService();
    $whatsappService->sendFromTemplate('invoice-created', $invoice->customer, [
        'invoiceNumber' => $invoice->invoice_number,
        'amount' => 'Rp ' . number_format($invoice->amount, 0, ',', '.'),
        'dueDate' => $invoice->due_date->format('d M Y'),
        'paymentLink' => route('payment.show', $invoice->payment_token),
    ]);
}
```

### Payment Success
```php
// In PaymentController after successful payment
if (config('whatsapp.auto_send.payment_success')) {
    $whatsappService = new WhatsappService();
    $whatsappService->sendFromTemplate('payment-success', $invoice->customer, [
        'invoiceNumber' => $invoice->invoice_number,
        'amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
        'paymentDate' => now()->format('d M Y'),
    ]);
}
```

### Customer Approved
```php
// In AdminController@approveCustomer
if (config('whatsapp.auto_send.customer_approved')) {
    $whatsappService = new WhatsappService();
    $whatsappService->sendFromTemplate('customer-approved', $customer, [
        'username' => $customer->pppoe_username,
        'password' => $customer->pppoe_password,
        'expiredAt' => $customer->expired_at->format('d M Y'),
        'invoiceNumber' => $invoice->invoice_number,
        'amount' => 'Rp ' . number_format($invoice->amount, 0, ',', '.'),
        'paymentLink' => route('payment.show', $invoice->payment_token),
    ]);
}
```

---

## 📊 Statistics

```php
$stats = $whatsappService->getStatistics(30);

// Returns:
[
    'total_sent' => 1500,
    'total_delivered' => 1450,
    'total_failed' => 50,
    'total_pending' => 10,
    'provider_quota' => 500, // Remaining today
    'provider_name' => 'Fonnte Primary',
]
```

---

## 🔍 Message Status Flow

```
pending → sent → delivered → read
         ↓
       failed (can be retried)
```

**Status:**
- `pending` - Belum dikirim
- `sent` - Sudah dikirim ke provider
- `failed` - Gagal dikirim
- `delivered` - Sudah terkirim ke customer
- `read` - Sudah dibaca customer

---

## ⚙️ Configuration Options

### Auto-Send Events
```php
'auto_send' => [
    'invoice_created' => true,
    'invoice_reminder' => true,
    'payment_success' => true,
    'payment_failed' => false,
    'customer_registered' => true,
    'customer_approved' => true,
],
```

### Reminder Days
```php
'reminder_days' => [
    7,  // H-7
    3,  // H-3
    1,  // H-1
    0,  // H-0 (due date)
],
```

### Queue Settings
```php
'use_queue' => true,
'queue_name' => 'whatsapp',
```

### Rate Limiting
```php
'rate_limit' => 60, // messages per minute
```

### Retry Settings
```php
'retry_failed' => true,
'max_retries' => 3,
'retry_delay' => 300, // seconds
```

---

## 🚀 Queue Worker

Untuk auto-send dan bulk sending, gunakan queue worker:

```bash
# Start queue worker
php artisan queue:work --queue=whatsapp

# With supervisor (production)
[program:whatsapp-worker]
command=php /path/to/artisan queue:work --queue=whatsapp --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

---

## 📈 Performance Tips

1. **Use Queue** - Enable queue untuk bulk sending
2. **Rate Limiting** - Sesuaikan rate limit dengan provider
3. **Daily Quota** - Monitor daily quota usage
4. **Failed Retry** - Enable auto-retry untuk failed messages
5. **Multiple Providers** - Setup multiple providers untuk failover

---

## 🔒 Security

1. **API Key** - Simpan di `.env`, jangan commit ke git
2. **Authentication** - Semua API endpoint require authentication
3. **Rate Limiting** - Prevent abuse dengan rate limiting
4. **Validation** - Validate phone number format
5. **Logging** - Log semua aktivitas untuk audit

---

## 🐛 Troubleshooting

### Message Not Sent

**Check:**
1. Provider is active and default
2. API key is correct
3. Daily quota not exceeded
4. Phone number format is correct (62xxx)
5. Template is active
6. Check logs: `storage/logs/laravel.log`

### Failed Messages

**Retry:**
```php
$message = WhatsappMessage::find($id);
$whatsappService->send(
    $message->phone,
    $message->message,
    $message->customer_id,
    $message->template_id,
    $message->template_type
);
```

### Quota Exceeded

**Reset manually:**
```php
$provider = WhatsappProvider::find($id);
$provider->update([
    'daily_sent' => 0,
    'last_reset_date' => now()->toDateString(),
]);
```

---

## 📞 Support

**Provider Support:**
- Fonnte: https://fonnte.com/support
- Wablas: https://wablas.com/support
- Woowa: https://woowa.id/support

**Documentation:**
- Fonnte API: https://fonnte.com/api
- Wablas API: https://wablas.com/docs
- Woowa API: https://woowa.id/docs

---

## 🎯 Roadmap

- [ ] WhatsApp Business API integration
- [ ] Media message support (image, document)
- [ ] Template approval workflow
- [ ] A/B testing for templates
- [ ] Advanced analytics dashboard
- [ ] Webhook for delivery status
- [ ] Customer reply handling
- [ ] Chatbot integration

---

**Version**: 2.4.0  
**Last Updated**: 2026-05-27  
**Status**: Production Ready
