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
 * نموذج العقار — الكيان المحوري في النظام
 *
 * يدعم 9 أنواع أصول عقارية مع metadata مرن لكل نوع
 */
class Property extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'owner_id', 'manager_id', 'name', 'name_en', 'code',
        'type', 'sub_type', 'status',
        'address_line', 'city', 'district', 'postal_code', 'additional_number',
        'lat', 'lng',
        'total_units', 'total_area_sqm', 'land_area_sqm',
        'year_built', 'floors_count', 'parking_spots',
        'amenities', 'onboarding_date', 'operation_start_date',
        'deed_date', 'deed_number',
        'risk_level', 'market_value', 'insurance_value',
        'metadata',
    ];

    protected $casts = [
        'amenities' => 'array',
        'metadata' => 'array',
        'total_area_sqm' => 'decimal:2',
        'land_area_sqm' => 'decimal:2',
        'market_value' => 'decimal:2',
        'insurance_value' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'onboarding_date' => 'date',
        'operation_start_date' => 'date',
        'deed_date' => 'date',
        'total_units' => 'integer',
        'floors_count' => 'integer',
        'parking_spots' => 'integer',
    ];

    // ─── أنواع العقارات ──────────────────────────────
    const TYPE_RESIDENTIAL_COMPOUND = 'residential_compound';  // مجمع سكني
    const TYPE_COMMERCIAL_BUILDING = 'commercial_building';    // مبنى تجاري
    const TYPE_TOWER = 'tower';                                // برج
    const TYPE_VILLA = 'villa';                                // فيلا
    const TYPE_MIXED_USE = 'mixed_use';                        // متعدد الاستخدام
    const TYPE_LAND = 'land';                                  // أرض
    const TYPE_WAREHOUSE = 'warehouse';                        // مستودع
    const TYPE_MALL = 'mall';                                  // مول تجاري
    const TYPE_OFFICE_BUILDING = 'office_building';            // مبنى مكاتب

    const TYPES = [
        self::TYPE_RESIDENTIAL_COMPOUND,
        self::TYPE_COMMERCIAL_BUILDING,
        self::TYPE_TOWER,
        self::TYPE_VILLA,
        self::TYPE_MIXED_USE,
        self::TYPE_LAND,
        self::TYPE_WAREHOUSE,
        self::TYPE_MALL,
        self::TYPE_OFFICE_BUILDING,
    ];

    // ─── حالات العقار ────────────────────────────────
    const STATUS_ONBOARDING = 'onboarding';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_ARCHIVED = 'archived';

    // ─── مستويات المخاطر ─────────────────────────────
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    // ─── Relationships ───────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function leases(): HasMany
    {
        return $this->hasManyThrough(
            \Modules\Leasing\Models\Lease::class,
            Unit::class,
        );
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(\Modules\Maintenance\Models\WorkOrder::class);
    }

    public function pmPlans(): HasMany
    {
        return $this->hasMany(\Modules\Maintenance\Models\PmPlan::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(\Modules\Maintenance\Models\Inspection::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(\App\Core\Documents\Models\Document::class, 'documentable');
    }

    public function communications(): MorphMany
    {
        return $this->morphMany(\App\Core\Notifications\Models\Communication::class, 'communicatable');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\LedgerEntry::class);
    }

    public function alerts(): MorphMany
    {
        return $this->morphMany(\App\Core\Notifications\Models\Alert::class, 'alertable');
    }

    public function risks(): MorphMany
    {
        return $this->morphMany(\Modules\RiskManagement\Models\Risk::class, 'riskable');
    }

    public function hoaAssociation(): HasOne
    {
        return $this->hasOne(\Modules\HOA\Models\HoaAssociation::class);
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    // ─── Computed Attributes ─────────────────────────

    /**
     * نسبة الإشغال
     */
    public function getOccupancyRateAttribute(): float
    {
        $total = $this->units()->count();
        if ($total === 0) return 0;

        $occupied = $this->units()->where('status', 'occupied')->count();
        return round(($occupied / $total) * 100, 1);
    }

    /**
     * عدد الوحدات الشاغرة
     */
    public function getVacantUnitsCountAttribute(): int
    {
        return $this->units()->where('status', 'vacant')->count();
    }

    /**
     * إجمالي الإيجارات الشهرية المتوقعة
     */
    public function getMonthlyRevenueAttribute(): float
    {
        return (float) $this->units()
            ->where('status', 'occupied')
            ->join('leases', 'units.id', '=', 'leases.unit_id')
            ->where('leases.status', 'active')
            ->selectRaw('SUM(CASE
                WHEN leases.rent_frequency = \'monthly\' THEN leases.rent_amount
                WHEN leases.rent_frequency = \'quarterly\' THEN leases.rent_amount / 3
                WHEN leases.rent_frequency = \'semi_annual\' THEN leases.rent_amount / 6
                WHEN leases.rent_frequency = \'annual\' THEN leases.rent_amount / 12
                ELSE 0
            END) as monthly')
            ->value('monthly') ?? 0;
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "تم {$eventName} العقار: {$this->name}");
    }

    // ─── Helpers ─────────────────────────────────────

    /**
     * إنشاء كود فريد للعقار
     */
    public static function generateCode(): string
    {
        $last = static::withTrashed()->orderByDesc('created_at')->value('code');
        $number = $last ? (int) str_replace('PROP-', '', $last) + 1 : 1;
        return 'PROP-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * هل العقار جاهز للتشغيل؟
     */
    public function isReadyForOperation(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->units()->count() > 0
            && $this->documents()->where('category', 'deed')->exists();
    }
}
