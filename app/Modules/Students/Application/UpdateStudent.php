<?php

namespace App\Modules\Students\Application;

use App\Modules\Identity\Application\PasswordHasher;
use App\Modules\Students\Domain\StudentRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class UpdateStudent
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly PasswordHasher $hasher,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(
        int $studentId,
        string $name,
        string $username,
        ?string $email,
        string $identifier,
        string $birthDate,
        ?string $temporaryPassword = null,
        ?string $phone = null,
        ?int $actorId = null,
    ): void {
        if ($this->students->find($studentId) === null) {
            throw new DomainException('No se encontró el estudiante.');
        }

        $passwordHash = $temporaryPassword !== null && trim($temporaryPassword) !== ''
            ? $this->hasher->hash($temporaryPassword)
            : null;

        $this->students->update(
            $studentId,
            trim($name),
            trim($username),
            $email !== null && trim($email) !== '' ? strtolower(trim($email)) : null,
            trim($identifier),
            $birthDate,
            $passwordHash,
            $phone !== null && trim($phone) !== '' ? trim($phone) : null,
        );
        $this->auditor->log('student.updated', $actorId, "student:{$studentId}");
        if ($passwordHash !== null) {
            $this->auditor->log('student.password_reset', $actorId, "student:{$studentId}");
        }
    }
}
