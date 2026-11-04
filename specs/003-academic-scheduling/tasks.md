---
description: "Tasks for programación académica"
---

# Tasks: Programación académica

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/scheduling-routes.md](contracts/scheduling-routes.md)

**Tests**: Incluidos (constitución, principio VII). Pest para backend, Vitest para frontend.

**Format**: `[ID] [P?] [Story] Descripción con ruta`

## Phase 1: Setup

- [x] T001 Obtener aprobación del usuario para añadir `@fullcalendar/core`, `@fullcalendar/vue3`, `@fullcalendar/daygrid`, `@fullcalendar/timegrid`, `@fullcalendar/interaction` (AGENTS.md; constitución XI) e instalarlas en `package.json`
- [x] T002 Crear estructura del módulo `app/Modules/Scheduling/{Domain,Application,Infrastructure}` y `app/Providers/SchedulingServiceProvider.php`; registrarlo en `bootstrap/providers.php`
- [x] T003 [P] Añadir permisos `scheduling.manage` y `scheduling.view-own` y asignarlos al administrador/docente en el seeder de roles existente

## Phase 2: Foundational

- [x] T004 Migraciones `academic_periods`, `classrooms`, `course_schedules`, `schedule_blocks` en `database/migrations/` según data-model.md
- [x] T005 [P] Modelos Eloquent y factories en `app/Modules/Scheduling/Infrastructure/Models/` (Period, Classroom, CourseSchedule, ScheduleBlock)
- [x] T006 [P] Dominio: `CourseSchedule`, `ScheduleBlock`, `Modality` y puerto `ScheduleRepository` en `app/Modules/Scheduling/Domain/`
- [x] T007 [P] Test unitario de `ConflictDetector` en `tests/Unit/Scheduling/ConflictDetectorTest.php` (traslape, bloques consecutivos, docente siempre, aula solo presencial, mismo periodo)
- [x] T008 Implementar `ConflictDetector` en `app/Modules/Scheduling/Domain/ConflictDetector.php`
- [x] T009 Implementar `EloquentScheduleRepository` y `SchedulePolicy` en `app/Modules/Scheduling/Infrastructure/`
- [x] T010 [P] Componente `resources/js/design-system/ScheduleCalendar.vue` (envuelve FullCalendar, semanal/diaria, selección/arrastre, locale es), `resources/js/design-system/SearchableSelect.vue` (dropdown reutilizable con buscador y máximo 5 elementos visibles con scroll) y composables `useScheduleCalendar.ts` / `useSearchableSelect.ts`
- [x] T011 [P] Tests Vitest en `resources/js/__tests__/ScheduleCalendar.test.ts` (emite `select` y `move`, renderiza eventos) y `resources/js/__tests__/SearchableSelect.test.ts` (máximo 5 visibles con scroll, búsqueda y selección)

**Checkpoint**: base lista.

## Phase 3: User Story 1 - Programar curso con docente, horario y modalidad (P1) 🎯 MVP

**Independent Test**: Crear programación presencial y virtual; el docente queda asignado al curso.

### Tests
- [x] T012 [P] [US1] Feature test en `tests/Feature/Scheduling/CreateScheduleTest.php`: crea presencial con aula, virtual sin aula, rechaza presencial sin aula, rechaza rango horario inválido, rechaza usuario sin rol docente, 403 sin `scheduling.manage`, sincroniza `course_teacher`
- [x] T013 [P] [US1] Feature test CRUD de aulas y periodos en `tests/Feature/Scheduling/ClassroomPeriodTest.php` (incluye impedir eliminar aula con programaciones vigentes)

### Implementation
- [x] T014 [US1] Caso de uso `CreateSchedule` (transacción, sincroniza `course_teacher`) en `app/Modules/Scheduling/Application/CreateSchedule.php`
- [x] T015 [P] [US1] Casos de uso `ManageClassrooms` y `ManagePeriods` en `app/Modules/Scheduling/Application/`
- [x] T016 [US1] Form Requests, controladores y rutas (`/scheduling`, `/scheduling/schedules`, `/scheduling/classrooms`, `/scheduling/periods`) en `app/Modules/Scheduling/Infrastructure/Http/` y `routes/web.php`
- [x] T017 [US1] Páginas `resources/js/pages/Scheduling/Index.vue`, `Classrooms.vue`, `Periods.vue` con formulario (docente, periodo, modalidad, aula, bloques) y calendario
- [x] T018 [US1] Enlace "Programación académica" en la navegación, visible con `scheduling.manage`

