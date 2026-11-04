<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Scheduling\Domain\ScheduleRepository;

class DeleteCourseSession
{
    public function __construct(private readonly ScheduleRepository $schedules) {}

    public function __invoke(int $sessionId): void
    {
        $this->schedules->deleteSession($sessionId);
    }
}
