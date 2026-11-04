<?php

namespace App\Modules\Scheduling\Domain;

/** Representa una programación académica (edición/grupo) de un curso con sus docentes y sesiones. */
final readonly class CourseOffering
{
    /**
     * @param  list<int>  $teacherIds
     * @param  list<CourseSession>  $sessions
     */
    public function __construct(
        public ?int $id,
        public int $courseId,
        public string $groupCode,
        public string $startsOn,
        public string $endsOn,
        public int $maxStudents,
        public Modality $modality,
        public OfferingStatus $status,
        public array $teacherIds,
        public array $sessions,
    ) {
        if (trim($this->groupCode) === '') {
            throw new SchedulingRuleViolation('group_code', 'El nombre o código del grupo es obligatorio.');
        }

        if ($this->endsOn < $this->startsOn) {
            throw new SchedulingRuleViolation('ends_on', 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.');
        }

        if ($this->maxStudents < 1) {
            throw new SchedulingRuleViolation('max_students', 'La capacidad máxima de estudiantes debe ser al menos 1.');
        }

        if (count($this->teacherIds) !== 1) {
            throw new SchedulingRuleViolation('teacher_id', 'Cada grupo debe asignarse a un único docente.');
        }

        foreach ($this->sessions as $session) {
            if (! in_array($session->teacherId, $this->teacherIds, true)) {
                throw new SchedulingRuleViolation('sessions', 'El docente responsable de cada sesión debe ser el docente asignado al grupo.');
            }

            if ($this->modality === Modality::InPerson && $session->classroomId === null) {
                throw new SchedulingRuleViolation('classroom_id', 'Debe seleccionar un aula para una sesión presencial.');
            }
        }
    }

    public function effectiveStatus(?string $today = null): OfferingStatus
    {
        return $this->status->resolveForDates($this->startsOn, $this->endsOn, $today);
    }
}
