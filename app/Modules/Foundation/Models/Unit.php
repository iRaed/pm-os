<?php

declare(strict_types=1);

namespace Modules\Foundation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج الوحدة العقارية
 */
class Unit extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'property_id', 'unit_number', 'unit_code', 'floor',
        'type', 'status',
        'area_sqm', 'rooms', 'bathrooms', 'living_rooms',
        'has_kitchen', 'has_balcony', 'view_direction',
        'base_rent', 'market_rent', 'last_rent',
        'meter_numbers', 'features', 'appliances',
        'last_inspection_date', 'condition_rating',
        'metadata',
    ];

    protected $casts = [
        'meter_numbers' => 'array',
        'features' => 'array',
        'appliances' => 'array',
        'metadata' => 'array',
        'area_sqm' => 'decimal:2',
        'base_rent' => 'decimal:2',
        'market_rent' => 'decimal:2',
        'last_rent' => 'decimal:2',
        'has_kitchen' => 'boolean',
        'has_balcony' => 'boolean',
        'last_inspection_date' => 'date',
    ];

    // ─── أنواع الوحدات ───────────────────────────────
    const TYPE_APARTMENT = 'apartment';
    const TYPE_OFFICE = 'office';
    const TYPE_SHOP = 'shop';
    const TYPE_STUDIO = 'studio';
    const TYPE_VILLA = 'villa';
    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_PARKING = 'parking';
    const TYPE_STORAGE = 'storage';
    const TYPE_OTHER = 'other';

    // ─── حالات الوحدة ────────────────────────────────
    const STATUS_VACANT = 'vacant';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    const STATUS_RESERVED = 'reserved';
    const STATUS_NOT_AVAILABLE = 'not_available';

    // ─── Relationships ───────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(\Modules\Leasing\Models\Lease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(\Modules\Leasing\Models\Lease::class)
            ->where('status', 'active')
            ->latest('start_date');
    }

    public function currentResident()
    {
        return $this->hasOneThrough(
            \Modules\TenantManagement\Models\Resident::class,
            \Modules\Leasing\Models\Lease::class,
            'unit_id',       // Foreign key on leases
            'id',            // Foreign key on residents
            'id',            // Local key on units
            'resident_id'    // Local key on leases
        )->where('leases.status', 'active');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(\Modules\Maintenance\Models\WorkOrder::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(\Modules\Maintenance\Models\Inspection::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Collection\Models\Invoice::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(\App\Core\Documents\Models\Document::class, 'documentable');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeVacant($query)
    {
        return $query->where('status', self::STATUS_VACANT);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ─── Computed ────────────────────────────────────

    public function getIsVacantAttribute(): bool
    {
        return $this->status === self::STATUS_VACANT;
    }

    public function getVacancyDaysAttribute(): ?int
    {
        if (!$this->is_vacant) return null;

        $lastLease = $this->leases()
            ->whereIn('status', ['expired', 'terminated'])
            ->latest('end_date')
            ->first();

        if (!$lastLease) return null;

        return (int) now()->diffInDays($lastLease->end_date);
    }

    public function getFullCodeAttribute(): string
    {
        return $this->unit_code ?? "{$this->property->code}-U-{$this->unit_number}";
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} الوحدة: {$this->unit_number}");
    }
}
