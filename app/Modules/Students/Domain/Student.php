<?php

namespace App\Modules\Students\Domain;

final readonly class Student
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public ?string $username,
        public ?string $email,
        public ?string $identifier,
        public ?string $birthDate,
        public ?string $phone,
        public bool $isActive,
    ) {}
}
