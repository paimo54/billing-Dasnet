<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Router extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'identity',
        'host',
        'port',
        'username',
        'password',
        'region',
        'location',
        'latitude',
        'longitude',
        'max_capacity',
        'current_users',
        'status',
        'last_check',
        'last_error',
        'radius_secret',
        'use_radius',
        'priority',
        'auto_assign',
        'description',
        'metadata',
    ];

    protected $casts = [
        'port' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'max_capacity' => 'integer',
        'current_users' => 'integer',
        'last_check' => 'datetime',
        'use_radius' => 'boolean',
        'priority' => 'integer',
        'auto_assign' => 'boolean',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_ERROR = 'error';

    /**
     * Get customers assigned to this router
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get coverage areas served by this router
     */
    public function coverageAreas()
    {
        return $this->belongsToMany(CoverageArea::class, 'router_coverage_area')
            ->withPivot('priority', 'is_primary', 'signal_strength')
            ->withTimestamps();
    }

    /**
     * Check if router is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if router is available for new assignments
     */
    public function isAvailable(): bool
    {
        return $this->isActive()
            && $this->auto_assign
            && $this->current_users < $this->max_capacity;
    }

    /**
     * Get capacity utilization percentage
     */
    public function getCapacityPercentage(): float
    {
        if ($this->max_capacity == 0) {
            return 0;
        }
        return round(($this->current_users / $this->max_capacity) * 100, 2);
    }

    /**
     * Check if router is near capacity
     */
    public function isNearCapacity(int $threshold = 90): bool
    {
        return $this->getCapacityPercentage() >= $threshold;
    }

    /**
     * Mark router as active
     */
    public function markAsActive(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'last_check' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark router as error
     */
    public function markAsError(string $error): bool
    {
        return $this->update([
            'status' => self::STATUS_ERROR,
            'last_check' => now(),
            'last_error' => $error,
        ]);
    }

    /**
     * Mark router as maintenance
     */
    public function markAsMaintenance(): bool
    {
        return $this->update([
            'status' => self::STATUS_MAINTENANCE,
            'last_check' => now(),
        ]);
    }

    /**
     * Increment current users count
     */
    public function incrementUsers(): bool
    {
        return $this->increment('current_users');
    }

    /**
     * Decrement current users count
     */
    public function decrementUsers(): bool
    {
        if ($this->current_users > 0) {
            return $this->decrement('current_users');
        }
        return true;
    }

    /**
     * Scope: Active routers
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Available for assignment
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('auto_assign', true)
            ->whereRaw('current_users < max_capacity');
    }

    /**
     * Scope: By region
     */
    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope: Order by priority
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope: Order by load (least loaded first)
     */
    public function scopeOrderByLoad($query)
    {
        return $query->orderByRaw('(current_users / max_capacity) ASC');
    }
}
