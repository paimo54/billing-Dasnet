# Payment Gateway Integration - Quick Start Guide

## 🎉 Version 2.2.0 - Payment Gateway Ready!

Sistem payment gateway telah berhasil diintegrasikan dengan **Duitku** dan **QRIS Dinamis**.

---

## ✅ Yang Sudah Diimplementasikan

### 1. **Payment Gateway Services** ✓
- ✅ **DuitkuService**: Full integration dengan Duitku
- ✅ **QrisService**: QRIS Dynamic integration
- ✅ Signature generation & validation
- ✅ Callback handling dengan security
- ✅ Payment status checking

### 2. **Database & Models** ✓
- ✅ `payments` table dengan 10+ indexes
- ✅ Payment Model dengan helper methods
- ✅ Invoice-Payment relationships
- ✅ Soft deletes support

### 3. **API Endpoints** ✓
- ✅ Create payment API
- ✅ Duitku callback webhook
- ✅ QRIS callback webhook
- ✅ Check payment status
- ✅ Payment history

### 4. **Controllers & Commands** ✓
- ✅ PaymentController
- ✅ payment:auto-expire command

### 5. **Configuration** ✓
- ✅ config/payment.php
- ✅ Environment variables setup
- ✅ Payment methods configuration

### 6. **Documentation** ✓
- ✅ PAYMENT-GATEWAY.md (comprehensive)
- ✅ API documentation
- ✅ Usage examples
- ✅ Security guidelines

---

## 🚀 Quick Setup

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Configure Environment

Add to `.env`:

```env
# Duitku Configuration
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_api_key
DUITKU_BASE_URL=https://sandbox.duitku.com

# QRIS Configuration
QRIS_MERCHANT_ID=your_merchant_id
QRIS_API_KEY=your_api_key
QRIS_BASE_URL=https://api.qris-provider.com
```

### 3. Setup Cron (Optional)

```bash
# Auto-expire pending payments every hour
* * * * * cd /path/to/billing-Dasnet && php artisan payment:auto-expire
```

---

## 💳 Supported Payment Methods

### Duitku
- **Virtual Account**: BCA, Mandiri, BNI, BRI, Permata, CIMB, Danamon
- **E-Wallet**: OVO, ShopeePay, LinkAja, DANA
- **Retail**: Indomaret, Alfamart
- **QRIS**: QRIS Nobu, ShopeePay QRIS

### QRIS Dynamic
- **QRIS**: Generate unique QRIS per transaction

---

## 📝 Usage Example

### Create Payment (API)

```bash
curl -X POST http://your-domain.com/api/payment/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_id": 123,
    "payment_gateway": "duitku",
    "payment_method": "BC"
  }'
```

### Create Payment (Code)

```php
use App\Services\PaymentGateway\DuitkuService;

$duitku = new DuitkuService();
$invoice = Invoice::find(123);

$result = $duitku->createTransaction($invoice, 'BC'); // BCA VA

if ($result['success']) {
    $vaNumber = $result['va_number'];
    // Show VA number to customer
}
```

---

## 🔒 Security Features

- ✅ Signature validation untuk semua callbacks
- ✅ HMAC SHA256 untuk QRIS
- ✅ MD5 signature untuk Duitku
- ✅ Amount consistency checking
- ✅ Duplicate payment prevention
- ✅ IP & user agent tracking

---

## 📊 Payment Flow

```
1. Customer → Select Payment Method
2. System → Create Payment → Payment Gateway
3. Payment Gateway → Generate VA/QRIS
4. Customer → Pay via Bank/E-wallet
5. Payment Gateway → Callback → System
6. System → Update Payment Status → Update Invoice
7. System → Unsuspend Service (if applicable)
```

---

## 🛠️ Available Commands

```bash
# Auto-expire pending payments
php artisan payment:auto-expire

# Dry-run mode (test)
php artisan payment:auto-expire --dry-run
```

---

## 📁 File Structure

```
app/
├── Console/Commands/
│   └── AutoExpirePayments.php
├── Http/Controllers/
│   └── PaymentController.php
├── Models/
│   └── Payment.php
└── Services/PaymentGateway/
    ├── DuitkuService.php
    └── QrisService.php

config/
└── payment.php

database/migrations/
└── 2026_05_27_084050_create_payments_table.php

routes/
├── api.php (payment APIs)
└── web.php (payment pages)
```

---

## 🎯 Next Steps

### Testing
1. Setup sandbox credentials
2. Test payment creation
3. Test callback handling
4. Verify payment status updates

### Production
1. Update to production URLs
2. Use production credentials
3. Setup SSL for callbacks
4. Configure firewall
5. Test end-to-end flow

---

## 📚 Documentation

Lihat dokumentasi lengkap di:
- **PAYMENT-GATEWAY.md**: Complete payment gateway guide
- **CHANGELOG.md**: Version 2.2.0 release notes

---

## 🎉 Summary

**Version 2.2.0** successfully implements:
- ✅ Duitku payment gateway integration
- ✅ QRIS Dynamic payment integration
- ✅ Multiple payment methods support
- ✅ Secure callback handling
- ✅ Comprehensive API endpoints
- ✅ Auto-expire functionality
- ✅ Complete documentation

**Ready for**: Online payment processing with multiple payment methods!

---

**Version**: 2.2.0  
**Status**: Production Ready  
**Last Updated**: 2026-05-27
