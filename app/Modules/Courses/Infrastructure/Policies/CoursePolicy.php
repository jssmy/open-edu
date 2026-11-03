<?php

namespace App\Modules\Courses\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use Illuminate\Auth\Access\Response;

final class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courses.view');
    }

    public function view(User $user, Course $course): Response
    {
        if (! $user->can('courses.view')) {
            return Response::deny();
        }

        if ($user->hasRole('docente') && ! $course->teachers()->whereKey($user->id)->exists()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function manageContent(User $user, Course $course): Response
    {
        if (! $user->can('content.manage')) {
            return Response::deny();
        }

        if ($user->hasRole('docente') && ! $course->teachers()->whereKey($user->id)->exists()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
