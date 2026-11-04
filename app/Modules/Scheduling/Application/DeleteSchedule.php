<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ScheduleRepository;

class DeleteSchedule
{
    public function __construct(private readonly ScheduleRepository $schedules) {}

    public function __invoke(int $offeringId): void
    {
        $courseId = $this->schedules->courseIdOf($offeringId);
        $this->schedules->delete($offeringId);
        $this->schedules->syncCourseTeachers($courseId);
    }
}