## Phase 4: User Story 2 - Evitar conflictos (P2)

**Independent Test**: Traslape de docente o aula devuelve 422 con mensaje claro.

- [x] T019 [P] [US2] Feature test en `tests/Feature/Scheduling/ScheduleConflictTest.php`: `teacher_conflict` (también virtual), `classroom_conflict`, consecutivos permitidos, virtual con docentes distintos permitido, periodos distintos sin conflicto
- [x] T020 [US2] Integrar `ConflictDetector` en `CreateSchedule` con bloqueo transaccional de docente/aula; devolver errores 422 según el contrato
- [x] T021 [US2] Mostrar errores de conflicto junto al campo en el formulario de `Scheduling/Index.vue`

## Phase 5: User Story 3 - Editar, reasignar y consultar (P3)

**Independent Test**: Cambiar docente reasigna `course_teacher`; filtros y horario docente funcionan.

- [x] T022 [P] [US3] Feature tests en `tests/Feature/Scheduling/UpdateDeleteScheduleTest.php`: editar/reasignar, cambio de presencial a virtual libera el aula, eliminar, periodo terminado bloqueado (`period_read_only`, FR-014), filtros curso/docente/aula, `/my/schedule` solo propio
- [x] T023 [US3] Casos de uso `UpdateSchedule` y `DeleteSchedule` (bloqueo por periodo finalizado, resincroniza `course_teacher`) en `app/Modules/Scheduling/Application/`
- [x] T024 [US3] Rutas PUT/DELETE, filtros y `GET /my/schedule`; página `resources/js/pages/Scheduling/MySchedule.vue`; eliminar con `AlertDialog`
- [x] T025 [US3] Mover bloques por arrastre en el calendario (`Scheduling/Index.vue`) con reversión si el backend rechaza

## Phase 6: Migración y retiro del flujo anterior (FR-008, FR-010)

- [x] T026 [P] Test de migración en `tests/Feature/Scheduling/LegacyAssignmentMigrationTest.php`: cada `course_teacher` genera programación sin periodo ni bloques
- [x] T027 Migración de datos de `course_teacher` a `course_schedules` (periodo nulo) en `database/migrations/`
- [x] T028 Página/lista "Por programar" (`GET /scheduling/pending`, paginada con `useListFilters`) en `resources/js/pages/Scheduling/Pending.vue`
- [x] T029 Retirar `AssignTeachers`, `AssignCourseTeachersRequest`, ruta `courses.teachers` y la acción del controlador; ajustar `TeacherScopeTest`/`TeacherUnassignTest` existentes
- [x] T030 En `resources/js/pages/Courses/Show.vue` reemplazar `TeacherPicker` por lista de docentes en solo lectura; eliminar `TeacherPicker.vue`, `useTeacherPicker.ts` y sus tests de la feature 002 (aprobado por el usuario)

## Phase 7: Polish

- [x] T031 [P] Estados vacíos (sin datos / sin resultados) y accesibilidad (aria-label, objetivos ≥44px) en las páginas de Scheduling
- [x] T032 Ejecutar `vendor/bin/pint --dirty --format agent`, `php artisan test --compact` y `npm test`
- [x] T033 Validar el flujo de [quickstart.md](quickstart.md) y actualizar el vault de Obsidian (REQ de programación académica)

## Dependencies

- Setup → Foundational → US1 → US2 → US3 → Migración/retiro → Polish.
- US2 depende de `CreateSchedule` (T014); US3 de US1; T029/T030 solo tras T027 y T028 (no perder asignaciones).
- T001 bloquea T010, T011 y T017 (FullCalendar).

## Parallel Example

```text
T005, T006, T007 en paralelo (archivos distintos) tras T004
T012 y T013 en paralelo; T019 y T022 en paralelo con su implementación previa de tests
```

## Implementation Strategy

1. **MVP**: Setup + Foundational + US1 → validar programación con aula/virtual.
2. Añadir US2 (conflictos) y probar.
3. Añadir US3 (edición, horarios, solo lectura de periodos).
4. Migrar datos y retirar el flujo anterior solo cuando "Por programar" esté operativo.
