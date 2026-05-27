# Payment Gateway Integration Documentation

## Overview

Sistem payment gateway telah diintegrasikan dengan **Duitku** dan **QRIS Dinamis** untuk mendukung berbagai metode pembayaran online.

---

## Supported Payment Methods

### 1. Duitku Payment Gateway

**Payment Methods**:
- **Virtual Account**: BCA, Mandiri, BNI, BRI, Permata, CIMB Niaga, Danamon, dll
- **E-Wallet**: OVO, ShopeePay, LinkAja, DANA
- **Retail**: Indomaret, Alfamart
- **QRIS**: QRIS Nobu, ShopeePay QRIS

### 2. QRIS Dynamic

**Payment Method**:
- **QRIS Dynamic**: Generate QRIS code unik per transaksi

---

## Database Schema

### Payments Table

```sql
payments
├── id (bigint)
├── invoice_id (foreign key)
├── customer_id (foreign key)
├── payment_gateway (duitku, qris)
├── payment_method (va_bca, qris, ovo, etc)
├── payment_channel (channel name)
├── transaction_id (unique)
├── reference_id (unique)
├── merchant_order_id
├── amount (decimal)
├── admin_fee (decimal)
├── total_amount (decimal)
├── status (pending, processing, success, failed, expired, cancelled)
├── va_number (nullable)
├── qris_string (nullable)
├── qris_url (nullable)
├── paid_at (timestamp)
├── expired_at (timestamp)
├── payment_date (datetime)
├── callback_data (json)
├── notes (text)
├── ip_address
├── user_agent
├── created_at
├── updated_at
└── deleted_at
```

**Indexes**: invoice_id, customer_id, payment_gateway, status, reference_id, transaction_id

---

## Configuration

### Environment Variables

Add to `.env`:

```env
# Payment Gateway Default
PAYMENT_DEFAULT_GATEWAY=duitku

# Duitku Configuration
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_api_key
DUITKU_BASE_URL=https://sandbox.duitku.com
DUITKU_CALLBACK_URL=${APP_URL}/api/payment/duitku/callback
DUITKU_RETURN_URL=${APP_URL}/payment/success

# QRIS Configuration
QRIS_MERCHANT_ID=your_merchant_id
QRIS_API_KEY=your_api_key
QRIS_BASE_URL=https://api.qris-provider.com
QRIS_CALLBACK_URL=${APP_URL}/api/payment/qris/callback
QRIS_EXPIRY_HOURS=24

# Payment Settings
PAYMENT_AUTO_EXPIRE_HOURS=24
PAYMENT_ENABLE_VA=true
PAYMENT_ENABLE_EWALLET=true
PAYMENT_ENABLE_RETAIL=true
PAYMENT_ENABLE_QRIS=true
PAYMENT_MIN_AMOUNT=10000
PAYMENT_MAX_AMOUNT=50000000
```

### Production URLs

**Duitku Production**:
```env
DUITKU_BASE_URL=https://passport.duitku.com
```

**QRIS Production**:
```env
QRIS_BASE_URL=https://api.qris-production.com
```

---

## API Endpoints

### 1. Create Payment

**Endpoint**: `POST /api/payment/create`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "invoice_id": 123,
  "payment_gateway": "duitku",
  "payment_method": "BC"
}
```

**Response Success**:
```json
{
  "success": true,
  "message": "Pembayaran berhasil dibuat",
  "data": {
    "success": true,
    "payment": {
      "id": 1,
      "transaction_id": "TRX-123-1234567890-ABC123",
      "status": "pending",
      ...
    },
    "payment_url": "https://sandbox.duitku.com/payment/...",
    "va_number": "8808123456789012",
    "reference": "D12345678"
  }
}
```

---

### 2. Check Payment Status

**Endpoint**: `GET /api/payment/{payment_id}/status`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "payment": {
    "id": 1,
    "status": "success",
    "paid_at": "2026-05-27 10:30:00",
    ...
  },
  "gateway_status": {
    "statusCode": "00",
    "statusMessage": "SUCCESS"
  }
}
```

---

### 3. Payment History

**Endpoint**: `GET /api/payment/invoice/{invoice_id}/history`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "payments": [
    {
      "id": 1,
      "transaction_id": "TRX-123-...",
      "payment_gateway": "duitku",
      "payment_method": "BC",
      "status": "success",
      "amount": 150000,
      "paid_at": "2026-05-27 10:30:00"
    }
  ]
}
```

---

### 4. Duitku Callback (Webhook)

**Endpoint**: `POST /api/payment/duitku/callback`

**No Authentication Required** (called by Duitku)

**Request Body** (from Duitku):
```json
{
  "merchantCode": "D12345",
  "amount": "150000",
  "merchantOrderId": "INV-123-1234567890",
  "productDetail": "Pembayaran Invoice...",
  "additionalParam": "",
  "paymentCode": "BC",
  "resultCode": "00",
  "merchantUserId": "customer@email.com",
  "reference": "D12345678",
  "signature": "abc123..."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Callback processed successfully"
}
```

---

### 5. QRIS Callback (Webhook)

**Endpoint**: `POST /api/payment/qris/callback`

**No Authentication Required** (called by QRIS provider)

**Request Body**:
```json
{
  "reference_id": "QRIS-123-1234567890",
  "status": "paid",
  "amount": 150000,
  "paid_at": "2026-05-27T10:30:00+07:00",
  "signature": "abc123..."
}
```

---

## Web Routes

### 1. Payment Page

**URL**: `/payment/invoice/{invoice_id}`

Menampilkan halaman pembayaran dengan pilihan metode pembayaran.

### 2. Payment Success

**URL**: `/payment/success?merchantOrderId={order_id}`

Halaman konfirmasi pembayaran berhasil.

### 3. Payment Failed

**URL**: `/payment/failed`

Halaman pembayaran gagal.

---

## Usage Examples

### Create Payment (Duitku - Virtual Account BCA)

```php
use App\Services\PaymentGateway\DuitkuService;

