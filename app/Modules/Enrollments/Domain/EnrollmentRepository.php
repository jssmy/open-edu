<?php

namespace App\Modules\Enrollments\Domain;

interface EnrollmentRepository
{
    public function find(int $enrollmentId): ?Enrollment;

    public function findForStudentAndCourse(int $studentId, int $courseId): ?Enrollment;

    public function create(int $studentId, int $courseId): Enrollment;

    public function setStatus(int $enrollmentId, EnrollmentStatus $status): void;
}
