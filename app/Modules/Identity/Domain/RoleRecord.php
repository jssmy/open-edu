<?php

namespace App\Modules\Identity\Domain;

final readonly class RoleRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $label,
        public ?string $description,
        public bool $isActive,
        public bool $isSystem,
        public int $userCount,
    ) {}
}
