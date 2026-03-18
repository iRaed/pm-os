<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * خطة الصيانة الوقائية
 */
class PmPlan extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'property_id', 'name', 'asset_type', 'asset_identifier',
        'frequency', 'frequency_value', 'next_scheduled_date', 'last_executed_date',
        'checklist', 'instructions',
        'contractor_id', 'assigned_user_id', 'estimated_cost',
        'is_active', 'auto_create_wo', 'metadata',
    ];

    protected $casts = [
        'checklist' => 'array',
        'metadata' => 'array',
        'estimated_cost' => 'decimal:2',
        'next_scheduled_date' => 'date',
        'last_executed_date' => 'date',
        'is_active' => 'boolean',
        'auto_create_wo' => 'boolean',
        'frequency_value' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Property::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\User::class, 'assigned_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PmLog::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()->where('next_scheduled_date', '<=', now());
    }

    /**
     * حساب تاريخ الصيانة القادم بعد التنفيذ
     */
    public function calculateNextDate(): \Carbon\Carbon
    {
        $base = $this->last_executed_date ?? now();

        return match ($this->frequency) {
            'daily' => $base->addDays($this->frequency_value),
            'weekly' => $base->addWeeks($this->frequency_value),
            'monthly' => $base->addMonths($this->frequency_value),
            'quarterly' => $base->addMonths($this->frequency_value * 3),
            'semi_annual' => $base->addMonths($this->frequency_value * 6),
            'annual' => $base->addYears($this->frequency_value),
            default => $base->addMonths(1),
        };
    }
}
