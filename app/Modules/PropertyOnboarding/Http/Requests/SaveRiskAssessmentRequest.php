<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveRiskAssessmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'risks' => ['required', 'array', 'min:1'],
            'risks.*.category' => ['required', 'in:operational,financial,legal,safety,tenant,vendor,market,compliance'],
            'risks.*.title' => ['required', 'string', 'max:255'],
            'risks.*.description' => ['nullable', 'string', 'max:2000'],
            'risks.*.likelihood' => ['required', 'in:rare,unlikely,possible,likely,certain'],
            'risks.*.impact' => ['required', 'in:negligible,minor,moderate,major,catastrophic'],
            'risks.*.mitigation_plan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
