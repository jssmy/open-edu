<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\UserRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class ChangeUserRole
{
    public function __construct(private readonly UserRepository $users, private readonly Auditor $auditor) {}

    public function __invoke(int $id, Role $role, int $actorId): void
    {
        $user = $this->users->find($id) ?? throw new DomainException('El usuario no existe.');

        if ($user->role === Role::Administrador && $role !== Role::Administrador
            && $this->users->countActiveByRole(Role::Administrador) <= 1) {
            throw new DomainException('Debe existir al menos un administrador activo.', 'role');
        }

        $this->users->changeRole($id, $role);
        $this->auditor->log('user.role_changed', $actorId, "user:{$id}", [
            'from' => $user->role?->value,
            'to' => $role->value,
        ]);
    }
}
