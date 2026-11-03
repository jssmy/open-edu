<?php

use App\Modules\Identity\Application\ChangeUserRole;
use App\Modules\Identity\Application\CreateUser;
use App\Modules\Identity\Application\PasswordHasher;
use App\Modules\Identity\Application\SetUserStatus;
use App\Modules\Identity\Application\UpdateRolePermissions;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\UserRecord;
use App\Modules\Identity\Domain\UserRepository;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

final class InMemoryUsers implements UserRepository
{
    /** @var array<int, UserRecord> */
    public array $items = [];

    public function find(int $id): ?UserRecord
    {
        return $this->items[$id] ?? null;
    }

    public function findByEmail(string $email): ?UserRecord
    {
        foreach ($this->items as $u) {
            if ($u->email === $email) {
                return $u;
            }
        }

        return null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function create(string $name, string $email, string $passwordHash, Role $role, bool $mustChangePassword): UserRecord
    {
        $id = count($this->items) + 1;

        return $this->items[$id] = new UserRecord($id, $name, $email, true, $mustChangePassword, $role);
    }

    public function update(int $id, string $name, string $email): void {}

    public function setActive(int $id, bool $active): void
    {
        $u = $this->items[$id];
        $this->items[$id] = new UserRecord($id, $u->name, $u->email, $active, $u->mustChangePassword, $u->role);
    }

    public function changeRole(int $id, Role $role): void
    {
        $u = $this->items[$id];
        $this->items[$id] = new UserRecord($id, $u->name, $u->email, $u->isActive, $u->mustChangePassword, $role);
    }

    public function countActiveByRole(Role $role): int
    {
        return count(array_filter($this->items, fn ($u) => $u->role === $role && $u->isActive));
    }
}

final class FakeAuditor implements Auditor
{
    /** @var list<string> */
    public array $events = [];

    public function log(string $event, ?int $userId = null, ?string $subject = null, array $metadata = []): void
    {
        $this->events[] = $event;
    }
}

final class FakeHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return "hashed:{$plain}";
    }
}

beforeEach(function () {
    $this->users = new InMemoryUsers;
    $this->audit = new FakeAuditor;
});

it('crea un usuario con cambio de contraseña obligatorio y correo normalizado', function () {
    $user = (new CreateUser($this->users, new FakeHasher, $this->audit))('Ana', ' ANA@Test.com ', Role::Docente, 'temporal-1');

    expect($user->email)->toBe('ana@test.com')->and($user->mustChangePassword)->toBeTrue();
    expect($this->audit->events)->toBe(['user.created']);
});

it('no permite correos duplicados', function () {
    $create = new CreateUser($this->users, new FakeHasher, $this->audit);
    $create('Ana', 'ana@test.com', Role::Docente, 'temporal-1');

    expect(fn () => $create('Otra', 'ana@test.com', Role::Docente, 'temporal-1'))->toThrow(DomainException::class);
});

it('protege al último administrador activo', function () {
    $admin = (new CreateUser($this->users, new FakeHasher, $this->audit))('Admin', 'a@test.com', Role::Administrador, 'x');

    expect(fn () => (new SetUserStatus($this->users, $this->audit))($admin->id, false, 99))->toThrow(DomainException::class);
    expect(fn () => (new ChangeUserRole($this->users, $this->audit))($admin->id, Role::Docente, 99))->toThrow(DomainException::class);
});

it('audita el cambio de rol', function () {
    $user = (new CreateUser($this->users, new FakeHasher, $this->audit))('Ana', 'ana@test.com', Role::Docente, 'x');
    (new ChangeUserRole($this->users, $this->audit))($user->id, Role::GestorDeCursos, 99);

    expect($this->users->find($user->id)->role)->toBe(Role::GestorDeCursos)->and($this->audit->events)->toContain('user.role_changed');
});

it('rechaza permisos desconocidos y la edición del Administrador', function () {
    $repo = new class implements RolePermissionRepository
    {
        public function allPermissions(): array
        {
            return ['courses.view'];
        }

        public function permissionsOf(Role $role): array
        {
            return [];
        }

        public function sync(Role $role, array $permissions): void {}
    };
    $update = new UpdateRolePermissions($repo, $this->audit);

    expect(fn () => $update(Role::Docente, ['inventado'], 1))->toThrow(DomainException::class);
    expect(fn () => $update(Role::Administrador, [], 1))->toThrow(DomainException::class);
    $update(Role::Docente, ['courses.view'], 1);
    expect($this->audit->events)->toBe(['role.permissions_updated']);
});
