<?php

namespace App\Modules\Enrollments\Infrastructure\Http\Requests;

use App\Modules\Enrollments\Domain\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEnrollmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('enrollments.manage');
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(EnrollmentStatus::class)]];
    }
}
