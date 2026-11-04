<?php

namespace App\Modules\Scheduling\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;

final class SchedulePolicy
{
    public function manage(User $user): bool
    {
        return $user->can('scheduling.manage');
    }

    public function viewOwn(User $user, ?CourseOffering $offering = null): bool
    {
        if (! $user->can('scheduling.view-own')) {
            return false;
        }

        if ($offering === null) {
            return true;
        }

        return $offering->teachers()->whereKey($user->id)->exists()
            || $offering->sessions()->where('teacher_id', $user->id)->exists();
    }
}
