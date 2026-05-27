# Panduan Setup Aplikasi Billing Dasnet

## Prerequisites
- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js & NPM (untuk frontend assets)

## Langkah-langkah Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Setup Environment

```bash
# Copy file .env.example ke .env
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Konfigurasi Database

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=billing_dasnet
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 4. Buat Database

```bash
# Buat database MySQL
mysql -u root -p
CREATE DATABASE billing_dasnet;
exit;
```

### 5. Jalankan Migration & Seeder

```bash
# Jalankan migration untuk membuat tabel
php artisan migrate

# Jalankan seeder untuk data awal (roles, admin, dll)
php artisan db:seed
```

### 6. Build Frontend Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 7. Jalankan Aplikasi

```bash
# Jalankan development server
php artisan serve
```

Aplikasi akan berjalan di: http://localhost:8000

## Default Login Credentials

Setelah menjalankan seeder, gunakan kredensial berikut:

### Super Admin
- Email: `superadmin@dasnet.com`
- Password: `password`

### Admin
- Email: `admin@dasnet.com`
- Password: `password`

### Technician
- Email: `technician@dasnet.com`
- Password: `password`

**PENTING:** Segera ubah password default setelah login pertama kali!

## Troubleshooting

### Error: "vendor/autoload.php not found"
```bash
composer install
```

### Error: "No application encryption key"
```bash
php artisan key:generate
```

### Error: "SQLSTATE[HY000] [1049] Unknown database"
Pastikan database sudah dibuat dan konfigurasi di `.env` sudah benar.

### Permission Error (Linux/Mac)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Permission Error (Windows)
Pastikan folder `storage` dan `bootstrap/cache` memiliki write permission.

## Fitur Aplikasi

- **Super Admin**: Manajemen admin, teknisi, paket, pelanggan, invoice, dan laporan keuangan lengkap
- **Admin**: Manajemen teknisi, pelanggan, invoice, paket (edit harga), dan laporan keuangan
- **Technician**: Manajemen pelanggan sendiri, invoice, dan laporan fee

## Struktur Role

1. **SuperAdmin**: Akses penuh ke semua fitur
2. **Admin**: Manajemen operasional dan keuangan
3. **Technician**: Manajemen pelanggan dan melihat fee sendiri

## Support

Jika ada masalah, silakan hubungi tim development atau buat issue di repository.
