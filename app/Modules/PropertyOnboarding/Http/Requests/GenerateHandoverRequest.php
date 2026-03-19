<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateHandoverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'signed_by_company' => ['required', 'string', 'max:255'],
            'signed_by_owner' => ['required', 'string', 'max:255'],
            'signed_at' => ['nullable', 'date'],
            'witnesses' => ['nullable', 'array'],
            'witnesses.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'signed_by_company.required' => 'يجب إدخال اسم ممثل الشركة',
            'signed_by_owner.required' => 'يجب إدخال اسم المالك أو ممثله',
        ];
    }
}
