<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Foundation\Models\Property;

class CreatePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'uuid', 'exists:owners,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', Property::TYPES)],
            'sub_type' => ['nullable', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'total_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'year_built' => ['nullable', 'integer', 'min:1900'],
            'floors_count' => ['nullable', 'integer', 'min:0'],
            'parking_spots' => ['nullable', 'integer', 'min:0'],
            'amenities' => ['nullable', 'array'],
            'deed_number' => ['nullable', 'string'],
            'deed_date' => ['nullable', 'date'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_id.required' => 'يجب تحديد المالك',
            'name.required' => 'يجب إدخال اسم العقار',
            'type.required' => 'يجب تحديد نوع العقار',
            'address_line.required' => 'يجب إدخال العنوان',
            'city.required' => 'يجب تحديد المدينة',
        ];
    }
}
