<?php

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;

final class SpatieRolePermissionRepository implements RolePermissionRepository
{
    public function allPermissions(): array
    {
        return Permission::orderBy('name')->pluck('name')->all();
    }

    public function permissionsOf(Role $role): array
    {
        return RoleModel::findByName($role->value, 'web')->permissions->pluck('name')->sort()->values()->all();
    }

    public function sync(Role $role, array $permissions): void
    {
        RoleModel::findByName($role->value, 'web')->syncPermissions($permissions);
    }
}
