<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'city' => $this->city,
            'district' => $this->district,
            'total_units' => $this->total_units,
            'occupancy_rate' => $this->occupancy_rate,
            'vacant_units' => $this->vacant_units_count,
            'risk_level' => $this->risk_level,
            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'manager' => $this->whenLoaded('manager', fn() => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'residential_compound' => 'مجمع سكني',
            'commercial_building' => 'مبنى تجاري',
            'tower' => 'برج',
            'villa' => 'فيلا',
            'mixed_use' => 'متعدد الاستخدام',
            'land' => 'أرض',
            'warehouse' => 'مستودع',
            'mall' => 'مول تجاري',
            'office_building' => 'مبنى مكاتب',
            default => $this->type,
        };
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'onboarding' => 'قيد التهيئة',
            'active' => 'نشط',
            'suspended' => 'موقوف',
            'archived' => 'مؤرشف',
            default => $this->status,
        };
    }
}
