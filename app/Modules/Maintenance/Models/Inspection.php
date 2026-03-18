<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inspection extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'property_id', 'unit_id', 'inspector_id',
        'type', 'status', 'scheduled_date', 'completed_date',
        'checklist', 'results', 'overall_rating', 'photos',
        'findings', 'recommendations', 'generated_work_orders', 'metadata',
    ];

    protected $casts = [
        'checklist' => 'array',
        'results' => 'array',
        'photos' => 'array',
        'generated_work_orders' => 'array',
        'metadata' => 'array',
        'overall_rating' => 'decimal:1',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    const TYPE_ONBOARDING = 'onboarding';
    const TYPE_PERIODIC = 'periodic';
    const TYPE_MOVE_IN = 'move_in';
    const TYPE_MOVE_OUT = 'move_out';
    const TYPE_SAFETY = 'safety';
    const TYPE_COMPLAINT = 'complaint';
    const TYPE_PRE_LEASE = 'pre_lease';

    public function property(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Unit::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\User::class, 'inspector_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(\App\Core\Documents\Models\Document::class, 'documentable');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }
}
