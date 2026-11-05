# Research: Nombres Descriptivos de Permisos en Gestión de Roles

**Feature**: `007-permission-display-names`  
**Date**: 2026-11-04  
**Status**: Completed

## 1. Contexto y Problema
En `open-edu`, los permisos del sistema se gestionan a través de Spatie Laravel Permission. En la base de datos y en las interfaces actuales (`Roles/Create.vue` y `Roles/Index.vue`), los permisos se presentan utilizando su clave técnica cruda (`courses.manage`, `content.manage`, `scheduling.manage`, `scheduling.view-own`, `users.manage`, `roles.manage`, `students.manage`, `students.view`, `enrollments.manage`, `assessments.manage`, `reports.view`, `study`).

Esta nomenclatura no es amigable para los administradores ni usuarios de negocio. Se requiere que cada permiso tenga un **nombre amigable (display name / label)**, opcionalmente categorizado por módulo, y que sea este nombre el que se muestre en la interfaz de usuario, manteniendo intactas las claves técnicas para la autorización.

## 2. Decisiones Técnicas

### Decisión 1: Representación de Permiso en el Dominio (`PermissionRecord`)
- **Decisión**: Crear un DTO/Record inmutable en `App\Modules\Identity\Domain\PermissionRecord` con las propiedades:
  - `string $name`: Clave técnica interna (ej. `courses.manage`).
  - `string $label`: Nombre amigable en español (ej. `Gestión de cursos`).
  - `string $module`: Módulo o categoría (ej. `Cursos`, `Programación`, `Usuarios`, etc.).
  - `?string $description`: Descripción corta opcional de las capacidades otorgadas.
- **Alternativas consideradas**:
  - *Agregar columnas `label` y `module` a la tabla `permissions`*: Spatie Permission permite migrar campos adicionales en `permissions`. Sin embargo, esto requiere migraciones de base de datos y sincronización vía seeders que podrían desfasarse de las definiciones canónicas en código.
  - *Diccionario canónico centralizado en PHP (Catalog/Enum/Config) con proyección a `PermissionRecord`*: Proporciona control total de versiones de traducción en código, velocidad instantánea, sin consultas complejas, con fallback automático. Además, permite mantener la tabla `permissions` de Spatie limpia o rellenar dinámicamente si se desea en el futuro.
- **Elección**: Definir un catálogo canónico centralizado de permisos con sus metadatos (label, module, description) en la capa de Infraestructura/Dominio de `Identity` (o mapeado en `SpatieRolePermissionRepository`), devolviendo `list<PermissionRecord>` en `RolePermissionRepository::allPermissionDetails()` o actualizando `allPermissions()`. Para evitar romper el contrato actual de `permissionsOf($role): list<string>`, mantendremos `permissionsOf` devolviendo claves técnicas y añadiremos o adaptaremos los métodos para proporcionar los registros de permisos descriptivos a los controladores de Inertia.

### Decisión 2: Compatibilidad de Contratos en `RolePermissionRepository`
- **Decisión**:
  - Extender `RolePermissionRepository` con un método `permissionCatalog(): array` (o tipado `list<PermissionRecord>`).
  - En `RoleController@create` y `RoleController@index`, enviar `permission_catalog` o `permissions` estructurados como:
    ```json
    [
      {
        "name": "courses.manage",
        "label": "Gestión de cursos",
        "module": "Cursos",
        "description": "Permite crear, editar y administrar el catálogo general de cursos."
      }
    ]
    ```
- **Fallback amigable**: Si un permiso registrado en la base de datos no está en el catálogo canónico (por ejemplo, creado en un plugin o seeder nuevo), un método helper `labelFor(string $name)` generará un nombre legible por convención: ej. `scheduling.view-own` → "Scheduling: View own" o "Ver propios horarios de programación".

### Decisión 3: Interfaz de Usuario (Vue 3 + Tailwind)
- **Decisión**:
  - En `Roles/Create.vue` y `Roles/Index.vue`:
    - El checkbox debe mostrar `:label="p.label"` de forma prominente.
    - Se puede mostrar un texto secundario o badge con el módulo (`p.module`) o la clave técnica sutilmente (`text-xs text-muted`) o en un tooltip/descripción para dar claridad total.
    - Agrupar visualmente los permisos por `module` en pestañas, acordeones o bloques de tarjetas para mejorar la legibilidad.
    - En el formulario reactivo (`form.permissions`), el array continúa almacenando las cadenas técnicas (`string[]`), por lo que el payload hacia el backend `StoreRoleRequest` y `update` no cambia en absoluto.

## 3. Catálogo Canónico Inicial de Permisos de Open-Edu

| Clave Técnica (`name`) | Nombre Amigable (`label`) | Módulo (`module`) | Descripción |
|---|---|---|---|
| `users.manage` | Gestión de usuarios | Usuarios | Crear, editar y suspender cuentas de usuario en el sistema. |
| `roles.manage` | Gestión de roles y permisos | Seguridad | Crear roles y configurar permisos de acceso en la plataforma. |
| `courses.manage` | Gestión de cursos | Cursos | Crear, editar estructura y administrar cursos institucionales. |
| `courses.view` | Consulta de cursos | Cursos | Visualizar el catálogo de cursos y contenidos publicados. |
| `content.manage` | Gestión de contenidos | Contenidos | Crear y editar lecciones, temas y diapositivas de cursos. |
| `students.manage` | Gestión de estudiantes | Estudiantes | Administrar expedientes y datos de estudiantes. |
| `students.view` | Consulta de estudiantes | Estudiantes | Ver información y avance de estudiantes. |
| `enrollments.manage` | Gestión de matrículas | Matrículas | Matricular y desmatricular estudiantes en clases y grupos. |
| `assessments.manage` | Gestión de evaluaciones | Evaluaciones | Crear, editar y calificar pruebas y evaluaciones académicas. |
| `reports.view` | Consulta de reportes | Reportes | Acceder a reportes y analíticas institucionales. |
| `scheduling.manage` | Gestión de programación académica | Programación | Programar periodos, clases, grupos, aulas y horarios. |
| `scheduling.view-own` | Visualización de clases asignadas | Programación | Ver las clases y horarios propios asignados a un docente. |
| `study` | Aula de estudio interactiva | Aprendizaje | Acceso al reproductor de lecciones y estudio para estudiantes. |

## 4. Conclusión
El enfoque es de impacto mínimo y riesgo cero para la lógica de seguridad:
- Se preservan las políticas de autorización, gates y claves de Spatie en BD.
- Se enriquece la capa de presentación y el repositorio de Identity con metadatos descriptivos en español.
- Se actualizan las vistas de Vue para agrupar y mostrar los nombres amigables.

