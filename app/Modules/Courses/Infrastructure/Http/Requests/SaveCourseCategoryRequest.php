<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Modules\Courses\Infrastructure\Models\CourseCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SaveCourseCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merged = [];

        if (is_string($this->input('name'))) {
            $merged['name'] = trim($this->input('name'));
        }

        $roleName = $this->input('role_name');
        $merged['role_name'] = is_string($roleName) && trim($roleName) !== ''
            ? trim($roleName)
            : 'estudiante';

        $this->merge($merged);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('courses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $slug = Str::slug($value);
                    $exists = CourseCategory::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($value))])
                        ->orWhere('slug', $slug)
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe una categoría con ese nombre.');
                    }
                },
            ],
            'role_name' => ['required', 'string', 'max:64', 'exists:roles,name'],
        ];
    }
}
