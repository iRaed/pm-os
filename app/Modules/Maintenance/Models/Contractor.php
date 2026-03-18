<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * نموذج المقاول / المورد
 */
class Contractor extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'name_en', 'company_name', 'commercial_reg',
        'phone', 'phone_2', 'email',
        'specializations', 'rating', 'completed_orders_count',
        'contract_type', 'contract_start', 'contract_end',
        'hourly_rate', 'iban', 'bank_name',
        'status', 'notes', 'metadata',
    ];

    protected $casts = [
        'specializations' => 'array',
        'metadata' => 'array',
        'rating' => 'decimal:1',
        'hourly_rate' => 'decimal:2',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'completed_orders_count' => 'integer',
    ];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function pmPlans(): HasMany
    {
        return $this->hasMany(PmPlan::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(\App\Core\Documents\Models\Document::class, 'documentable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySpecialization($query, string $spec)
    {
        return $query->whereJsonContains('specializations', $spec);
    }

    public function updateRating(): void
    {
        $avg = $this->workOrders()
            ->whereNotNull('resident_rating')
            ->avg('resident_rating');

        if ($avg) {
            $this->update([
                'rating' => round($avg, 1),
                'completed_orders_count' => $this->workOrders()->where('status', 'closed')->count(),
            ]);
        }
    }
}
