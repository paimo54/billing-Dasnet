<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Invoice relationship
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');

            // Payment gateway info
            $table->string('payment_gateway', 50); // duitku, qris
            $table->string('payment_method', 50); // va_bca, va_bni, qris, etc
            $table->string('payment_channel', 100)->nullable(); // Channel name

            // Transaction details
            $table->string('transaction_id')->unique(); // Our internal transaction ID
            $table->string('reference_id')->unique(); // Payment gateway reference
            $table->string('merchant_order_id')->nullable(); // Duitku merchantOrderId

            // Amount
            $table->decimal('amount', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // amount + admin_fee

            // Payment status
            $table->enum('status', [
                'pending',      // Menunggu pembayaran
                'processing',   // Sedang diproses
                'success',      // Berhasil
                'failed',       // Gagal
                'expired',      // Kadaluarsa
                'cancelled'     // Dibatalkan
            ])->default('pending');

            // Virtual Account / QRIS details
            $table->string('va_number')->nullable(); // Virtual Account number
            $table->text('qris_string')->nullable(); // QRIS string
            $table->string('qris_url')->nullable(); // QRIS image URL

            // Payment info
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->dateTime('payment_date')->nullable(); // Tanggal pembayaran dari gateway

            // Callback data
            $table->json('callback_data')->nullable(); // Raw callback data from gateway
            $table->text('notes')->nullable();

            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('invoice_id');
            $table->index('customer_id');
            $table->index('payment_gateway');
            $table->index('status');
            $table->index('reference_id');
            $table->index('transaction_id');
            $table->index(['invoice_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
