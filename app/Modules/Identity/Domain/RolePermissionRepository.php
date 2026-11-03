<?php

namespace App\Modules\Identity\Domain;

interface RolePermissionRepository
{
    /** @return list<string> */
    public function allPermissions(): array;

    /** @return list<string> */
    public function permissionsOf(Role $role): array;

    /** @param list<string> $permissions */
    public function sync(Role $role, array $permissions): void;
}
