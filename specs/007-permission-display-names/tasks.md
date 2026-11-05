# Tasks: Nombres Descriptivos de Permisos en Gestión de Roles

**Input**: Design documents from `specs/007-permission-display-names/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`
**Feature Directory**: `specs/007-permission-display-names`

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., [US1], [US2], [US3])
- Include exact file paths in descriptions

---

## Phase 1: Setup & Data Modeling (Shared Foundation)

**Purpose**: Definir las entidades de dominio y tipos compartidos para permisos con nombres descriptivos.

- [x] T001 [P] Crear el DTO inmutable `PermissionRecord` con campos `name`, `label`, `module` y `description` en `app/Modules/Identity/Domain/PermissionRecord.php`
- [x] T002 [P] Crear el catálogo canónico y fallback `PermissionDictionary` con mapeo de 13 permisos en `app/Modules/Identity/Domain/PermissionDictionary.php`
- [x] T003 Actualizar la interfaz de dominio `RolePermissionRepository` con el método `allPermissionRecords(): array` en `app/Modules/Identity/Domain/RolePermissionRepository.php`
- [x] T004 Implementar `allPermissionRecords()` en `app/Modules/Identity/Infrastructure/SpatieRolePermissionRepository.php` usando `PermissionDictionary::get()`
- [x] T005 [P] Definir el tipo TypeScript `PermissionItem` (`name: string, label: string, module: string, description?: string | null`) en `resources/js/types/index.ts`

**Checkpoint**: Base de dominio lista y tipada para abastecer los controladores y vistas.

---

## Phase 2: Foundational & Controller Pipeline

**Purpose**: Adaptar los endpoints de Inertia para enviar los permisos enriquecidos a la interfaz de usuario.

- [x] T006 Actualizar `RoleController@create` y `RoleController@index` para enviar `permissions` mapeados a arrays estructurados mediante `allPermissionRecords()` en `app/Modules/Identity/Infrastructure/Http/RoleController.php`
- [x] T007 [P] Crear pruebas de integración en `tests/Feature/Identity/RolePermissionsDisplayTest.php` validando que los endpoints de `/roles` y `/roles/create` devuelven la estructura con `label`, `module` y `description`

**Checkpoint**: Endpoints listos y probados para consumo del frontend.

---

## Phase 3: User Story 1 - Visualización de Nombres Amigables al Crear un Rol (Priority: P1) 🎯 MVP

**Goal**: Permitir al Administrador ver y asignar permisos identificados con nombres claros en lenguaje natural en `/roles/create`.

**Independent Test**: Visitar `/roles/create` y verificar que la grilla de selección de permisos muestra las etiquetas amigables en español (ej. "Gestión de cursos", "Gestión de usuarios") y crea el rol correctamente asociando sus claves técnicas.

- [x] T008 [US1] Actualizar la vista `resources/js/pages/Roles/Create.vue` para tipar la prop `permissions: PermissionItem[]` y mostrar `p.label` y `p.description` en los checkboxes de asignación
- [x] T009 [US1] Verificar y asegurar que `form.permissions` continúe almacenando y enviando los identificadores técnicos (`p.name`) al enviar `POST /roles` en `resources/js/pages/Roles/Create.vue`
- [x] T010 [P] [US1] Añadir o actualizar pruebas de frontend en `resources/js/__tests__/RolesCreate.test.ts` (o pruebas de renderizado equivalentes) para validar la visualización de labels amigables en el formulario de creación

**Checkpoint**: User Story 1 (MVP) completamente operativa e independientemente comprobable.

---

## Phase 4: User Story 2 - Visualización y Edición de Permisos en Roles Existentes (Priority: P2)

**Goal**: Permitir al Administrador visualizar y modificar los permisos de roles existentes en el modal de permisos con nombres legibles en `/roles`.

