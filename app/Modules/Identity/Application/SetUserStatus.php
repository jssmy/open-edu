<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\UserRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class SetUserStatus
{
    public function __construct(private readonly UserRepository $users, private readonly Auditor $auditor) {}

    public function __invoke(int $id, bool $active, int $actorId): void
    {
        $user = $this->users->find($id) ?? throw new DomainException('El usuario no existe.');

        if (! $active && $id === $actorId) {
            throw new DomainException('No puedes desactivar tu propia cuenta.');
        }
        if (! $active && $user->role === Role::Administrador && $this->users->countActiveByRole(Role::Administrador) <= 1) {
            throw new DomainException('Debe existir al menos un administrador activo.');
        }

        $this->users->setActive($id, $active);
        $this->auditor->log($active ? 'user.activated' : 'user.deactivated', $actorId, "user:{$id}");
    }
}
