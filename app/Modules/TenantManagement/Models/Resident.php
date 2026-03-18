<?php

declare(strict_types=1);

namespace Modules\TenantManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج المستأجر (Resident)
 * سمّيناه Resident بدل Tenant لتجنب التعارض مع SaaS Tenant
 */
class Resident extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'name_en', 'national_id_type', 'national_id',
        'phone', 'phone_2', 'email', 'nationality',
        'company_name', 'commercial_reg', 'tax_number',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'status', 'credit_score', 'rating', 'onboarding_completed',
        'source', 'notes', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'credit_score' => 'integer',
        'rating' => 'decimal:1',
        'onboarding_completed' => 'boolean',
    ];

    const STATUS_PROSPECT = 'prospect';
    const STATUS_ACTIVE = 'active';
    const STATUS_FORMER = 'former';
    const STATUS_BLACKLISTED = 'blacklisted';

    const SOURCE_WALK_IN = 'walk_in';
    const SOURCE_ONLINE = 'online';
    const SOURCE_REFERRAL = 'referral';
    const SOURCE_AGENT = 'agent';
    const SOURCE_AQAR = 'aqar';
    const SOURCE_HARAJ = 'haraj';

    // ─── Relationships ───────────────────────────────

    public function leases(): HasMany
    {
        return $this->hasMany(\Modules\Leasing\Models\Lease::class);
    }

    public function activeLeases(): HasMany
    {
        return $this->hasMany(\Modules\Leasing\Models\Lease::class)
            ->where('status', 'active');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Collection\Models\Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\Modules\Collection\Models\Payment::class);
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(\Modules\Collection\Models\PaymentSchedule::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(\App\Core\Documents\Models\Document::class, 'documentable');
    }

    public function communications(): MorphMany
    {
        return $this->morphMany(\App\Core\Notifications\Models\Communication::class, 'communicatable');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeProspects($query)
    {
        return $query->where('status', self::STATUS_PROSPECT);
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('status', self::STATUS_BLACKLISTED);
    }

    // ─── Computed ────────────────────────────────────

    public function getIsCommercialAttribute(): bool
    {
        return !empty($this->commercial_reg);
    }

    public function getTotalOutstandingAttribute(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['issued', 'sent', 'partially_paid', 'overdue'])
            ->sum('balance');
    }

    public function getPaymentHistoryScoreAttribute(): float
    {
        $total = $this->invoices()->whereIn('status', ['paid', 'overdue'])->count();
        if ($total === 0) return 100;

        $onTime = $this->invoices()
            ->where('status', 'paid')
            ->whereColumn('paid_date', '<=', 'due_date')
            ->count();

        return round(($onTime / $total) * 100, 1);
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} المستأجر: {$this->name}");
    }
}
