<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'objectives' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
