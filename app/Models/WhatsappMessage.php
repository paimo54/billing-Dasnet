<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'template_id',
        'phone',
        'message',
        'template_type',
        'status',
        'provider',
        'message_id',
        'response',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';

    /**
     * Get the customer that owns the message
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the template used for this message
     */
    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    /**
     * Mark message as sent
     */
    public function markAsSent(string $messageId = null, string $response = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'message_id' => $messageId,
            'response' => $response,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Check if message is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if message is sent
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if message is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Scope: Pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Sent messages
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope: Failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: By customer
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: By template type
     */
    public function scopeByTemplateType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope: Recent messages
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
