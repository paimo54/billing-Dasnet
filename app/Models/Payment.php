<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'customer_id',
        'payment_gateway',
        'payment_method',
        'payment_channel',
        'transaction_id',
        'reference_id',
        'merchant_order_id',
        'amount',
        'admin_fee',
        'total_amount',
        'status',
        'va_number',
        'qris_string',
        'qris_url',
        'paid_at',
        'expired_at',
        'payment_date',
        'callback_data',
        'notes',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'payment_date' => 'datetime',
        'callback_data' => 'array',
    ];

    /**
     * Payment status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Payment gateway constants
     */
    const GATEWAY_DUITKU = 'duitku';
    const GATEWAY_QRIS = 'qris';

    /**
     * Get the invoice that owns the payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the customer that owns the payment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment is success
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Mark payment as success
     */
    public function markAsSuccess($paymentDate = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'paid_at' => now(),
            'payment_date' => $paymentDate ?? now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed($notes = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark payment as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for success payments
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope for specific gateway
     */
    public function scopeGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }
}
