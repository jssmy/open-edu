<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\RoleRecord;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class CreateRole
{
    public function __construct(private readonly RolePermissionRepository $roles, private readonly Auditor $auditor) {}

    /** @param list<string> $permissions */
    public function __invoke(string $name, ?string $description, array $permissions, int $actorId): RoleRecord
    {
        $name = trim($name);
        if ($this->roles->nameExists($name)) {
            throw new DomainException('Ya existe un rol con ese nombre (activo o inactivo). Si está inactivo, reactívalo.', 'name');
        }

        $unknown = array_diff($permissions, $this->roles->allPermissions());
        if ($unknown !== []) {
            throw new DomainException('Permisos desconocidos: '.implode(', ', $unknown), 'permissions');
        }

        $role = $this->roles->create($name, $description, array_values(array_unique($permissions)));
        $this->auditor->log('role.created', $actorId, "role:{$role->name}", ['permissions' => $permissions]);

        return $role;
    }
}
