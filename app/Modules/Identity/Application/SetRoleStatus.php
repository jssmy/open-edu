<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class SetRoleStatus
{
    public function __construct(private readonly RolePermissionRepository $roles, private readonly Auditor $auditor) {}

    public function __invoke(int $id, bool $active, bool $confirmed, int $actorId): void
    {
        $role = $this->roles->find($id) ?? throw new DomainException('El rol no existe.');

        if (! $active && $role->name === Role::Administrador->value) {
            throw new DomainException('El rol Administrador no se puede inactivar.');
        }
        if (! $active && $role->userCount > 0 && ! $confirmed) {
            throw new DomainException("Confirma la inactivación: {$role->userCount} usuario(s) quedarán bloqueados.");
        }

        $this->roles->setActive($id, $active);
        $this->auditor->log(
            $active ? 'role.activated' : 'role.deactivated',
            $actorId,
            "role:{$role->name}",
            $active ? [] : ['affected_users' => $role->userCount],
        );
    }
}
