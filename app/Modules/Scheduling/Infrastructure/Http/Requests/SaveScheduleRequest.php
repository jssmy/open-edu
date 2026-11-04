<?php

namespace App\Modules\Scheduling\Infrastructure\Http\Requests;

use App\Models\User;
use App\Modules\Scheduling\Domain\Modality;
use App\Modules\Scheduling\Domain\OfferingStatus;
use App\Modules\Scheduling\Domain\SessionStatus;
use App\Modules\Scheduling\Infrastructure\Models\Classroom;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('scheduling.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $teacherIds = $this->input('teacher_ids');
        if ($this->filled('teacher_id')) {
            $teacherIds = [(int) $this->input('teacher_id')];
        } elseif (! is_array($teacherIds)) {
            $teacherIds = [];
        }

        $sessions = $this->input('sessions');
        if (! is_array($sessions)) {
            $sessions = [];
        }

        $defaultClassroomId = $this->input('classroom_id');
        $defaultTeacherId = is_array($teacherIds) && isset($teacherIds[0]) ? (int) $teacherIds[0] : null;

        $normalizedSessions = array_map(function ($session) use ($defaultClassroomId, $defaultTeacherId): array {
            $row = is_array($session) ? $session : [];

            return [
                'title' => $row['title'] ?? 'Sesión de clase',
                'session_date' => $row['session_date'] ?? null,
                'starts_at' => $row['starts_at'] ?? null,
                'ends_at' => $row['ends_at'] ?? null,
                'teacher_id' => $defaultTeacherId ?? ($row['teacher_id'] ?? null),
                'classroom_id' => array_key_exists('classroom_id', $row) ? $row['classroom_id'] : $defaultClassroomId,
                'meeting_url' => $row['meeting_url'] ?? null,
                'status' => $row['status'] ?? SessionStatus::Scheduled->value,
            ];
        }, $sessions);

        $offeringParam = $this->route('schedule') ?? $this->route('offering');
        $existingOffering = $offeringParam instanceof CourseOffering
            ? $offeringParam
            : (is_numeric($offeringParam) ? CourseOffering::query()->find((int) $offeringParam) : null);

        $rawGroupCode = trim((string) $this->input('group_code', ''));
        if ($rawGroupCode === '') {
            if ($existingOffering !== null && $existingOffering->group_code !== '' && ! str_starts_with($existingOffering->group_code, 'PEND-')) {
                $rawGroupCode = $existingOffering->group_code;
            } else {
                $rawGroupCode = CourseOffering::nextGroupCodeForCourse($this->integer('course_id'), $existingOffering?->id);
            }
        }

        $this->merge([
            'group_code' => $rawGroupCode,
            'starts_on' => $this->input('starts_on', now()->toDateString()),
            'ends_on' => $this->input('ends_on', now()->addMonths(3)->toDateString()),
            'max_students' => $this->input('max_students', 30),
            'status' => $this->input('status', OfferingStatus::Published->value),
            'teacher_ids' => is_array($teacherIds) ? array_values(array_unique(array_map('intval', $teacherIds))) : [],
            'sessions' => $normalizedSessions,
        ]);
    }

    public function rules(): array
    {
        $offeringParam = $this->route('schedule') ?? $this->route('offering');
        $offeringId = $offeringParam instanceof CourseOffering ? $offeringParam->id : (is_numeric($offeringParam) ? (int) $offeringParam : null);

        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'group_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('course_offerings', 'group_code')
                    ->where(fn ($q) => $q->where('course_id', $this->integer('course_id')))
                    ->ignore($offeringId),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'max_students' => ['required', 'integer', 'min:1', 'max:5000'],
            'modality' => ['required', Rule::enum(Modality::class)],
            'status' => ['required', Rule::enum(OfferingStatus::class)],
            'teacher_ids' => ['required', 'array', 'size:1'],
            'teacher_ids.*' => ['required', 'integer', 'exists:users,id'],
            'sessions' => ['required', 'array', 'min:1'],
            'sessions.*.title' => ['required', 'string', 'max:160'],
            'sessions.*.session_date' => ['required', 'date'],
            'sessions.*.starts_at' => ['required', 'date_format:H:i'],
            'sessions.*.ends_at' => ['required', 'date_format:H:i'],
            'sessions.*.teacher_id' => ['required', 'integer', 'exists:users,id'],
            'sessions.*.classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'sessions.*.meeting_url' => ['nullable', 'string', 'max:500'],
            'sessions.*.status' => ['required', Rule::enum(SessionStatus::class)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var list<int> $teacherIds */
                $teacherIds = $this->input('teacher_ids', []);
                foreach ($teacherIds as $teacherId) {
                    $teacher = User::query()->find((int) $teacherId);
                    if ($teacher === null || ! $teacher->hasRole('docente') || ! $teacher->is_active) {
                        $validator->errors()->add('teacher_id', 'Debe seleccionar docentes activos con rol Docente.');
                        break;
                    }
                }

                $modality = $this->string('modality')->toString();
                /** @var list<array<string, mixed>> $sessions */
                $sessions = $this->input('sessions', []);

                foreach ($sessions as $session) {
                    if (! in_array((int) ($session['teacher_id'] ?? 0), $teacherIds, true)) {
                        $validator->errors()->add('teacher_id', 'El docente responsable de la sesión debe pertenecer a los docentes habilitados del grupo.');
                        break;
                    }

                    if ($modality === Modality::InPerson->value) {
                        $classroomId = $session['classroom_id'] ?? null;
                        if ($classroomId === null || $classroomId === '') {
                            $validator->errors()->add('classroom_id', 'Debe seleccionar un aula para una clase presencial.');
                            break;
                        }
                        if (! Classroom::query()->whereKey((int) $classroomId)->where('is_active', true)->exists()) {
                            $validator->errors()->add('classroom_id', 'El aula seleccionada no está activa.');
                            break;
                        }
                    }
                }
            },
        ];
    }
}
