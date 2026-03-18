<?php

declare(strict_types=1);

namespace Modules\Collection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج الفاتورة
 */
class Invoice extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'lease_id', 'resident_id', 'property_id', 'unit_id',
        'invoice_number', 'type',
        'subtotal', 'vat_rate', 'vat_amount', 'discount_amount', 'total_amount', 'paid_amount', 'balance',
        'currency', 'issued_date', 'due_date', 'paid_date',
        'status', 'sadad_number', 'sadad_status',
        'zatca_invoice_hash', 'zatca_qr_code', 'zatca_reported',
        'period_start', 'period_end',
        'description', 'notes', 'line_items', 'metadata',
    ];

    protected $casts = [
        'line_items' => 'array',
        'metadata' => 'array',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'issued_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'zatca_reported' => 'boolean',
    ];

    // ─── أنواع الفواتير ──────────────────────────────
    const TYPE_RENT = 'rent';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_SERVICE_CHARGE = 'service_charge';
    const TYPE_PENALTY = 'penalty';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_PARKING = 'parking';
    const TYPE_OTHER = 'other';

    // ─── الحالات ─────────────────────────────────────
    const STATUS_DRAFT = 'draft';
    const STATUS_ISSUED = 'issued';
    const STATUS_SENT = 'sent';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WRITTEN_OFF = 'written_off';

    // ─── Relationships ───────────────────────────────

    public function lease(): BelongsTo
    {
        return $this->belongsTo(\Modules\Leasing\Models\Lease::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(\Modules\TenantManagement\Models\Resident::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Unit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'confirmed');
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

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [self::STATUS_ISSUED, self::STATUS_SENT, self::STATUS_PARTIALLY_PAID])
            ->where('due_date', '<', now());
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ISSUED, self::STATUS_SENT, self::STATUS_PARTIALLY_PAID, self::STATUS_OVERDUE,
        ]);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period_start', '<=', $period)
            ->where('period_end', '>=', $period);
    }

    // ─── Computed ────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_SENT, self::STATUS_PARTIALLY_PAID])
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return (int) $this->due_date->diffInDays(now());
    }

    public function getAgingBucketAttribute(): string
    {
        $days = $this->days_overdue;
        if ($days === 0) return 'current';
        if ($days <= 30) return '1-30';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        return '90+';
    }

    // ─── Business Logic ──────────────────────────────

    public function recordPayment(float $amount): void
    {
        $this->paid_amount = (float) $this->paid_amount + $amount;
        $this->balance = (float) $this->total_amount - (float) $this->paid_amount;

        if ($this->balance <= 0) {
            $this->status = self::STATUS_PAID;
            $this->paid_date = now();
            $this->balance = 0;
        } elseif ((float) $this->paid_amount > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }

        $this->save();
    }

    public function calculateVat(): void
    {
        $this->vat_amount = round((float) $this->subtotal * ((float) $this->vat_rate / 100), 2);
        $this->total_amount = (float) $this->subtotal + (float) $this->vat_amount - (float) $this->discount_amount;
        $this->balance = (float) $this->total_amount - (float) $this->paid_amount;
    }

    // ─── Helpers ─────────────────────────────────────

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $last = static::withTrashed()
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $number = $last ? (int) substr($last, -5) + 1 : 1;
        return "INV-{$year}-" . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} الفاتورة: {$this->invoice_number}");
    }
}
