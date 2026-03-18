<?php

declare(strict_types=1);

namespace Modules\Leasing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

/**
 * نموذج عقد الإيجار
 */
class Lease extends Model
{
    use HasUuids, SoftDeletes, LogsActivity, HasStates;

    protected $fillable = [
        'unit_id', 'resident_id', 'created_by',
        'lease_number', 'ejar_contract_id',
        'type', 'status',
        'start_date', 'end_date', 'duration_months',
        'rent_amount', 'rent_frequency', 'deposit_amount', 'commission_amount', 'currency',
        'payment_method', 'terms', 'included_utilities',
        'auto_renew', 'renewal_increase_pct',
        'signed_at', 'signed_by_resident', 'signed_by_manager', 'document_path',
        'previous_lease_id', 'metadata',
    ];

    protected $casts = [
        'terms' => 'array',
        'included_utilities' => 'array',
        'metadata' => 'array',
        'rent_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'renewal_increase_pct' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'auto_renew' => 'boolean',
        'duration_months' => 'integer',
    ];

    // ─── الأنواع ─────────────────────────────────────
    const TYPE_NEW = 'new';
    const TYPE_RENEWAL = 'renewal';
    const TYPE_SUBLEASE = 'sublease';

    // ─── الحالات ─────────────────────────────────────
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRING = 'expiring';       // آخر 30 يوم
    const STATUS_EXPIRED = 'expired';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_CANCELLED = 'cancelled';

    // ─── تكرار الدفع ────────────────────────────────
    const FREQ_MONTHLY = 'monthly';
    const FREQ_QUARTERLY = 'quarterly';
    const FREQ_SEMI_ANNUAL = 'semi_annual';
    const FREQ_ANNUAL = 'annual';

    // ─── Relationships ───────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Unit::class);
    }

    public function property()
    {
        return $this->hasOneThrough(
            \Modules\Foundation\Models\Property::class,
            \Modules\Foundation\Models\Unit::class,
            'id',           // units.id
            'id',           // properties.id
            'unit_id',      // leases.unit_id
            'property_id'   // units.property_id
        );
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(\Modules\TenantManagement\Models\Resident::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\User::class, 'created_by');
    }

    public function previousLease(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_lease_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'previous_lease_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Collection\Models\Invoice::class);
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(\Modules\Collection\Models\PaymentSchedule::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(\App\Core\Documents\Models\Document::class, 'documentable');
    }

    public function alerts(): MorphMany
    {
        return $this->morphMany(\App\Core\Notifications\Models\Alert::class, 'alertable');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('end_date', '<', now());
    }

    // ─── Computed ────────────────────────────────────

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date) return null;
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function getIsExpiringAttribute(): bool
    {
        return $this->days_remaining !== null && $this->days_remaining <= 30 && $this->days_remaining > 0;
    }

    public function getMonthlyRentAttribute(): float
    {
        return match ($this->rent_frequency) {
            self::FREQ_MONTHLY => (float) $this->rent_amount,
            self::FREQ_QUARTERLY => round((float) $this->rent_amount / 3, 2),
            self::FREQ_SEMI_ANNUAL => round((float) $this->rent_amount / 6, 2),
            self::FREQ_ANNUAL => round((float) $this->rent_amount / 12, 2),
            default => (float) $this->rent_amount,
        };
    }

    public function getAnnualRentAttribute(): float
    {
        return match ($this->rent_frequency) {
            self::FREQ_MONTHLY => (float) $this->rent_amount * 12,
            self::FREQ_QUARTERLY => (float) $this->rent_amount * 4,
            self::FREQ_SEMI_ANNUAL => (float) $this->rent_amount * 2,
            self::FREQ_ANNUAL => (float) $this->rent_amount,
            default => (float) $this->rent_amount,
        };
    }

    public function getTotalCollectedAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'paid')->sum('total_amount');
    }

    public function getTotalOutstandingAttribute(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['issued', 'sent', 'partially_paid', 'overdue'])
            ->sum('balance');
    }

    // ─── Helpers ─────────────────────────────────────

    public static function generateLeaseNumber(): string
    {
        $year = now()->format('Y');
        $last = static::withTrashed()
            ->where('lease_number', 'like', "LSE-{$year}-%")
            ->orderByDesc('lease_number')
            ->value('lease_number');

        $number = $last ? (int) substr($last, -5) + 1 : 1;
        return "LSE-{$year}-" . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * حساب عدد الدفعات حسب تكرار الدفع
     */
    public function getInstallmentsCount(): int
    {
        $months = $this->start_date->diffInMonths($this->end_date);

        return match ($this->rent_frequency) {
            self::FREQ_MONTHLY => (int) $months,
            self::FREQ_QUARTERLY => (int) ceil($months / 3),
            self::FREQ_SEMI_ANNUAL => (int) ceil($months / 6),
            self::FREQ_ANNUAL => (int) ceil($months / 12),
            default => (int) $months,
        };
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} العقد: {$this->lease_number}");
    }
}
