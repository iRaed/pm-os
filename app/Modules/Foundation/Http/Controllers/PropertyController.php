<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Requests\StorePropertyRequest;
use Modules\Foundation\Http\Requests\UpdatePropertyRequest;
use Modules\Foundation\Http\Resources\PropertyResource;
use Modules\Foundation\Http\Resources\PropertyDetailResource;
use Modules\Foundation\Models\Property;
use Modules\Foundation\Models\Unit;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PropertyController extends Controller
{
    /**
     * عرض قائمة العقارات مع فلترة وترتيب
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Property::class);

        $properties = QueryBuilder::for(Property::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('city'),
                AllowedFilter::exact('district'),
                AllowedFilter::exact('owner_id'),
                AllowedFilter::exact('manager_id'),
                AllowedFilter::exact('risk_level'),
                AllowedFilter::scope('search', 'whereSearch'),
            ])
            ->allowedSorts(['name', 'created_at', 'total_units', 'city', 'status'])
            ->allowedIncludes(['owner', 'manager', 'units'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    /**
     * إنشاء عقار جديد
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $this->authorize('create', Property::class);

        $data = $request->validated();
        $data['code'] = Property::generateCode();
        $data['status'] = Property::STATUS_ONBOARDING;

        $property = Property::create($data);

        return response()->json([
            'message' => 'تم إنشاء العقار بنجاح',
            'data' => new PropertyDetailResource($property->load('owner')),
        ], 201);
    }

    /**
     * عرض تفاصيل عقار
     */
    public function show(Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        $property->load(['owner', 'manager', 'units']);

        return response()->json([
            'data' => new PropertyDetailResource($property),
        ]);
    }

    /**
     * تحديث بيانات عقار
     */
    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $property->update($request->validated());

        return response()->json([
            'message' => 'تم تحديث العقار بنجاح',
            'data' => new PropertyDetailResource($property->fresh(['owner', 'manager'])),
        ]);
    }

    /**
     * حذف عقار (Soft Delete)
     */
    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);

        // التحقق من عدم وجود عقود نشطة
        $activeLeases = $property->units()
            ->whereHas('activeLease')
            ->count();

        if ($activeLeases > 0) {
            return response()->json([
                'message' => "لا يمكن حذف العقار — يوجد {$activeLeases} عقد نشط",
            ], 422);
        }

        $property->delete();

        return response()->json([
            'message' => 'تم حذف العقار بنجاح',
        ]);
    }

    /**
     * وحدات العقار
     */
    public function units(Property $property, Request $request): JsonResponse
    {
        $this->authorize('view', $property);

        $units = QueryBuilder::for(Unit::where('property_id', $property->id))
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('floor'),
            ])
            ->allowedSorts(['unit_number', 'floor', 'base_rent', 'status', 'area_sqm'])
            ->defaultSort('unit_number')
            ->allowedIncludes(['activeLease', 'activeLease.resident'])
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $units,
        ]);
    }

    /**
     * إحصائيات العقار
     */
    public function stats(Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        $totalUnits = $property->units()->count();
        $occupied = $property->units()->where('status', 'occupied')->count();
        $vacant = $property->units()->where('status', 'vacant')->count();
        $underMaintenance = $property->units()->where('status', 'under_maintenance')->count();

        $openWorkOrders = $property->workOrders()
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->count();

        $emergencyWorkOrders = $property->workOrders()
            ->where('priority', 'emergency')
            ->whereNotIn('status', ['completed', 'verified', 'closed', 'cancelled'])
            ->count();

        return response()->json([
            'data' => [
                'units' => [
                    'total' => $totalUnits,
                    'occupied' => $occupied,
                    'vacant' => $vacant,
                    'under_maintenance' => $underMaintenance,
                    'occupancy_rate' => $totalUnits > 0 ? round(($occupied / $totalUnits) * 100, 1) : 0,
                ],
                'maintenance' => [
                    'open_work_orders' => $openWorkOrders,
                    'emergency' => $emergencyWorkOrders,
                ],
                'financial' => [
                    'monthly_revenue' => $property->monthly_revenue,
                    'annual_revenue_potential' => $property->monthly_revenue * 12,
                ],
            ],
        ]);
    }

    /**
     * تقرير الإشغال
     */
    public function occupancy(Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        $units = $property->units()
            ->with(['activeLease.resident'])
            ->orderBy('unit_number')
            ->get()
            ->map(fn($unit) => [
                'id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'floor' => $unit->floor,
                'type' => $unit->type,
                'status' => $unit->status,
                'area_sqm' => $unit->area_sqm,
                'base_rent' => $unit->base_rent,
                'resident' => $unit->activeLease?->resident?->name,
                'lease_end' => $unit->activeLease?->end_date?->format('Y-m-d'),
                'vacancy_days' => $unit->vacancy_days,
            ]);

        return response()->json(['data' => $units]);
    }

    /**
     * ملخص مالي للعقار
     */
    public function financialSummary(Property $property, Request $request): JsonResponse
    {
        $this->authorize('view', $property);

        $period = $request->get('period', now()->format('Y-m'));

        $revenue = $property->ledgerEntries()
            ->where('account_type', 'revenue')
            ->where('period', $period)
            ->sum('credit');

        $expenses = $property->ledgerEntries()
            ->where('account_type', 'expense')
            ->where('period', $period)
            ->sum('debit');

        return response()->json([
            'data' => [
                'period' => $period,
                'revenue' => (float) $revenue,
                'expenses' => (float) $expenses,
                'net_operating_income' => (float) $revenue - (float) $expenses,
            ],
        ]);
    }

    /**
     * إكمال تهيئة العقار
     */
    public function completeOnboarding(Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        if ($property->status !== Property::STATUS_ONBOARDING) {
            return response()->json([
                'message' => 'العقار ليس في مرحلة التهيئة',
            ], 422);
        }

        // التحقق من المتطلبات الأساسية
        $checks = [];

        if ($property->units()->count() === 0) {
            $checks[] = 'يجب إضافة وحدة واحدة على الأقل';
        }

        if (!$property->documents()->where('category', 'deed')->exists()) {
            $checks[] = 'يجب رفع صورة الصك';
        }

        if (!empty($checks)) {
            return response()->json([
                'message' => 'لا يمكن إكمال التهيئة — متطلبات ناقصة',
                'missing' => $checks,
            ], 422);
        }

        $property->update([
            'status' => Property::STATUS_ACTIVE,
            'operation_start_date' => now(),
        ]);

        return response()->json([
            'message' => 'تم تفعيل العقار بنجاح',
            'data' => new PropertyDetailResource($property->fresh()),
        ]);
    }
}
