<?php

namespace App\Modules\Assessments\Infrastructure\Policies;

use App\Models\User;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Auth\Access\Response;

final class AssessmentPolicy
{
    public function manageAssessment(User $user, Course $course): Response
    {
        if (! $user->can('assessments.manage')) {
            return Response::deny();
        }

        if ($user->hasRole('docente') && ! $course->teachers()->whereKey($user->id)->exists()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function take(User $user, Assessment $assessment): bool
    {
        if (! $this->hasActiveEnrollment($user, $assessment)
            || ! $assessment->is_published
            || $assessment->course->trashed()
            || $assessment->course->status !== 'published') {
            return false;
        }

        $student = Student::query()->where('user_id', $user->id)->first();

        if ($student === null) {
            return false;
        }

        $attempts = $assessment->attempts()->where('student_id', $student->id);

        return $attempts->where('status', 'in_progress')->exists()
            || $assessment->attempts()->where('student_id', $student->id)->count() < $assessment->max_attempts;
    }

    public function submit(User $user, Assessment $assessment): bool
    {
        if (! $this->hasActiveEnrollment($user, $assessment)) {
            return false;
        }

        $student = Student::query()->where('user_id', $user->id)->first();

        return $student !== null && $assessment->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->exists();
    }

    public function viewResults(User $user, Assessment $assessment): Response
    {
        if ($user->can('assessments.manage') || $user->can('students.view')) {
            if ($user->hasRole('docente') && ! $assessment->course->teachers()->whereKey($user->id)->exists()) {
                return Response::denyAsNotFound();
            }

            return Response::allow();
        }

        return $this->hasActiveOrHistoricalEnrollment($user, $assessment)
            ? Response::allow()
            : Response::deny();
    }

    private function hasActiveEnrollment(User $user, Assessment $assessment): bool
    {
        if (! $user->is_active || ! $user->hasRole('estudiante')) {
            return false;
        }

        $student = Student::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($student === null) {
            return false;
        }

        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $assessment->course_id)
            ->where('status', EnrollmentStatus::Active->value)
            ->with('courseOffering')
            ->first();

        if ($enrollment === null) {
            return false;
        }

        // Si la oferta tiene fecha de fin y ya pasó, no se permite rendir evaluaciones
        if ($enrollment->courseOffering?->ends_on !== null) {
            if ($enrollment->courseOffering->ends_on->toDateString() < now()->toDateString()) {
                return false;
            }
        }

        return true;
    }

    private function hasActiveOrHistoricalEnrollment(User $user, Assessment $assessment): bool
    {
        if (! $user->is_active || ! $user->hasRole('estudiante')) {
            return false;
        }

        $student = Student::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $student !== null && Enrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $assessment->course_id)
            ->exists();
    }
}
