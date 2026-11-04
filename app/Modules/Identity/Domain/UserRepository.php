<?php

namespace App\Modules\Identity\Domain;

interface UserRepository
{
    public function find(int $id): ?UserRecord;

    public function findByEmail(string $email): ?UserRecord;

    /** @return list<UserRecord> */
    public function all(): array;

    public function create(string $name, string $email, string $passwordHash, Role|string $role, bool $mustChangePassword): UserRecord;

    public function update(int $id, string $name, string $email): void;

    public function setActive(int $id, bool $active): void;

    public function changeRole(int $id, Role|string $role): void;

    public function countActiveByRole(Role|string $role): int;
}
