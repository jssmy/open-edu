<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ScheduleRepository;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;

class DeleteSchedule
{
    public function __construct(private readonly ScheduleRepository $schedules) {}

    public function __invoke(int $scheduleId): void
    {
        if ($this->schedules->scheduleEnded($scheduleId)) {
            throw new SchedulingRuleViolation('academic_period_id', 'El periodo está cerrado y no admite cambios.');
        }

        $courseId = $this->schedules->courseIdOf($scheduleId);
        $this->schedules->delete($scheduleId);
        $this->schedules->syncCourseTeachers($courseId);
    }
}
