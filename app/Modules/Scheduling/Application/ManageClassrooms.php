<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;

class ManageClassrooms
{
    public function __construct(private readonly ScheduleRepository $schedules) {}

    /** @param array{name:string,capacity?:?int,is_active?:bool} $data */
    public function save(array $data, ?int $classroomId = null): int
    {
        if ($classroomId !== null && isset($data['is_active']) && $data['is_active'] === false) {
            if ($this->schedules->classroomHasSchedules($classroomId)) {
                throw new SchedulingRuleViolation('classroom', 'El aula tiene programaciones vigentes; reasígnelas antes de desactivarla.');
            }
        }

        return $this->schedules->saveClassroom([
            'name' => $data['name'],
            'capacity' => $data['capacity'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ], $classroomId);
    }

    public function delete(int $classroomId): void
    {
        if ($this->schedules->classroomHasSchedules($classroomId)) {
            throw new SchedulingRuleViolation('classroom', 'El aula tiene programaciones asociadas y no puede eliminarse.');
        }

        $this->schedules->deleteClassroom($classroomId);
    }
}