$duitku = new DuitkuService();
$invoice = Invoice::find(123);

$result = $duitku->createTransaction($invoice, 'BC'); // BC = BCA VA

if ($result['success']) {
    $payment = $result['payment'];
    $vaNumber = $result['va_number'];
    $paymentUrl = $result['payment_url'];
    
    // Redirect user to payment URL or show VA number
}
```

### Create Payment (QRIS)

```php
use App\Services\PaymentGateway\QrisService;

$qris = new QrisService();
$invoice = Invoice::find(123);

$result = $qris->createQris($invoice);

if ($result['success']) {
    $payment = $result['payment'];
    $qrisString = $result['qris_string'];
    $qrisUrl = $result['qris_url'];
    
    // Display QRIS code to user
}
```

### Check Payment Status

```php
$payment = Payment::find(1);

if ($payment->payment_gateway === 'duitku') {
    $duitku = new DuitkuService();
    $result = $duitku->checkStatus($payment->merchant_order_id);
} else {
    $qris = new QrisService();
    $result = $qris->checkStatus($payment->reference_id);
}
```

---

## Console Commands

### Auto-Expire Payments

Automatically expire pending payments that have passed their expiry time.

```bash
# Run auto-expire
php artisan payment:auto-expire

# Dry-run mode (test without expiring)
php artisan payment:auto-expire --dry-run
```

**Cron Schedule** (add to `app/Console/Kernel.php`):
```php
// Run every hour
$schedule->command('payment:auto-expire')
         ->hourly();
```

---

## Payment Flow

### 1. Customer Initiates Payment

```
Customer → Invoice Page → Select Payment Method → Create Payment
```

### 2. Payment Created

```
System → Payment Gateway API → Generate VA/QRIS → Save to Database
```

### 3. Customer Pays

```
Customer → Bank/E-wallet → Pay using VA/QRIS
```

### 4. Payment Gateway Callback

```
Payment Gateway → Webhook → Update Payment Status → Update Invoice Status
```

### 5. Auto-Unsuspend (if applicable)

```
Payment Success → Check Customer Status → Unsuspend Service (Mikrotik)
```

---

## Payment Status Flow

```
pending → processing → success
        ↓
        expired
        ↓
        failed
        ↓
        cancelled
```

**Status Descriptions**:
- `pending`: Menunggu pembayaran
- `processing`: Sedang diproses
- `success`: Pembayaran berhasil
- `failed`: Pembayaran gagal
- `expired`: Pembayaran kadaluarsa
- `cancelled`: Pembayaran dibatalkan

---

## Security

### 1. Signature Validation

**Duitku**:
```php
$signature = md5($merchantCode . $amount . $merchantOrderId . $apiKey);
```

**QRIS**:
```php
$signature = hash_hmac('sha256', $stringToSign, $apiKey);
```

### 2. Callback Validation

- Validate signature dari payment gateway
- Verify merchant code/ID
- Check amount consistency
- Prevent duplicate processing

### 3. IP Whitelisting (Optional)

Whitelist IP payment gateway di firewall untuk callback endpoints.

---

## Testing

### Sandbox Mode

**Duitku Sandbox**:
- URL: `https://sandbox.duitku.com`
- Test cards dan VA tersedia di dokumentasi Duitku

**QRIS Sandbox**:
- URL: Sesuai provider QRIS
- Test QRIS code untuk testing

### Test Payment Flow

1. Create test invoice
2. Generate payment dengan sandbox credentials
3. Use test payment method
4. Trigger callback manually atau via simulator
5. Verify payment status updated
6. Verify invoice status updated

---

## Monitoring & Logging

### Log Locations

```
storage/logs/laravel.log
```

### Important Logs

- Payment creation
- Callback received
- Payment status changes
- Errors and exceptions

### Monitor Commands

```bash
# View logs in real-time
tail -f storage/logs/laravel.log

# Check failed payments
php artisan tinker
>>> Payment::where('status', 'failed')->count()

# Check pending payments
>>> Payment::where('status', 'pending')->count()
```

---

## Troubleshooting

### Payment Not Created

**Check**:
1. API credentials correct
2. Network connectivity
3. Payment gateway API status
4. Log errors in `storage/logs/laravel.log`

### Callback Not Received

**Check**:
1. Callback URL accessible from internet
2. Firewall not blocking
3. SSL certificate valid
4. Signature validation passing

### Payment Status Not Updated

**Check**:
1. Callback signature validation
2. Payment record exists
3. Database connection
4. Log callback data

---

## Production Checklist

- [ ] Update to production API URLs
- [ ] Use production API credentials
- [ ] Setup SSL certificate for callback URLs
- [ ] Configure firewall for callback IPs
- [ ] Test callback endpoints
- [ ] Setup monitoring alerts
- [ ] Configure cron for auto-expire
- [ ] Test payment flow end-to-end
- [ ] Setup backup for payment data
- [ ] Document API credentials securely

---

## Future Enhancements

1. **Refund System**: Implement refund functionality
2. **Recurring Payments**: Auto-debit untuk subscription
3. **Payment Analytics**: Dashboard untuk payment metrics
4. **Multi-Currency**: Support multiple currencies
5. **Payment Links**: Generate payment links via email/SMS
6. **Installment**: Support cicilan/installment
7. **Promo Codes**: Discount codes integration
8. **Payment Reminder**: Auto-reminder untuk pending payments

---

**Version**: 2.2.0  
**Last Updated**: 2026-05-27  
**Status**: Production Ready
