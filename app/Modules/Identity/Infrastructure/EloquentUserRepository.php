<?php

namespace App\Modules\Identity\Infrastructure;

use App\Models\User;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\UserRecord;
use App\Modules\Identity\Domain\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function find(int $id): ?UserRecord
    {
        $user = User::find($id);

        return $user ? $this->toRecord($user) : null;
    }

    public function findByEmail(string $email): ?UserRecord
    {
        $user = User::where('email', $email)->first();

        return $user ? $this->toRecord($user) : null;
    }

    public function all(): array
    {
        return User::with('roles')->orderBy('name')->get()->map(fn (User $u) => $this->toRecord($u))->all();
    }

    public function create(string $name, string $email, string $passwordHash, Role|string $role, bool $mustChangePassword): UserRecord
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'must_change_password' => $mustChangePassword,
            'is_active' => true,
        ]);
        $user->assignRole($this->nameOf($role));

        return $this->toRecord($user);
    }

    public function update(int $id, string $name, string $email): void
    {
        User::whereKey($id)->update(['name' => $name, 'email' => $email]);
    }

    public function setActive(int $id, bool $active): void
    {
        User::whereKey($id)->update(['is_active' => $active]);
    }

    public function changeRole(int $id, Role|string $role): void
    {
        User::findOrFail($id)->syncRoles([$this->nameOf($role)]);
    }

    public function countActiveByRole(Role|string $role): int
    {
        return User::role($this->nameOf($role))->where('is_active', true)->count();
    }

    private function toRecord(User $user): UserRecord
    {
        $roleName = $user->getRoleNames()->first();

        return new UserRecord(
            $user->id,
            $user->name,
            $user->email,
            $user->is_active,
            $user->must_change_password,
            $roleName ? Role::tryFrom($roleName) : null,
            $roleName,
        );
    }

    private function nameOf(Role|string $role): string
    {
        return $role instanceof Role ? $role->value : $role;
    }
}
