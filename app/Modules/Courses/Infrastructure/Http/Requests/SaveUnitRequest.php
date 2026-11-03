<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('content.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
        ];
    }
}
