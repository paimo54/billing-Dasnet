<?php

namespace Database\Seeders;

use App\Models\WhatsappTemplate;
use App\Models\WhatsappProvider;
use Illuminate\Database\Seeder;

class WhatsappSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default provider
        WhatsappProvider::create([
            'name' => 'Fonnte Default',
            'provider' => 'fonnte',
            'api_key' => env('FONNTE_API_KEY', 'your-api-key-here'),
            'api_url' => 'https://api.fonnte.com/send',
            'sender_number' => env('FONNTE_SENDER_NUMBER', ''),
            'is_active' => true,
            'is_default' => true,
            'daily_limit' => 1000,
            'daily_sent' => 0,
        ]);

        // Create default templates
        $templates = [
            [
                'name' => 'Invoice Created',
                'type' => 'invoice-created',
                'description' => 'Sent when new invoice is created',
                'message' => "Halo *{{customerName}}*,\n\nInvoice baru telah dibuat:\n📄 No. Invoice: *{{invoiceNumber}}*\n💰 Jumlah: *{{amount}}*\n📅 Jatuh Tempo: *{{dueDate}}*\n\nSilakan lakukan pembayaran sebelum tanggal jatuh tempo.\n\n💳 Link Pembayaran:\n{{paymentLink}}\n\nTerima kasih,\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'Invoice Reminder',
                'type' => 'invoice-reminder',
                'description' => 'Sent as reminder before due date',
                'message' => "Halo *{{customerName}}*,\n\nPengingat pembayaran invoice:\n📄 No. Invoice: *{{invoiceNumber}}*\n💰 Jumlah: *{{amount}}*\n📅 Jatuh Tempo: *{{dueDate}}*\n⏰ Sisa Waktu: *{{daysRemaining}} hari*\n\nMohon segera lakukan pembayaran untuk menghindari pemutusan layanan.\n\n💳 Link Pembayaran:\n{{paymentLink}}\n\nTerima kasih,\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'Payment Success',
                'type' => 'payment-success',
                'description' => 'Sent when payment is successful',
                'message' => "Halo *{{customerName}}*,\n\n✅ Pembayaran Anda telah berhasil!\n\n📄 No. Invoice: *{{invoiceNumber}}*\n💰 Jumlah: *{{amount}}*\n📅 Tanggal Bayar: *{{paymentDate}}*\n\nLayanan internet Anda akan aktif kembali dalam beberapa menit.\n\nTerima kasih atas pembayaran Anda!\n\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'Customer Registration Confirmation',
                'type' => 'customer-registered',
                'description' => 'Sent when customer submits registration',
                'message' => "Halo *{{customerName}}*,\n\nTerima kasih telah mendaftar di *{{companyName}}*!\n\n✅ Pendaftaran Anda telah kami terima dan sedang dalam proses verifikasi.\n\n📋 Data Pendaftaran:\n👤 Nama: {{customerName}}\n📞 Telepon: {{phone}}\n📧 Email: {{email}}\n📍 Alamat: {{address}}\n📦 Paket: {{profileName}}\n\nTim kami akan segera menghubungi Anda untuk proses instalasi.\n\nTerima kasih,\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'Customer Approved',
                'type' => 'customer-approved',
                'description' => 'Sent when admin approves customer registration',
                'message' => "Halo *{{customerName}}*,\n\n🎉 Selamat! Pendaftaran Anda telah disetujui.\n\n📋 Informasi Akun:\n👤 Username: *{{username}}*\n🔐 Password: *{{password}}*\n📦 Paket: *{{profileName}}*\n📅 Aktif Hingga: *{{expiredAt}}*\n\n💳 Invoice Instalasi:\n📄 No. Invoice: *{{invoiceNumber}}*\n💰 Jumlah: *{{amount}}*\n\n💳 Link Pembayaran:\n{{paymentLink}}\n\nSetelah pembayaran, teknisi kami akan segera melakukan instalasi.\n\nTerima kasih,\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance Notification',
                'type' => 'maintenance-notification',
                'description' => 'Sent for maintenance announcements',
                'message' => "Halo *{{customerName}}*,\n\n⚠️ *PEMBERITAHUAN MAINTENANCE*\n\n🔧 Kami akan melakukan maintenance pada:\n📅 Tanggal: *{{maintenanceDate}}*\n⏰ Waktu: *{{maintenanceTime}}*\n⏱️ Durasi: *{{duration}}*\n📍 Area: *{{affectedArea}}*\n\n📝 Keterangan:\n{{description}}\n\nMohon maaf atas ketidaknyamanannya.\n\nTerima kasih,\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'Invoice Overdue',
                'type' => 'invoice-overdue',
                'description' => 'Sent when invoice is overdue',
                'message' => "Halo *{{customerName}}*,\n\n⚠️ *PERINGATAN TAGIHAN TERLAMBAT*\n\n📄 No. Invoice: *{{invoiceNumber}}*\n💰 Jumlah: *{{amount}}*\n📅 Jatuh Tempo: *{{dueDate}}*\n⏰ Terlambat: *{{daysOverdue}} hari*\n\nLayanan Anda akan diputus jika tidak segera melakukan pembayaran.\n\n💳 Link Pembayaran:\n{{paymentLink}}\n\nMohon segera lakukan pembayaran.\n\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
            [
                'name' => 'General Broadcast',
                'type' => 'general-broadcast',
                'description' => 'General broadcast to customers',
                'message' => "Halo *{{customerName}}*,\n\n{{message}}\n\nTerima kasih,\n*{{companyName}}*\n📞 {{companyPhone}}",
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            WhatsappTemplate::create($template);
        }

        $this->command->info('WhatsApp templates and provider seeded successfully!');
    }
}

