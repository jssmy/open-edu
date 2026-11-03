<?php

namespace App\Modules\Enrollments\Application;

use App\Modules\Enrollments\Domain\EnrollmentRepository;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class ChangeEnrollmentStatus
{
    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(int $enrollmentId, EnrollmentStatus $status, ?int $actorId = null): void
    {
        $enrollment = $this->enrollments->find($enrollmentId)
            ?? throw new DomainException('No se encontró la inscripción.');
        $updated = $enrollment->changeStatus($status);

        $this->enrollments->setStatus($enrollmentId, $updated->status);
        $this->auditor->log('enrollment.status_changed', $actorId, "enrollment:{$enrollmentId}", [
            'status' => $updated->status->value,
        ]);
    }
}
