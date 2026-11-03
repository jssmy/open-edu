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
        string $email,
        string $temporaryPassword,
        ?string $identifier,
        ?string $phone,
        ?int $actorId = null,
    ): Student {
        $student = $this->students->register(
            trim($name),
            strtolower(trim($email)),
            $this->hasher->hash($temporaryPassword),
            $identifier,
            $phone,
        );
        $this->auditor->log('student.registered', $actorId, "student:{$student->id}");

        return $student;
    }
}
