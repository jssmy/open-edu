<?php

namespace App\Modules\Scheduling\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scheduling.manage') ?? false;
    }

    public function rules(): array
    {
        $classroomId = $this->route('classroom')?->id ?? $this->route('classroom');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('classrooms', 'name')->ignore($classroomId)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

