<?php

namespace App\Modules\Enrollments\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('enrollments.manage');
    }

    public function rules(): array
    {
        return ['student_id' => ['required', 'integer', 'exists:students,id']];
    }
}
