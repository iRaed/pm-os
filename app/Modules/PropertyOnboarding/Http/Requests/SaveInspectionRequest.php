<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveInspectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checklist' => ['required', 'array', 'min:1'],
            'checklist.*.id' => ['required', 'string'],
            'checklist.*.status' => ['required', 'in:pass,fail,na'],
            'checklist.*.notes' => ['nullable', 'string', 'max:500'],
            'results' => ['nullable', 'array'],
            'overall_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['string'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'checklist.required' => 'يجب تعبئة قائمة الفحص',
            'overall_rating.required' => 'يجب تحديد التقييم العام',
        ];
    }
}
