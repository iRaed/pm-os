<?php

declare(strict_types=1);

namespace Modules\Collection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج الدفعة / السداد
 */
class Payment extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'invoice_id', 'resident_id', 'collected_by',
        'amount', 'currency', 'payment_date', 'method',
        'reference_number', 'cheque_number', 'cheque_date', 'bank_name',
        'status', 'receipt_number', 'receipt_path',
        'notes', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'cheque_date' => 'date',
    ];

    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_SADAD = 'sadad';
    const METHOD_MADA = 'mada';
    const METHOD_CHEQUE = 'cheque';
    const METHOD_ONLINE = 'online';

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    // ─── Relationships ───────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(\Modules\TenantManagement\Models\Resident::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\User::class, 'collected_by');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} دفعة: {$this->amount} ر.س");
    }
}
