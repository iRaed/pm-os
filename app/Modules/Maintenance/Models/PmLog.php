<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'pm_plan_id', 'work_order_id',
        'scheduled_date', 'executed_date', 'executed_by_type', 'executed_by_id',
        'checklist_results', 'result', 'findings', 'recommendations',
        'photos', 'cost',
    ];

    protected $casts = [
        'checklist_results' => 'array',
        'photos' => 'array',
        'cost' => 'decimal:2',
        'scheduled_date' => 'date',
        'executed_date' => 'date',
    ];

    public function pmPlan(): BelongsTo
    {
        return $this->belongsTo(PmPlan::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
