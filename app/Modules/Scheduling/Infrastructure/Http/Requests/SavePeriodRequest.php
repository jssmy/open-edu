<?php

namespace App\Modules\Scheduling\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scheduling.manage') ?? false;
    }

    public function rules(): array
    {
        $periodId = $this->route('period')?->id ?? $this->route('period');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('academic_periods', 'name')->ignore($periodId)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ];
    }
}

