<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Models\Property;
use Modules\Foundation\Models\Unit;
use Modules\TenantManagement\Models\Resident;
use Modules\Leasing\Models\Lease;
use Modules\Collection\Models\Invoice;
use Modules\Maintenance\Models\WorkOrder;

class DashboardController extends Controller
{
    /**
     * لوحة المؤشرات الرئيسية
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'overview' => $this->getOverview(),
                'occupancy' => $this->getOccupancy(),
                'collection' => $this->getCollection(),
                'maintenance' => $this->getMaintenance(),
                'leases' => $this->getLeaseAlerts(),
            ],
        ]);
    }

    /**
     * إحصائيات سريعة
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->getOverview(),
        ]);
    }

    private function getOverview(): array
    {
        return [
            'total_properties' => Property::active()->count(),
            'total_units' => Unit::count(),
            'occupied_units' => Unit::where('status', 'occupied')->count(),
            'vacant_units' => Unit::where('status', 'vacant')->count(),
            'total_residents' => Resident::active()->count(),
            'active_leases' => Lease::active()->count(),
        ];
    }

    private function getOccupancy(): array
    {
        $total = Unit::count();
        $occupied = Unit::where('status', 'occupied')->count();

        return [
            'rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
            'by_property' => Property::active()
                ->withCount([
                    'units',
                    'units as occupied_units_count' => fn($q) => $q->where('status', 'occupied'),
                ])
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'total' => $p->units_count,
                    'occupied' => $p->occupied_units_count,
                    'rate' => $p->units_count > 0
                        ? round(($p->occupied_units_count / $p->units_count) * 100, 1)
                        : 0,
                ]),
        ];
    }

    private function getCollection(): array
    {
        $currentMonth = now()->format('Y-m');

        $totalDue = Invoice::whereIn('status', ['issued', 'sent', 'partially_paid', 'overdue'])
            ->sum('balance');

        $collectedThisMonth = Invoice::where('status', 'paid')
            ->whereMonth('paid_date', now()->month)
            ->whereYear('paid_date', now()->year)
            ->sum('total_amount');

        $overdueCount = Invoice::overdue()->count();
        $overdueAmount = Invoice::overdue()->sum('balance');

        return [
            'total_receivable' => (float) $totalDue,
            'collected_this_month' => (float) $collectedThisMonth,
            'overdue_count' => $overdueCount,
            'overdue_amount' => (float) $overdueAmount,
            'aging' => [
                'current' => (float) Invoice::pending()->where('due_date', '>=', now())->sum('balance'),
                '1_30' => (float) Invoice::overdue()->where('due_date', '>=', now()->subDays(30))->sum('balance'),
                '31_60' => (float) Invoice::overdue()->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)])->sum('balance'),
                '61_90' => (float) Invoice::overdue()->whereBetween('due_date', [now()->subDays(90), now()->subDays(61)])->sum('balance'),
                '90_plus' => (float) Invoice::overdue()->where('due_date', '<', now()->subDays(90))->sum('balance'),
            ],
        ];
    }

    private function getMaintenance(): array
    {
        return [
            'open_work_orders' => WorkOrder::open()->count(),
            'emergency' => WorkOrder::emergency()->whereNotIn('status', ['completed', 'verified', 'closed', 'cancelled'])->count(),
            'sla_breached' => WorkOrder::open()->where('sla_deadline', '<', now())->count(),
            'completed_this_month' => WorkOrder::whereIn('status', ['completed', 'verified', 'closed'])
                ->whereMonth('completed_at', now()->month)
                ->count(),
            'by_category' => WorkOrder::open()
                ->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'by_priority' => WorkOrder::open()
                ->selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
        ];
    }

    private function getLeaseAlerts(): array
    {
        return [
            'expiring_30_days' => Lease::expiring(30)->count(),
            'expiring_60_days' => Lease::expiring(60)->count(),
            'expiring_90_days' => Lease::expiring(90)->count(),
            'expired_not_renewed' => Lease::active()->where('end_date', '<', now())->count(),
            'upcoming_expirations' => Lease::expiring(30)
                ->with(['unit.property', 'resident'])
                ->orderBy('end_date')
                ->limit(10)
                ->get()
                ->map(fn($l) => [
                    'id' => $l->id,
                    'lease_number' => $l->lease_number,
                    'resident' => $l->resident->name,
                    'property' => $l->unit->property->name ?? null,
                    'unit' => $l->unit->unit_number,
                    'end_date' => $l->end_date->format('Y-m-d'),
                    'days_remaining' => $l->days_remaining,
                ]),
        ];
    }
}
