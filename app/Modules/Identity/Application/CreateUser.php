<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\UserRecord;
use App\Modules\Identity\Domain\UserRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class CreateUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly Auditor $auditor,
    ) {}

    /** El usuario recibe una contraseña temporal y debe cambiarla en su primer acceso. */
    public function __invoke(string $name, string $email, Role|string $role, string $temporaryPassword, ?int $actorId = null): UserRecord
    {
        $email = strtolower(trim($email));
        if ($this->users->findByEmail($email) !== null) {
            throw new DomainException('Ya existe un usuario con ese correo.', 'email');
        }

        $user = $this->users->create($name, $email, $this->hasher->hash($temporaryPassword), $role, true);
        $this->auditor->log('user.created', $actorId, "user:{$user->id}", ['role' => $role instanceof Role ? $role->value : $role]);

        return $user;
    }
}
