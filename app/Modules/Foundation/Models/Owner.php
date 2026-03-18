<?php

declare(strict_types=1);

namespace Modules\Foundation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج المالك
 */
class Owner extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'name_en', 'national_id_type', 'national_id',
        'phone', 'phone_2', 'email', 'iban', 'bank_name',
        'management_fee_pct', 'contract_start', 'contract_end',
        'tax_registration_no', 'address', 'status', 'notes', 'metadata',
    ];

    protected $casts = [
        'address' => 'array',
        'metadata' => 'array',
        'management_fee_pct' => 'decimal:2',
        'contract_start' => 'date',
        'contract_end' => 'date',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    // ─── Relationships ───────────────────────────────

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\LedgerEntry::class);
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

    // ─── Computed ────────────────────────────────────

    public function getTotalUnitsAttribute(): int
    {
        return $this->properties->sum('total_units');
    }

    public function getActivePropertiesCountAttribute(): int
    {
        return $this->properties()->where('status', Property::STATUS_ACTIVE)->count();
    }

    public function getIsContractExpiredAttribute(): bool
    {
        return $this->contract_end && $this->contract_end->isPast();
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} المالك: {$this->name}");
    }
}
