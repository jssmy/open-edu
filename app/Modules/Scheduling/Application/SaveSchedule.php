<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ConflictDetector;
use App\Modules\Scheduling\Domain\Modality;
use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;
use App\Modules\Scheduling\Domain\TimeBlock;

/** Crea o actualiza una programación validando modalidad, periodo cerrado y conflictos. */
class SaveSchedule
{
    public function __construct(
        private readonly ScheduleRepository $schedules,
        private readonly ConflictDetector $detector,
    ) {}

    /**
     * @param  array{course_id:int,teacher_id:int,academic_period_id:int,modality:string,classroom_id?:?int}  $data
     * @param  list<array{day_of_week:int,starts_at:string,ends_at:string}>  $rawBlocks
     */
    public function __invoke(array $data, array $rawBlocks, ?int $scheduleId = null): int
    {
        $modality = Modality::from($data['modality']);
        $data['classroom_id'] = $modality === Modality::Virtual ? null : ($data['classroom_id'] ?? null);

        if ($modality === Modality::InPerson && $data['classroom_id'] === null) {
            throw new SchedulingRuleViolation('classroom_id', 'Debe seleccionar un aula para una clase presencial.');
        }

        if ($this->schedules->periodHasEnded($data['academic_period_id'])
            || ($scheduleId !== null && $this->schedules->scheduleEnded($scheduleId))) {
            throw new SchedulingRuleViolation('academic_period_id', 'El periodo está cerrado y no admite cambios.');
        }

        $blocks = array_map(
            fn (array $block) => TimeBlock::fromTimes($block['day_of_week'], $block['starts_at'], $block['ends_at']),
            $rawBlocks,
        );

        $periodId = $data['academic_period_id'];

        if ($this->detector->hasConflict($blocks, $this->schedules->teacherBlocks($periodId, $data['teacher_id'], $scheduleId))) {
            throw new SchedulingRuleViolation('teacher_id', 'El docente ya tiene una clase en ese horario.');
        }

        if ($data['classroom_id'] !== null
            && $this->detector->hasConflict($blocks, $this->schedules->classroomBlocks($periodId, $data['classroom_id'], $scheduleId))) {
            throw new SchedulingRuleViolation('classroom_id', 'El aula ya está ocupada en ese horario.');
        }

        if ($scheduleId === null) {
            $scheduleId = $this->schedules->create($data, $rawBlocks);
        } else {
            $previousCourse = $this->schedules->courseIdOf($scheduleId);
            $this->schedules->update($scheduleId, $data, $rawBlocks);
            $this->schedules->syncCourseTeachers($previousCourse);
        }

        $this->schedules->syncCourseTeachers($data['course_id']);

        return $scheduleId;
    }
}
