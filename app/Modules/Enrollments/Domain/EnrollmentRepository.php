<?php

namespace App\Modules\Enrollments\Domain;

use App\Modules\Scheduling\Domain\ConflictDetector;

interface EnrollmentRepository
{
    public function find(int $enrollmentId): ?Enrollment;

    public function findForStudentAndCourse(int $studentId, int $courseId): ?Enrollment;

    public function create(int $studentId, int $courseId, ?int $courseOfferingId = null): Enrollment;

    public function enrollInOffering(int $studentId, int $courseOfferingId, ConflictDetector $detector): Enrollment;

    public function setStatus(int $enrollmentId, EnrollmentStatus $status): void;
}
