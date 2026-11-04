<?php

namespace App\Modules\Scheduling\Infrastructure\Http\Requests;

use App\Models\User;
use App\Modules\Scheduling\Domain\SessionStatus;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scheduling.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->input('title', 'Sesión de clase'),
            'status' => $this->input('status', SessionStatus::Scheduled->value),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'session_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'meeting_url' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(SessionStatus::class)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $teacher = User::query()->find($this->integer('teacher_id'));
                if ($teacher === null || ! $teacher->hasRole('docente') || ! $teacher->is_active) {
                    $validator->errors()->add('teacher_id', 'Debe seleccionar un docente activo con rol Docente.');
                }

                $classroomId = $this->input('classroom_id');
                if ($classroomId !== null && $classroomId !== '' && ! Classroom::query()->whereKey((int) $classroomId)->where('is_active', true)->exists()) {
                    $validator->errors()->add('classroom_id', 'El aula seleccionada no está activa.');
                }
            },
        ];
    }
}
