<?php

namespace App\Modules\Scheduling\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Scheduling\Infrastructure\Models\CourseSchedule;

final class SchedulePolicy
{
    public function manage(User $user): bool
    {
        return $user->can('scheduling.manage');
    }

    public function viewOwn(User $user, ?CourseSchedule $schedule = null): bool
    {
        if (! $user->can('scheduling.view-own')) {
            return false;
        }

        return $schedule === null || $schedule->teacher_id === $user->id;
    }
}

