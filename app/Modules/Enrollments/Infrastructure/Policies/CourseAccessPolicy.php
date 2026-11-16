<?php

namespace App\Modules\Enrollments\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Infrastructure\Models\Student;

final class CourseAccessPolicy
{
    public function view(User $user, Course $course): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($course->status !== 'published') {
            return false;
        }

        if ($user->hasRole('estudiante')) {
            $student = Student::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($student === null) {
                return false;
            }

            $enrollment = $student->enrollments()
                ->where('course_id', $course->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->with('courseOffering')
                ->first();

            if ($enrollment === null) {
                return false;
            }

            if ($enrollment->courseOffering?->starts_on !== null) {
                $startsOn = $enrollment->courseOffering->starts_on->toDateString();
                if ($startsOn > now()->toDateString()) {
                    return false;
                }
            }

            return true;
        }

        if ($user->hasRole('docente')) {
            return Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->exists();
        }

        return false;
    }
}
