<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('content.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['required', 'integer', 'distinct', 'exists:units,id'],
        ];
    }
}
