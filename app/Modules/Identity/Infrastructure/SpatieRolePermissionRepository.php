<?php

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Domain\PermissionDictionary;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\RoleRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;

final class SpatieRolePermissionRepository implements RolePermissionRepository
{
    public function allPermissions(): array
    {
        return Permission::orderBy('name')->pluck('name')->all();
    }

    public function allPermissionRecords(): array
    {
        return Permission::orderBy('name')
            ->pluck('name')
            ->map(fn (string $name) => PermissionDictionary::get($name))
            ->all();
    }

    public function permissionsOf(Role|string $role): array
    {
        return RoleModel::findByName($this->nameOf($role), 'web')->permissions->pluck('name')->sort()->values()->all();
    }

    public function sync(Role|string $role, array $permissions): void
    {
        RoleModel::findByName($this->nameOf($role), 'web')->syncPermissions($permissions);
    }

    public function find(int $id): ?RoleRecord
    {
        $role = RoleModel::withCount('users')->find($id);

        return $role ? $this->toRecord($role) : null;
    }

    public function all(?string $term = null, ?string $status = null): array
    {
        $term = trim((string) $term);

        return RoleModel::withCount('users')
            ->when($term !== '', fn ($q) => $q->where('name', 'ilike', '%'.addcslashes($term, '%_\\').'%'))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (RoleModel $r) => $this->toRecord($r))
            ->all();
    }

    public function nameExists(string $name): bool
    {
        return RoleModel::whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])->exists();
    }

    public function create(string $name, ?string $description, array $permissions): RoleRecord
    {
        $role = RoleModel::create([
            'name' => trim($name),
            'guard_name' => 'web',
            'description' => $description,
            'is_active' => true,
            'is_system' => false,
        ]);
        $role->syncPermissions($permissions);

        return $this->toRecord($role->loadCount('users'));
    }

    public function setActive(int $id, bool $active): void
    {
        RoleModel::whereKey($id)->update(['is_active' => $active]);
    }

    private function nameOf(Role|string $role): string
    {
        return $role instanceof Role ? $role->value : $role;
    }

    private function toRecord(RoleModel $role): RoleRecord
    {
        return new RoleRecord(
            $role->id,
            $role->name,
            Role::tryFrom($role->name)?->label() ?? $role->name,
            $role->description,
            (bool) $role->is_active,
            (bool) $role->is_system,
            (int) $role->users_count,
        );
    }
}
