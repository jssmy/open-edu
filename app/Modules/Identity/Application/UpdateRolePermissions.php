<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class UpdateRolePermissions
{
    public function __construct(private readonly RolePermissionRepository $roles, private readonly Auditor $auditor) {}

    /** @param list<string> $permissions */
    public function __invoke(Role $role, array $permissions, int $actorId): void
    {
        if ($role === Role::Administrador) {
            throw new DomainException('Los permisos del Administrador no se pueden modificar.');
        }

        $unknown = array_diff($permissions, $this->roles->allPermissions());
        if ($unknown !== []) {
            throw new DomainException('Permisos desconocidos: '.implode(', ', $unknown), 'permissions');
        }

        $this->roles->sync($role, array_values(array_unique($permissions)));
        $this->auditor->log('role.permissions_updated', $actorId, "role:{$role->value}", ['permissions' => $permissions]);
    }
}
