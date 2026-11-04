# Tasks: Gestión de Roles (crear e inactivar)

**Input**: Design documents from `/specs/005-role-management/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/roles-http.md
**Tests**: Incluidos (la constitución exige pruebas junto a la implementación; Pest).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: paralelizable (archivos distintos, sin dependencias)
- **[Story]**: US1 crear rol · US2 inactivar/reactivar · US3 bloqueo de login

## Phase 1: Setup

- [x] T001 Leer `.ai/rules/index.md` y las reglas aplicables a `app/Modules/Identity`, `resources/js/pages` y `tests`
- [x] T002 Activar skills `laravel-best-practices`, `inertia-vue-development` y `testing-best-practices` antes de escribir código

## Phase 2: Foundational (bloquea todas las historias)

- [x] T003 Crear migración `database/migrations/*_add_status_to_roles_table.php`: columnas `description` (nullable), `is_active` (default true), `is_system` (default false), índice único funcional sobre `lower(name)`; marcar como sistema administrador, gestor_de_cursos, docente, estudiante
- [x] T004 [P] Actualizar `database/seeders/RolesAndPermissionsSeeder.php` para crear los roles de sistema con `is_system = true` e `is_active = true`
- [x] T005 [P] Crear `app/Modules/Identity/Domain/RoleRecord.php` (id, name, label, description, isActive, isSystem, userCount)
- [x] T006 Ampliar `app/Modules/Identity/Domain/RolePermissionRepository.php` (find, create, setActive, countUsers, listing paginado) e implementarlo en `app/Modules/Identity/Infrastructure/SpatieRolePermissionRepository.php`
- [x] T007 [P] Crear `app/Modules/Identity/Infrastructure/RoleListQuery.php` con filtros `q`, `status` y paginación en servidor
- [x] T008 [P] Crear factory/estado de rol personalizado para pruebas (`database/factories`) y ajustar los helpers de test existentes si dependen del enum

**Checkpoint**: esquema y repositorio listos.

## Phase 3: User Story 1 - Crear un nuevo rol (P1) 🎯 MVP

**Goal**: el administrador crea un rol activo con nombre, descripción y permisos, y puede asignarlo a usuarios.
**Independent Test**: crear "Coordinador", asignarlo a un usuario y verlo en el listado.

### Tests

- [x] T009 [P] [US1] Pruebas Feature en `tests/Feature/Identity/RoleManagementTest.php`: creación exitosa (queda activo), nombre duplicado sin distinguir mayúsculas y contra inactivos, usuario sin `roles.manage` recibe 403, asignar rol nuevo a un usuario
- [x] T010 [P] [US1] Pruebas Unit en `tests/Unit/Identity/RoleUseCasesTest.php` para `CreateRole` (validación de permisos desconocidos, auditoría `role.created`)

### Implementation

- [x] T011 [P] [US1] Crear `app/Modules/Identity/Application/CreateRole.php` (unicidad, permisos válidos, transacción, auditoría)
- [x] T012 [P] [US1] Crear `app/Modules/Identity/Infrastructure/Http/Requests/StoreRoleRequest.php`
- [x] T013 [US1] Añadir `create`/`store` en `app/Modules/Identity/Infrastructure/Http/RoleController.php` y rutas `GET /roles/create`, `POST /roles` en `routes/web.php` bajo `can:roles.manage`
- [x] T014 [US1] Ajustar `ChangeUserRole.php`, `UserRecord.php`, `EloquentUserRepository.php`, `StoreUserRequest.php` y `UpdateUserRequest.php` para aceptar el nombre de cualquier rol activo en lugar de `Rule::enum`
- [x] T015 [P] [US1] Crear `resources/js/pages/Roles/Create.vue` (formulario con etiquetas visibles, errores por campo, selección de permisos, componentes del sistema de diseño)
- [x] T016 [US1] Reemplazar el listado de `resources/js/pages/Roles/Index.vue` por `Table` + `Pagination` + `useListFilters` (`q`, `status`) con estados vacío distinguidos y botón "Nuevo rol"

**Checkpoint**: US1 funcional por sí sola.

## Phase 4: User Story 2 - Inactivar y reactivar un rol (P1)

**Goal**: inactivar/reactivar roles con confirmación y reglas de protección.
**Independent Test**: inactivar un rol, ver estado y que no es asignable; reactivarlo.

### Tests

- [x] T017 [P] [US2] Ampliar `RoleManagementTest.php`: inactivar y reactivar, estado visible en listado, rol inactivo no asignable, Administrador no inactivable, con usuarios exige `confirm=true` (422 con `affected_users`), auditoría
- [x] T018 [P] [US2] Ampliar `RoleUseCasesTest.php` para `SetRoleStatus` (reglas y conservación de asignaciones)

### Implementation

- [x] T019 [US2] Crear `app/Modules/Identity/Application/SetRoleStatus.php` (protege Administrador, exige confirmación si hay usuarios, transacción, auditoría `role.deactivated`/`role.activated`)
- [x] T020 [US2] Añadir acción `status` en `RoleController.php` y ruta `PUT /roles/{id}/status` en `routes/web.php`
- [x] T021 [US2] Filtrar roles inactivos en las opciones de asignación del formulario de usuarios (`UserController.php` y vista de usuarios en `resources/js/pages/Users`)
- [x] T022 [US2] En `Roles/Index.vue`: columna de estado, `IconButton` con `aria-label` y tooltip para inactivar/reactivar, `AlertDialog` (warning) con el número de usuarios afectados; Administrador sin acción de inactivar

**Checkpoint**: US1 y US2 funcionan de forma independiente.

## Phase 5: User Story 3 - Bloqueo de login por rol inactivo (P1)

**Goal**: usuarios con rol inactivo no inician sesión; sesiones abiertas continúan hasta expirar.
**Independent Test**: inactivar el rol y comprobar login rechazado con mensaje genérico; reactivar y acceder.

### Tests

- [x] T023 [P] [US3] Pruebas Feature en `tests/Feature/Identity/RoleInactiveLoginTest.php`: login rechazado con el mismo mensaje que credenciales inválidas, sesión ya abierta no se interrumpe, login correcto tras reactivar, usuario con rol activo no afectado, auditoría `auth.failed`

### Implementation

- [x] T024 [US3] Modificar `Auth::attempt` en `app/Modules/Identity/Infrastructure/Http/AuthController.php` para excluir usuarios con rol inactivo (`whereDoesntHave('roles', is_active = false)`), conservando rate limiting y mensaje
- [x] T025 [US3] Verificar que `EnsureUserIsActive` no cierra sesiones por rol inactivo y dejar un comentario breve de la decisión (FR-008)

**Checkpoint**: las tres historias funcionan.

## Phase 6: Polish

- [x] T026 [P] Ejecutar `php artisan test --compact tests/Feature/Identity tests/Feature/Security tests/Unit/Identity` y corregir regresiones (p. ej. `RoleRouteMatrixTest`, `UserManagementTest`)
- [x] T027 Ejecutar `vendor/bin/pint --dirty --format agent` y los linters del frontend
- [x] T028 Revisar accesibilidad (WCAG 2.1 AA, objetivos táctiles 44px) y estados de carga en `Roles/Index.vue` y `Roles/Create.vue`
- [x] T029 Validar los escenarios de `quickstart.md`
- [x] T030 Crear la nota REQ en Obsidian (`Requirements/`) con la plantilla estándar y enlazarla con REQ-010 y REQ-008

## Dependencies & Execution Order

- Setup → Foundational (T003–T008) → historias → Polish.
- **US1** depende solo de Foundational. **US2** depende de Foundational y reutiliza `Roles/Index.vue` de US1 (T016 antes de T022). **US3** depende de T003 (columna `is_active`) y es independiente de las otras.
- Dentro de cada historia: pruebas primero (deben fallar), luego casos de uso, controlador y UI.
- `RoleController.php`, `routes/web.php` y `Roles/Index.vue` se tocan en varias historias: no paralelizar esas tareas.

## Parallel Examples

- Foundational: T004, T005, T007, T008 en paralelo tras T003.
- US1: T009, T010, T011, T012, T015 en paralelo.
- US3 puede desarrollarse en paralelo a US1/US2 tras T003.

## Implementation Strategy

1. **MVP**: Setup + Foundational + US1; validar creación y asignación.
2. Añadir US3 (cierra el efecto de seguridad) y US2 (gestión del ciclo de vida).
3. Polish y validación con `quickstart.md`.
