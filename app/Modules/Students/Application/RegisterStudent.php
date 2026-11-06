<?php

namespace App\Modules\Students\Application;

use App\Modules\Identity\Application\PasswordHasher;
use App\Modules\Students\Domain\Student;
use App\Modules\Students\Domain\StudentRepository;
use App\Shared\Audit\Auditor;

final class RegisterStudent
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly PasswordHasher $hasher,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(
        string $name,
        string $username,
        ?string $email,
        string $temporaryPassword,
        string $identifier,
        string $birthDate,
        ?string $phone = null,
        ?int $actorId = null,
    ): Student {
        $student = $this->students->register(
            trim($name),
            trim($username),
            $email !== null && trim($email) !== '' ? strtolower(trim($email)) : null,
            $this->hasher->hash($temporaryPassword),
            trim($identifier),
            $birthDate,
            $phone !== null && trim($phone) !== '' ? trim($phone) : null,
        );
        $this->auditor->log('student.registered', $actorId, "student:{$student->id}");

        return $student;
    }
}
