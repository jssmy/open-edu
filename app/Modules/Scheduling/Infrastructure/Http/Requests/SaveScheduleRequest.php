<?php

namespace App\Modules\Scheduling\Infrastructure\Http\Requests;

use App\Models\User;
use App\Modules\Scheduling\Domain\Modality;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scheduling.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'modality' => ['required', Rule::enum(Modality::class)],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'blocks' => ['required', 'array', 'min:1'],
            'blocks.*.day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'blocks.*.starts_at' => ['required', 'date_format:H:i'],
            'blocks.*.ends_at' => ['required', 'date_format:H:i'],
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

                if ($this->string('modality')->toString() === Modality::InPerson->value) {
                    $classroomId = $this->input('classroom_id');
                    if ($classroomId === null) {
                        $validator->errors()->add('classroom_id', 'Debe seleccionar un aula para una clase presencial.');
                    } elseif (! Classroom::query()->whereKey($classroomId)->where('is_active', true)->exists()) {
                        $validator->errors()->add('classroom_id', 'El aula seleccionada no está activa.');
                    }
                }
            },
        ];
    }
}

