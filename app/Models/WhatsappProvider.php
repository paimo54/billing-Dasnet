<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'api_url',
        'sender_number',
        'is_active',
        'is_default',
        'config',
        'daily_limit',
        'daily_sent',
        'last_reset_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'config' => 'array',
        'daily_limit' => 'integer',
        'daily_sent' => 'integer',
        'last_reset_date' => 'date',
    ];

    // Provider constants
    const PROVIDER_FONNTE = 'fonnte';
    const PROVIDER_WABLAS = 'wablas';
    const PROVIDER_WOOWA = 'woowa';
    const PROVIDER_TWILIO = 'twilio';

    /**
     * Check if provider is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if provider is default
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if daily limit reached
     */
    public function hasReachedDailyLimit(): bool
    {
        $this->resetDailyCountIfNeeded();
        return $this->daily_sent >= $this->daily_limit;
    }

    /**
     * Increment daily sent count
     */
    public function incrementDailySent(): bool
    {
        $this->resetDailyCountIfNeeded();
        return $this->increment('daily_sent');
    }

    /**
     * Reset daily count if needed
     */
    protected function resetDailyCountIfNeeded(): void
    {
        if ($this->last_reset_date === null || $this->last_reset_date->isYesterday()) {
            $this->update([
                'daily_sent' => 0,
                'last_reset_date' => now()->toDateString(),
            ]);
        }
    }

    /**
     * Get remaining daily quota
     */
    public function getRemainingQuota(): int
    {
        $this->resetDailyCountIfNeeded();
        return max(0, $this->daily_limit - $this->daily_sent);
    }

    /**
     * Scope: Active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default provider
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: By provider type
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: With available quota
     */
    public function scopeWithQuota($query)
    {
        return $query->whereRaw('daily_sent < daily_limit')
            ->orWhere(function ($q) {
                $q->where('last_reset_date', '<', now()->toDateString())
                    ->orWhereNull('last_reset_date');
            });
    }
}
