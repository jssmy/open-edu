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
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'identifier' => [
                'required', 'string', 'max:50',
                Rule::unique('students', 'identifier')->ignore($studentId),
            ],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'temporary_password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                Password::min(8),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
