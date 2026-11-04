<?php

namespace App\Modules\Enrollments\Application;

use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class UnenrollStudent
{
    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(int $enrollmentId, ?int $actorId = null): void
    {
        $enrollment = $this->enrollments->find($enrollmentId)
            ?? throw new DomainException('No se encontró la matrícula.');

        $updated = $enrollment->changeStatus(EnrollmentStatus::Cancelled);

        $this->enrollments->setStatus($enrollmentId, $updated->status);
        $this->auditor->log('enrollment.cancelled', $actorId, "enrollment:{$enrollmentId}", [
            'student_id' => $enrollment->studentId,
            'course_id' => $enrollment->courseId,
            'course_offering_id' => $enrollment->courseOfferingId,
        ]);
    }
}
