<?php

namespace App\Modules\Enrollments\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Students\Infrastructure\Models\Student;

final class CourseAccessPolicy
{
    public function view(User $user, Course $course): bool
    {
        if (! $user->is_active || ! $user->hasRole('estudiante')) {
            return false;
        }

        if ($course->status !== 'published') {
            return false;
        }

        $student = Student::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $enrollment = $student->enrollments()
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->with('courseOffering')
            ->first();

        if ($enrollment === null) {
            return false;
        }

        // Si la oferta tiene una fecha de inicio futura, no se permite el acceso todavía
        if ($enrollment->courseOffering?->starts_on !== null) {
            $startsOn = $enrollment->courseOffering->starts_on->toDateString();
            if ($startsOn > now()->toDateString()) {
                return false;
            }
        }

        return true;
    }
}
