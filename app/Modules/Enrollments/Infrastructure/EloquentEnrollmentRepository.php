<?php

namespace App\Modules\Enrollments\Infrastructure;

use App\Modules\Enrollments\Domain\Enrollment as EnrollmentRecord;
use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;

final class EloquentEnrollmentRepository implements EnrollmentRepository
{
    public function find(int $enrollmentId): ?EnrollmentRecord
    {
        $enrollment = Enrollment::query()->find($enrollmentId);

        return $enrollment ? $this->toRecord($enrollment) : null;
    }

    public function findForStudentAndCourse(int $studentId, int $courseId): ?EnrollmentRecord
    {
        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        return $enrollment ? $this->toRecord($enrollment) : null;
    }

    public function create(int $studentId, int $courseId): EnrollmentRecord
    {
        return $this->toRecord(Enrollment::query()->create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]));
    }

    public function setStatus(int $enrollmentId, EnrollmentStatus $status): void
    {
        Enrollment::query()->whereKey($enrollmentId)->update(['status' => $status]);
    }

    private function toRecord(Enrollment $enrollment): EnrollmentRecord
    {
        return new EnrollmentRecord(
            $enrollment->id,
            $enrollment->student_id,
            $enrollment->course_id,
            $enrollment->status,
            $enrollment->enrolled_at->toDateTimeImmutable(),
        );
    }
}
