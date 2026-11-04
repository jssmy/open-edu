<?php

namespace App\Modules\Identity\Domain;

final readonly class UserRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $isActive,
        public bool $mustChangePassword,
        public ?Role $role,
        public ?string $roleName = null,
    ) {}
}
