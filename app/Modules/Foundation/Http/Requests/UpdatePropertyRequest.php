<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Requests;

use Modules\Foundation\Models\Property;

class UpdatePropertyRequest extends StorePropertyRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        $rules = parent::rules();

        // عند التحديث، الحقول المطلوبة تصبح اختيارية
        foreach (['owner_id', 'name', 'type', 'address_line', 'city'] as $field) {
            if (isset($rules[$field])) {
                $rules[$field] = array_map(
                    fn($rule) => $rule === 'required' ? 'sometimes' : $rule,
                    $rules[$field]
                );
            }
        }

        // إضافة حقول خاصة بالتحديث
        $rules['status'] = ['sometimes', 'string', 'in:onboarding,active,suspended,archived'];
        $rules['risk_level'] = ['sometimes', 'string', 'in:low,medium,high,critical'];

        return $rules;
    }
}
