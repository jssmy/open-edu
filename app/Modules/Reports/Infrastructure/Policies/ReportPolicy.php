<?php

namespace App\Modules\Reports\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Auth\Access\Response;

final class ReportPolicy
{
    public function viewCourseProgress(User $user, Course $course): Response
    {
        if (! $user->can('reports.view')) {
            return Response::deny();
        }

        if ($user->hasRole('docente') && ! $course->teachers()->whereKey($user->id)->exists()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function viewMyProgress(User $user, Student $student): bool
    {
        return $user->is_active
            && $user->hasRole('estudiante')
            && $student->is_active
            && $student->user_id === $user->id;
    }
}
