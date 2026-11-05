<?php

use App\Modules\Identity\Application\CreateRole;
use App\Modules\Identity\Application\SetRoleStatus;
use App\Modules\Identity\Domain\Role;
use App\Modules\Identity\Domain\RolePermissionRepository;
use App\Modules\Identity\Domain\RoleRecord;
use App\Shared\Audit\Auditor;
use App\Shared\Exceptions\DomainException;

beforeEach(function () {
    $this->roles = new class implements RolePermissionRepository
    {
        /** @var array<int, RoleRecord> */
        public array $items = [];

        public function __construct()
        {
            $this->items[1] = new RoleRecord(1, 'administrador', 'Administrador', null, true, true, 1);
            $this->items[2] = new RoleRecord(2, 'docente', 'Docente', null, true, true, 2);
        }

        public function allPermissions(): array
        {
            return ['users.manage', 'courses.view'];
        }

        public function allPermissionRecords(): array
        {
            return [];
        }

        public function permissionsOf(Role|string $role): array
        {
            return [];
        }

        public function sync(Role|string $role, array $permissions): void {}

        public function find(int $id): ?RoleRecord
        {
            return $this->items[$id] ?? null;
        }

        public function all(?string $term = null, ?string $status = null): array
        {
            return array_values($this->items);
        }

        public function nameExists(string $name): bool
        {
            $needle = mb_strtolower(trim($name));
            foreach ($this->items as $r) {
                if (mb_strtolower($r->name) === $needle) {
                    return true;
                }
            }

            return false;
        }

        public function create(string $name, ?string $description, array $permissions): RoleRecord
        {
            $id = count($this->items) + 1;
            $record = new RoleRecord($id, trim($name), trim($name), $description, true, false, 0);

            return $this->items[$id] = $record;
        }

        public function setActive(int $id, bool $active): void
        {
            $r = $this->items[$id];
            $this->items[$id] = new RoleRecord($id, $r->name, $r->label, $r->description, $active, $r->isSystem, $r->userCount);
        }
    };

    $this->audit = new class implements Auditor
    {
        /** @var list<string> */
        public array $events = [];

        public function log(string $event, ?int $userId = null, ?string $subject = null, array $metadata = []): void
        {
            $this->events[] = $event;
        }
    };
});

it('crea un nuevo rol activo y registra auditoría', function () {
    $useCase = new CreateRole($this->roles, $this->audit);
    $role = $useCase('Coordinador', 'Rol de coordinación', ['courses.view'], 99);

    expect($role->name)->toBe('Coordinador')
        ->and($role->isActive)->toBeTrue()
        ->and($this->audit->events)->toBe(['role.created']);
});

it('rechaza nombres duplicados ignorando mayúsculas y espacios', function () {
    $useCase = new CreateRole($this->roles, $this->audit);

    expect(fn () => $useCase('  ADMINISTRADOR  ', null, [], 99))
        ->toThrow(DomainException::class, 'Ya existe un rol con ese nombre');
});

it('rechaza permisos desconocidos al crear rol', function () {
    $useCase = new CreateRole($this->roles, $this->audit);

    expect(fn () => $useCase('Tutor', null, ['permiso.inexistente'], 99))
        ->toThrow(DomainException::class, 'Permisos desconocidos');
});

it('impide inactivar el rol Administrador', function () {
    $useCase = new SetRoleStatus($this->roles, $this->audit);

    expect(fn () => $useCase(1, false, false, 99))
        ->toThrow(DomainException::class, 'El rol Administrador no se puede inactivar');
});

it('exige confirmación al inactivar un rol que tiene usuarios asignados', function () {
    $useCase = new SetRoleStatus($this->roles, $this->audit);

    expect(fn () => $useCase(2, false, false, 99))
        ->toThrow(DomainException::class, 'Confirma la inactivación: 2 usuario(s) quedarán bloqueados');

    $useCase(2, false, true, 99);
    expect($this->roles->find(2)->isActive)->toBeFalse()
        ->and($this->audit->events)->toBe(['role.deactivated']);
});

it('permite reactivar un rol inactivo sin pedir confirmación', function () {
    $this->roles->setActive(2, false);
    $useCase = new SetRoleStatus($this->roles, $this->audit);

    $useCase(2, true, false, 99);
    expect($this->roles->find(2)->isActive)->toBeTrue()
        ->and($this->audit->events)->toBe(['role.activated']);
});
