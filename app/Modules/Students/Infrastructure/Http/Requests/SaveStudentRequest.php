<?php

namespace App\Modules\Students\Infrastructure\Http\Requests;

use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('students.manage');
    }

    public function rules(): array
    {
        $studentId = $this->route('studentId');
        $userId = is_numeric($studentId)
            ? Student::query()->whereKey((int) $studentId)->value('user_id')
            : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'temporary_password' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                Password::min(8),
            ],
            'identifier' => [
                'nullable', 'string', 'max:255',
                Rule::unique('students', 'identifier')->ignore($studentId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
