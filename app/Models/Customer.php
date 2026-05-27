<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'address',
        'phone',
        'latitude',
        'longitude',
        'region',
        'billing_date',
        'package_id',
        'router_id',
        'coverage_area_id',
        'router_assigned_at',
        'pppoe_username',
        'pppoe_password',
        'created_by',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'billing_date' => 'date',
        'router_assigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the package that owns the customer.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user that created the customer.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the invoices for the customer.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the router assigned to the customer.
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get the coverage area of the customer.
     */
    public function coverageArea()
    {
        return $this->belongsTo(CoverageArea::class);
    }

    /**
     * Get the payments for the customer.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
