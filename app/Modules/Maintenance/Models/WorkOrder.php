<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج أمر العمل (الصيانة)
 */
class WorkOrder extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'property_id', 'unit_id',
        'reported_by_type', 'reported_by_id',
        'wo_number', 'title', 'description',
        'category', 'sub_category', 'priority', 'status',
        'assigned_to_type', 'assigned_to_id', 'contractor_id',
        'estimated_cost', 'actual_cost', 'parts_cost', 'labor_cost',
        'estimated_hours', 'actual_hours',
        'scheduled_date', 'started_at', 'completed_at', 'verified_at', 'closed_at',
        'sla_deadline', 'sla_breached',
        'before_photos', 'after_photos',
        'resident_rating', 'resident_feedback',
        'verified_by', 'closed_by', 'resolution_notes',
        'pm_plan_id', 'is_preventive',
        'checklist', 'metadata',
    ];

    protected $casts = [
        'before_photos' => 'array',
        'after_photos' => 'array',
        'checklist' => 'array',
        'metadata' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'sla_breached' => 'boolean',
        'is_preventive' => 'boolean',
        'resident_rating' => 'integer',
    ];

    // ─── التصنيفات ───────────────────────────────────
    const CATEGORY_PLUMBING = 'plumbing';
    const CATEGORY_ELECTRICAL = 'electrical';
    const CATEGORY_HVAC = 'hvac';
    const CATEGORY_STRUCTURAL = 'structural';
    const CATEGORY_PAINTING = 'painting';
    const CATEGORY_APPLIANCE = 'appliance';
    const CATEGORY_PEST_CONTROL = 'pest_control';
    const CATEGORY_ELEVATOR = 'elevator';
    const CATEGORY_FIRE_SYSTEM = 'fire_system';
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_OTHER = 'other';

    // ─── الأولوية ────────────────────────────────────
    const PRIORITY_EMERGENCY = 'emergency';  // 4 ساعات
    const PRIORITY_HIGH = 'high';            // 24 ساعة
    const PRIORITY_MEDIUM = 'medium';        // 72 ساعة
    const PRIORITY_LOW = 'low';              // 7 أيام

    // ─── الحالات ─────────────────────────────────────
    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_PENDING_PARTS = 'pending_parts';
    const STATUS_COMPLETED = 'completed';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * SLA بالساعات حسب الأولوية
     */
    const SLA_HOURS = [
        self::PRIORITY_EMERGENCY => 4,
        self::PRIORITY_HIGH => 24,
        self::PRIORITY_MEDIUM => 72,
        self::PRIORITY_LOW => 168,
    ];

    // ─── Relationships ───────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Unit::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function pmPlan(): BelongsTo
    {
        return $this->belongsTo(PmPlan::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\User::class, 'verified_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\User::class, 'closed_by');
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

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_OPEN,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_ON_HOLD,
            self::STATUS_PENDING_PARTS,
        ]);
    }

    public function scopeEmergency($query)
    {
        return $query->where('priority', self::PRIORITY_EMERGENCY);
    }

    public function scopeOverdue($query)
    {
        return $query->open()
            ->where('sla_deadline', '<', now())
            ->where('sla_breached', false);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ─── Computed ────────────────────────────────────

    public function getTotalCostAttribute(): float
    {
        return (float) ($this->actual_cost ?? $this->estimated_cost ?? 0);
    }

    public function getIsSlaBreachedAttribute(): bool
    {
        if (!$this->sla_deadline) return false;
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_VERIFIED, self::STATUS_CLOSED])) {
            return $this->completed_at && $this->completed_at->isAfter($this->sla_deadline);
        }
        return now()->isAfter($this->sla_deadline);
    }

    public function getResolutionTimeHoursAttribute(): ?float
    {
        if (!$this->completed_at) return null;
        return round($this->created_at->diffInHours($this->completed_at), 1);
    }

    // ─── Helpers ─────────────────────────────────────

    public static function generateWoNumber(): string
    {
        $year = now()->format('Y');
        $last = static::withTrashed()
            ->where('wo_number', 'like', "WO-{$year}-%")
            ->orderByDesc('wo_number')
            ->value('wo_number');

        $number = $last ? (int) substr($last, -5) + 1 : 1;
        return "WO-{$year}-" . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    public function calculateSlaDeadline(): \Carbon\Carbon
    {
        $hours = self::SLA_HOURS[$this->priority] ?? 72;
        return $this->created_at->addHours($hours);
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} أمر العمل: {$this->wo_number}");
    }
}
