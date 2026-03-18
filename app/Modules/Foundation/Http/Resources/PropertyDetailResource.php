<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'type' => $this->type,
            'sub_type' => $this->sub_type,
            'status' => $this->status,

            'location' => [
                'address_line' => $this->address_line,
                'city' => $this->city,
                'district' => $this->district,
                'postal_code' => $this->postal_code,
                'additional_number' => $this->additional_number,
                'lat' => $this->lat,
                'lng' => $this->lng,
            ],

            'specs' => [
                'total_units' => $this->total_units,
                'total_area_sqm' => $this->total_area_sqm,
                'land_area_sqm' => $this->land_area_sqm,
                'year_built' => $this->year_built,
                'floors_count' => $this->floors_count,
                'parking_spots' => $this->parking_spots,
                'amenities' => $this->amenities,
            ],

            'deed' => [
                'number' => $this->deed_number,
                'date' => $this->deed_date?->format('Y-m-d'),
            ],

            'valuation' => [
                'market_value' => $this->market_value,
                'insurance_value' => $this->insurance_value,
                'risk_level' => $this->risk_level,
            ],

            'operations' => [
                'onboarding_date' => $this->onboarding_date?->format('Y-m-d'),
                'operation_start_date' => $this->operation_start_date?->format('Y-m-d'),
                'occupancy_rate' => $this->occupancy_rate,
                'vacant_units' => $this->vacant_units_count,
                'monthly_revenue' => $this->monthly_revenue,
            ],

            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'phone' => $this->owner->phone,
                'management_fee_pct' => $this->owner->management_fee_pct,
            ]),

            'manager' => $this->whenLoaded('manager', fn() => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ]),

            'units_summary' => $this->whenLoaded('units', function () {
                $units = $this->units;
                return [
                    'total' => $units->count(),
                    'by_status' => $units->groupBy('status')->map->count(),
                    'by_type' => $units->groupBy('type')->map->count(),
                ];
            }),

            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
