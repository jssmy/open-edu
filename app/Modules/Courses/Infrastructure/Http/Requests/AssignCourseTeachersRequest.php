<?php

namespace App\Modules\Courses\Infrastructure\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignCourseTeachersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courses.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'teachers' => ['present', 'array'],
            'teachers.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $teacherIds = $this->input('teachers');
                $teacherCount = User::role('docente')->whereIn('id', $teacherIds)->count();

                if ($teacherCount !== count($teacherIds)) {
                    $validator->errors()->add('teachers', 'Solo se pueden asignar usuarios con rol Docente.');
                }
            },
        ];
    }
}
