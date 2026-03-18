<?php

declare(strict_types=1);

namespace Modules\Collection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج جدول الدفعات المجدولة
 */
class PaymentSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'lease_id', 'resident_id',
        'installment_number', 'amount', 'due_date',
        'status', 'invoice_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'installment_number' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_INVOICED = 'invoiced';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';

    public function lease(): BelongsTo
    {
        return $this->belongsTo(\Modules\Leasing\Models\Lease::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(\Modules\TenantManagement\Models\Resident::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }
}