**Independent Test**: Abrir el modal "Permisos" en `/roles` para cualquier rol y verificar que la lista muestra los nombres amigables con su selección actual intacta, y que guardar cambios persiste la selección correctamente.

- [x] T011 [US2] Actualizar `resources/js/pages/Roles/Index.vue` para recibir `permissions: PermissionItem[]` y renderizar `p.label` y `p.description` en el modal de asignación de permisos
- [x] T012 [US2] Asegurar que `selectedPermissions` en `resources/js/pages/Roles/Index.vue` conserve la reactividad y envíe las claves técnicas `p.name` al endpoint `PUT /roles/{id}/permissions`
- [x] T013 [P] [US2] Añadir o actualizar pruebas en `resources/js/__tests__/RolesIndex.test.ts` para verificar la presencia de etiquetas amigables en el modal de permisos

**Checkpoint**: User Story 1 y 2 completamente funcionales e integradas.

---

## Phase 5: User Story 3 - Categorización y Claridad Contextual de Permisos (Priority: P3)

**Goal**: Organizar y agrupar visualmente los permisos por módulo temático (Usuarios, Seguridad, Cursos, Contenidos, etc.) en las interfaces de selección.

**Independent Test**: Abrir tanto `/roles/create` como el modal en `/roles` y comprobar que los permisos aparecen organizados por módulos o secciones temáticas claras en lugar de una lista plana desordenada.

- [x] T014 [US3] Crear un helper computado o composable para agrupar permisos por `module` en `resources/js/pages/Roles/Create.vue`
- [x] T015 [US3] Aplicar el agrupamiento modular en el modal de permisos de `resources/js/pages/Roles/Index.vue`
- [x] T016 [US3] Agregar badge o texto indicador del módulo y estilizado accesible acorde al sistema de diseño

**Checkpoint**: Interfaz categorizada y óptima para el crecimiento del catálogo de permisos.

---

## Phase 6: Polish & Verification

**Purpose**: Verificación cruzada, calidad de código y pruebas de regresión.

- [x] T017 Ejecutar formateo de código con Laravel Pint: `vendor/bin/pint --format agent`
- [x] T018 Ejecutar suite completa de pruebas de backend con Pest: `php artisan test --filter=Role`
- [x] T019 Ejecutar suite de pruebas de frontend: `npm run test`
- [x] T020 Validar los escenarios descritos en `specs/007-permission-display-names/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup/Domain)**: Sin dependencias, inicia de inmediato.
- **Phase 2 (Controller Pipeline)**: Depende de Phase 1.
- **Phase 3 (User Story 1 - MVP)**: Depende de Phase 2.
- **Phase 4 (User Story 2)**: Depende de Phase 2. Puede realizarse en paralelo con Phase 3 o secuencialmente.
- **Phase 5 (User Story 3)**: Depende de Phase 3 y Phase 4 (refinamiento de presentación en las vistas).
- **Phase 6 (Polish)**: Depende de todas las fases anteriores.

### Parallel Opportunities
- T001 y T002 pueden crearse simultáneamente en backend.
- T005 (TypeScript types) puede crearse en paralelo con el backend.
- T007 (Pest tests) puede escribirse en paralelo con la adaptación del controlador.
- T010 y T013 (Tests frontend) pueden ejecutarse en paralelo con las vistas respectivas.

---

## Implementation Strategy (MVP First)

1. **Paso 1**: Completar Fase 1 y Fase 2 (Infraestructura de dominio y pipeline de datos a Inertia).
2. **Paso 2**: Implementar Fase 3 (User Story 1 - Creación de roles con nombres legibles) como **MVP**.
3. **Paso 3**: Implementar Fase 4 (User Story 2 - Modal de edición de roles existentes).
4. **Paso 4**: Implementar Fase 5 (User Story 3 - Categorización por módulos).
5. **Paso 5**: Ejecutar Fase 6 (Pint, pruebas Pest, Vitest y validación de quickstart).
