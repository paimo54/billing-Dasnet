<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoverageArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'region',
        'description',
        'polygon_coordinates',
        'center_latitude',
        'center_longitude',
        'radius_meters',
        'is_active',
        'service_start_date',
        'estimated_capacity',
        'current_subscribers',
        'signal_quality',
        'coverage_notes',
        'color_hex',
        'display_order',
        'show_on_map',
        'metadata',
    ];

    protected $casts = [
        'polygon_coordinates' => 'array',
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'radius_meters' => 'integer',
        'is_active' => 'boolean',
        'service_start_date' => 'date',
        'estimated_capacity' => 'integer',
        'current_subscribers' => 'integer',
        'display_order' => 'integer',
        'show_on_map' => 'boolean',
        'metadata' => 'array',
    ];

    // Signal quality constants
    const SIGNAL_EXCELLENT = 'excellent';
    const SIGNAL_GOOD = 'good';
    const SIGNAL_FAIR = 'fair';
    const SIGNAL_POOR = 'poor';

    /**
     * Get customers in this coverage area
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get routers serving this coverage area
     */
    public function routers()
    {
        return $this->belongsToMany(Router::class, 'router_coverage_area')
            ->withPivot('priority', 'is_primary', 'signal_strength')
            ->withTimestamps();
    }

    /**
     * Get primary router for this coverage area
     */
    public function primaryRouter()
    {
        return $this->routers()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Get available routers (active and not full)
     */
    public function availableRouters()
    {
        return $this->routers()
            ->where('status', Router::STATUS_ACTIVE)
            ->where('auto_assign', true)
            ->whereRaw('current_users < max_capacity')
            ->orderByPivot('priority', 'desc')
            ->orderByRaw('(current_users / max_capacity) ASC');
    }

    /**
     * Check if coverage area is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if coverage area has available capacity
     */
    public function hasCapacity(): bool
    {
        if ($this->estimated_capacity === null) {
            return true;
        }
        return $this->current_subscribers < $this->estimated_capacity;
    }

    /**
     * Get capacity utilization percentage
     */
    public function getCapacityPercentage(): float
    {
        if ($this->estimated_capacity === null || $this->estimated_capacity == 0) {
            return 0;
        }
        return round(($this->current_subscribers / $this->estimated_capacity) * 100, 2);
    }

    /**
     * Increment subscribers count
     */
    public function incrementSubscribers(): bool
    {
        return $this->increment('current_subscribers');
    }

    /**
     * Decrement subscribers count
     */
    public function decrementSubscribers(): bool
    {
        if ($this->current_subscribers > 0) {
            return $this->decrement('current_subscribers');
        }
        return true;
    }

    /**
     * Get GeoJSON feature for map display
     */
    public function toGeoJson(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $this->id,
                'name' => $this->name,
                'region' => $this->region,
                'description' => $this->description,
                'signal_quality' => $this->signal_quality,
                'is_active' => $this->is_active,
                'color' => $this->color_hex,
                'subscribers' => $this->current_subscribers,
                'capacity' => $this->estimated_capacity,
            ],
            'geometry' => [
                'type' => $this->polygon_coordinates ? 'Polygon' : 'Point',
                'coordinates' => $this->polygon_coordinates ?? [
                    $this->center_longitude,
                    $this->center_latitude
                ],
            ],
        ];
    }

    /**
     * Scope: Active coverage areas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Visible on map
     */
    public function scopeVisibleOnMap($query)
    {
        return $query->where('is_active', true)
            ->where('show_on_map', true);
    }

    /**
     * Scope: By region
     */
    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope: Order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc');
    }

    /**
     * Scope: With available capacity
     */
    public function scopeWithCapacity($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('estimated_capacity')
                ->orWhereRaw('current_subscribers < estimated_capacity');
        });
    }
}
