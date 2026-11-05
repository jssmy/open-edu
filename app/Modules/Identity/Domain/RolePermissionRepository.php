<?php

namespace App\Modules\Identity\Domain;

interface RolePermissionRepository
{
    /** @return list<string> */
    public function allPermissions(): array;

    /** @return list<PermissionRecord> */
    public function allPermissionRecords(): array;

    /** @return list<string> */
    public function permissionsOf(Role|string $role): array;

    /** @param list<string> $permissions */
    public function sync(Role|string $role, array $permissions): void;

    public function find(int $id): ?RoleRecord;

    /** @return list<RoleRecord> */
    public function all(?string $term = null, ?string $status = null): array;

    /** Coincidencia sin distinguir mayúsculas, incluye roles inactivos. */
    public function nameExists(string $name): bool;

    /** @param list<string> $permissions */
    public function create(string $name, ?string $description, array $permissions): RoleRecord;

    public function setActive(int $id, bool $active): void;
}
