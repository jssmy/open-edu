<?php

namespace App\Modules\Students\Application;

use App\Modules\Students\Domain\StudentRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class SetStudentStatus
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(int $studentId, bool $isActive, ?int $actorId = null): void
    {
        if ($this->students->find($studentId) === null) {
            throw new DomainException('No se encontró el estudiante.');
        }

        $this->students->setActive($studentId, $isActive);
        $this->auditor->log('student.status_changed', $actorId, "student:{$studentId}", [
            'is_active' => $isActive,
        ]);
    }
}
