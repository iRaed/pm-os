<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Foundation\Models\Property;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Property::class);
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'uuid', 'exists:owners,id'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . implode(',', Property::TYPES)],
            'sub_type' => ['nullable', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'additional_number' => ['nullable', 'string', 'max:10'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'total_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'land_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 5)],
            'floors_count' => ['nullable', 'integer', 'min:0', 'max:200'],
            'parking_spots' => ['nullable', 'integer', 'min:0'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:100'],
            'deed_number' => ['nullable', 'string', 'max:50'],
            'deed_date' => ['nullable', 'date'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
            'insurance_value' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_id.required' => 'يجب تحديد المالك',
            'owner_id.exists' => 'المالك غير موجود',
            'name.required' => 'يجب إدخال اسم العقار',
            'type.required' => 'يجب تحديد نوع العقار',
            'type.in' => 'نوع العقار غير صحيح',
            'address_line.required' => 'يجب إدخال العنوان',
            'city.required' => 'يجب تحديد المدينة',
        ];
    }
}
