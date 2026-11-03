<?php

namespace App\Modules\Students\Application;

use App\Modules\Students\Domain\StudentRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class UpdateStudent
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(
        int $studentId,
        string $name,
        string $email,
        ?string $identifier,
        ?string $phone,
        ?int $actorId = null,
    ): void {
        if ($this->students->find($studentId) === null) {
            throw new DomainException('No se encontró el estudiante.');
        }

        $this->students->update($studentId, trim($name), strtolower(trim($email)), $identifier, $phone);
        $this->auditor->log('student.updated', $actorId, "student:{$studentId}");
    }
}
