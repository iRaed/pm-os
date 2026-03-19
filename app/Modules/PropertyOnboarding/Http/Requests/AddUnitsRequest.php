<?php
// ═══ AddUnitsRequest ═══
namespace Modules\PropertyOnboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddUnitsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'units' => ['required', 'array', 'min:1'],
            'units.*.unit_number' => ['required', 'string', 'max:20'],
            'units.*.floor' => ['nullable', 'integer', 'min:0'],
            'units.*.type' => ['required', 'in:apartment,office,shop,studio,villa,warehouse,parking,storage,other'],
            'units.*.status' => ['nullable', 'in:vacant,not_available'],
            'units.*.area_sqm' => ['nullable', 'numeric', 'min:0'],
            'units.*.rooms' => ['nullable', 'integer', 'min:0'],
            'units.*.bathrooms' => ['nullable', 'integer', 'min:0'],
            'units.*.base_rent' => ['nullable', 'numeric', 'min:0'],
            'units.*.features' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'units.required' => 'يجب إضافة وحدة واحدة على الأقل',
            'units.*.unit_number.required' => 'يجب إدخال رقم الوحدة',
            'units.*.type.required' => 'يجب تحديد نوع الوحدة',
        ];
    }
}
