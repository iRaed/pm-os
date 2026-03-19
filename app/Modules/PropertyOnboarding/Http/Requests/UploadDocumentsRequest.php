<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'categories' => ['required', 'array'],
            'categories.*' => ['string', 'in:deed,license,contract,identity,inspection_report,insurance,financial,legal,photo,floor_plan,other'],
            'titles' => ['nullable', 'array'],
            'titles.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'documents.required' => 'يجب رفع ملف واحد على الأقل',
            'documents.*.max' => 'الحد الأقصى لحجم الملف 20 ميقا',
        ];
    }
}
