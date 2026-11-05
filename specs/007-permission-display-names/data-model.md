# Data Model: Nombres Descriptivos de Permisos en Gestión de Roles

**Feature**: `007-permission-display-names`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Domain Entities & Value Objects

### `PermissionRecord` (Data Transfer Object / Inmutable Value Object)
Representa un permiso disponible en el sistema con información amigable para los usuarios.

**Ubicación**: `App\Modules\Identity\Domain\PermissionRecord`

```php
namespace App\Modules\Identity\Domain;

final readonly class PermissionRecord
{
    public function __construct(
        public string $name,
        public string $label,
        public string $module,
        public ?string $description = null,
    ) {}

    /** @return array{name: string, label: string, module: string, description: ?string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'module' => $this->module,
            'description' => $this->description,
        ];
    }
}
```

## 2. Definición del Repositorio de Dominio

### `RolePermissionRepository` (Interface Update)
Se amplía el contrato para consultar tanto las claves simples como los registros enriquecidos:

```php
namespace App\Modules\Identity\Domain;

interface RolePermissionRepository
{
    /** @return list<string> */
    public function allPermissions(): array;

    /**
     * Retorna todos los permisos con nombres descriptivos y categorías.
     * @return list<PermissionRecord>
     */
    public function allPermissionRecords(): array;

    /** @return list<string> */
    public function permissionsOf(Role|string $role): array;

    /** @param list<string> $permissions */
    public function sync(Role|string $role, array $permissions): void;

    public function find(int $id): ?RoleRecord;

    /** @return list<RoleRecord> */
    public function all(?string $term = null, ?string $status = null): array;

    public function nameExists(string $name): bool;

    /** @param list<string> $permissions */
    public function create(string $name, ?string $description, array $permissions): RoleRecord;

    public function setActive(int $id, bool $active): void;
}
```

## 3. Catálogo Canónico y Fallback (`PermissionDictionary`)
Para mantener el principio de separación y no requerir migraciones frágiles de BD si cambian textos cosméticos:

```php
namespace App\Modules\Identity\Domain;

final class PermissionDictionary
{
    /** @var array<string, array{label: string, module: string, description: string}> */
    private const CATALOG = [
        'users.manage' => [
            'label' => 'Gestión de usuarios',
            'module' => 'Usuarios',
            'description' => 'Crear, editar y suspender cuentas de usuario en el sistema.',
        ],
        'roles.manage' => [
            'label' => 'Gestión de roles y permisos',
            'module' => 'Seguridad',
            'description' => 'Crear roles y configurar permisos de acceso en la plataforma.',
        ],
        'courses.manage' => [
            'label' => 'Gestión de cursos',
            'module' => 'Cursos',
            'description' => 'Crear, editar estructura y administrar cursos institucionales.',
        ],
        'courses.view' => [
            'label' => 'Consulta de cursos',
            'module' => 'Cursos',
            'description' => 'Visualizar el catálogo de cursos y contenidos publicados.',
        ],
        'content.manage' => [
            'label' => 'Gestión de contenidos',
            'module' => 'Contenidos',
            'description' => 'Crear y editar lecciones, temas y diapositivas de cursos.',
        ],
        'students.manage' => [
            'label' => 'Gestión de estudiantes',
            'module' => 'Estudiantes',
            'description' => 'Administrar expedientes y datos de estudiantes.',
        ],
        'students.view' => [
            'label' => 'Consulta de estudiantes',
            'module' => 'Estudiantes',
            'description' => 'Ver información y avance de estudiantes.',
        ],
        'enrollments.manage' => [
            'label' => 'Gestión de matrículas',
            'module' => 'Matrículas',
            'description' => 'Matricular y desmatricular estudiantes en clases y grupos.',
        ],
        'assessments.manage' => [
            'label' => 'Gestión de evaluaciones',
            'module' => 'Evaluaciones',
            'description' => 'Crear, editar y calificar pruebas y evaluaciones académicas.',
        ],
        'reports.view' => [
            'label' => 'Consulta de reportes',
            'module' => 'Reportes',
            'description' => 'Acceder a reportes y analíticas institucionales.',
        ],
        'scheduling.manage' => [
            'label' => 'Gestión de programación académica',
            'module' => 'Programación',
            'description' => 'Programar periodos, clases, grupos, aulas y horarios.',
        ],
        'scheduling.view-own' => [
            'label' => 'Visualización de clases asignadas',
            'module' => 'Programación',
            'description' => 'Ver las clases y horarios propios asignados a un docente.',
        ],
        'study' => [
            'label' => 'Aula de estudio interactiva',
            'module' => 'Aprendizaje',
            'description' => 'Acceso al reproductor de lecciones y estudio para estudiantes.',
        ],
    ];

    public static function get(string $name): PermissionRecord
    {
        if (isset(self::CATALOG[$name])) {
            $info = self::CATALOG[$name];
            return new PermissionRecord($name, $info['label'], $info['module'], $info['description']);
        }

        // Fallback dinámico si se registra un permiso no catalogado
        $clean = str_replace(['.', '-', '_'], ' ', $name);
        $label = ucfirst($clean);

        return new PermissionRecord($name, $label, 'General', null);
    }
}
```

## 4. Frontend Interfaces (TypeScript)

En `resources/js/types/index.ts` o tipos de roles:
```typescript
export interface PermissionItem {
    name: string;
    label: string;
    module: string;
    description?: string | null;
}
```

