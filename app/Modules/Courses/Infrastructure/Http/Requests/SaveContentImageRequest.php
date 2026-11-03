<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SaveContentImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        if (! $course instanceof Course || $this->user() === null) {
            return false;
        }

        Gate::forUser($this->user())->authorize('manageContent', $course);

        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}
