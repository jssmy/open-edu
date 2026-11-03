<?php

namespace App\Modules\Enrollments\Domain;

use App\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final readonly class Enrollment
{
    public function __construct(
        public int $id,
        public int $studentId,
        public int $courseId,
        public EnrollmentStatus $status,
        public DateTimeImmutable $enrolledAt,
    ) {}

    public function changeStatus(EnrollmentStatus $status): self
    {
        $allowed = match ($this->status) {
            EnrollmentStatus::Active => [EnrollmentStatus::Suspended, EnrollmentStatus::Cancelled],
            EnrollmentStatus::Suspended => [EnrollmentStatus::Active, EnrollmentStatus::Cancelled],
            EnrollmentStatus::Cancelled => [],
        };

        if ($status === $this->status) {
            return $this;
        }

        if (! in_array($status, $allowed, true)) {
            throw new DomainException('No se puede cambiar el estado de una inscripción cancelada.', 'status');
        }

        return new self($this->id, $this->studentId, $this->courseId, $status, $this->enrolledAt);
    }
}
