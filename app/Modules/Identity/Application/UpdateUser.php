<?php

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\UserRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class UpdateUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly Auditor $auditor,
    ) {}

    public function __invoke(
        int $id,
        string $name,
        string $email,
        ?string $password = null,
        ?int $actorId = null,
    ): void {
        $email = strtolower(trim($email));
        $this->users->find($id) ?? throw new DomainException('El usuario no existe.');

        $existing = $this->users->findByEmail($email);
        if ($existing !== null && $existing->id !== $id) {
            throw new DomainException('Ya existe un usuario con ese correo.', 'email');
        }

        $passwordHash = $password !== null && trim($password) !== ''
            ? $this->hasher->hash($password)
            : null;

        $this->users->update($id, $name, $email, $passwordHash, $passwordHash !== null);
        $this->auditor->log('user.updated', $actorId, "user:{$id}");
        if ($passwordHash !== null) {
            $this->auditor->log('user.password_reset_by_admin', $actorId, "user:{$id}");
        }
    }
}
