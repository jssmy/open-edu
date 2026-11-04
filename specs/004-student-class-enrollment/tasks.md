---
description: "Task list for Módulo de programación académica (CourseOffering, sesiones) y matrícula de estudiantes"
---

# Tasks: Módulo de programación académica (CourseOffering, sesiones) y matrícula de estudiantes

**Input**: Design documents from `/specs/004-student-class-enrollment/`: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/enrollment-and-scheduling-routes.md](contracts/enrollment-and-scheduling-routes.md), [quickstart.md](quickstart.md)

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/enrollment-and-scheduling-routes.md`

**Tests**: Incluidos conforme al Principio VII de la Constitución (`Pest` para backend, `Vitest` para frontend).

**Organization**: Las tareas están agrupadas por historia de usuario (`US1`, `US2`, `US3`) para permitir implementación y validación incremental e independiente.

## Format: `[ID] [P?] [Story?] Description with file path`

- **[P]**: Ejecutable en paralelo (archivos distintos, sin dependencias bloqueantes en curso)
- **[Story]**: Historia de usuario a la que pertenece (`[US1]`, `[US2]`, `[US3]`, exclusivo para fases de historias de usuario)
- Todas las tareas incluyen rutas de archivo explícitas y restricciones literales de `data-model.md`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Configuración de permisos de rol y preparación de proveedores de servicios para `Scheduling` y `Enrollments`.

- [X] T001 Verificar y asegurar en `database/seeders/DatabaseSeeder.php` (y seeder de roles/permisos) que el permiso `enrollments.manage` esté asignado por defecto únicamente al rol Administrador (y no a otros roles) y que `scheduling.manage` / `scheduling.view-own` estén registrados
- [X] T002 [P] Actualizar bindings de repositorios y servicios de `Scheduling` y `Enrollments` en `app/Providers/SchedulingServiceProvider.php` y `app/Providers/EnrollmentsServiceProvider.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema de base de datos, entidades/enums de dominio, transiciones automáticas de estado y detector de conflictos por fecha/hora que bloquean a todas las historias.

**⚠️ CRITICAL**: Ninguna historia de usuario puede iniciarse hasta completar esta fase.

- [X] T003 Crear migración `database/migrations/2026_11_04_000000_evolve_scheduling_to_course_offerings_and_sessions.php` que elimine `academic_periods`, cree `course_offerings` (`id`, `course_id` FK cascadeOnDelete, `group_code` string(80) con `unique(['course_id', 'group_code'])`, `starts_on` date, `ends_on` date con `ends_on >= starts_on`, `max_students` unsigned integer `>= 1`, `modality` string(20) enum `in_person | virtual | hybrid`, `status` string(20) enum `draft | published | in_progress | finished | cancelled` con `index(['course_id', 'status'])`), cree `course_offering_teacher` (`course_offering_id` FK cascadeOnDelete, `teacher_id` FK `users` cascadeOnDelete, `unique(['course_offering_id', 'teacher_id'])`), cree `course_sessions` (`id`, `course_offering_id` FK cascadeOnDelete, `title` string(160), `session_date` date, `starts_at` string(5), `ends_at` string(5) con `ends_at > starts_at`, `teacher_id` FK `users` restrictOnDelete, `classroom_id` nullable FK `classrooms` restrictOnDelete, `meeting_url` string(500) nullable, `status` string(20) enum `scheduled | in_progress | finished | cancelled`) y añada `course_offering_id` nullable FK `course_offerings` restrictOnDelete con `index(['course_offering_id', 'status'])` en `enrollments`
- [X] T004 [P] Implementar enums y entidades de dominio `Modality` (`in_person`, `virtual`, `hybrid`), `OfferingStatus` (`draft`, `published`, `in_progress`, `finished`, `cancelled` con transición automática a `in_progress` cuando `starts_on <= hoy <= ends_on` y a `finished` cuando `hoy > ends_on` si no es `draft` ni `cancelled`), `SessionStatus` (`scheduled`, `in_progress`, `finished`, `cancelled` con transición automática según `session_date` + `starts_at`/`ends_at`), `CourseOffering` y `CourseSession` en `app/Modules/Scheduling/Domain/`
- [X] T005 [P] Crear modelos Eloquent `CourseOffering` y `CourseSession` y eliminar `AcademicPeriod` en `app/Modules/Scheduling/Infrastructure/Models/` y actualizar `app/Modules/Enrollments/Infrastructure/Models/Enrollment.php` con la relación `courseOffering` (`course_offering_id`)
- [X] T006 [P] Actualizar test unitario `tests/Unit/Scheduling/ConflictDetectorTest.php` para validar conflictos por `session_date` exacta (`starts_at < other.ends_at && ends_at > other.starts_at`), sesiones consecutivas permitidas (`ends_at == other.starts_at`), ignorar sesiones en estado `cancelled`, traslape de `teacher_id`, traslape de `classroom_id`, traslape de sesiones del estudiante y transición automática de estados `OfferingStatus` y `SessionStatus`
- [X] T007 Implementar `ConflictDetector` por fecha y hora (para docente, aula y estudiante, ignorando sesiones `cancelled`) y actualizar `ScheduleRepository` en `app/Modules/Scheduling/Domain/ConflictDetector.php` y `app/Modules/Scheduling/Domain/ScheduleRepository.php`
- [X] T008 Implementar `EloquentScheduleRepository` en `app/Modules/Scheduling/Infrastructure/EloquentScheduleRepository.php` soportando `CourseOffering`, un único docente asignado por grupo (`course_offering_teacher`), sesiones manuales (`course_sessions`), cálculo de estados automáticos y sincronización derivada de `course_teacher`

**Checkpoint**: Base de datos, dominio y detector de conflictos por fecha listos.

---

## Phase 3: User Story 1 - Gestionar programaciones académicas (CourseOffering) y sus sesiones individuales (Priority: P1) 🎯 MVP

**Goal**: Permitir al administrador crear y editar libremente múltiples `CourseOffering` por curso (código de grupo autogenerado, fechas de inicio y fin por meses sin bloqueos de periodo, capacidad máxima de estudiantes, modalidad presencial/virtual/híbrida, estado y un único docente asignado al grupo) y gestionar manualmente sus sesiones individuales (`Sessions`) por fecha, validando traslapes de docente y aula.

**Independent Test**: Crear un `CourseOffering` publicado con capacidad máxima de 30 estudiantes, 1 docente asignado y 3 sesiones individuales en fechas distintas; editar una sesión; verificar rechazo de múltiples docentes por grupo y de traslapes de docente/aula en la misma fecha, y ausencia de bloqueos por periodo.

### Tests for User Story 1

- [X] T009 [P] [US1] Actualizar/crear pruebas Feature en `tests/Feature/Scheduling/CreateScheduleTest.php`, `tests/Feature/Scheduling/ScheduleConflictTest.php`, `tests/Feature/Scheduling/UpdateDeleteScheduleTest.php` y `tests/Feature/Scheduling/ClassroomPeriodTest.php` para cubrir `CourseOffering` (autogeneración secuencial de `group_code` como `GRP-01`, `GRP-02` por curso, fechas, `max_students >= 1`, modalidad `in_person | virtual | hybrid`, asignación de un único docente por grupo y rechazo si se envían múltiples docentes, transición automática `in_progress`/`finished`), creación/edición manual de `CourseSession` por fecha, rechazo `teacher_conflict` y `classroom_conflict` en la misma fecha, sesiones `cancelled` que liberan horario y retiro de `/scheduling/periods`

### Implementation for User Story 1

- [X] T010 [US1] Actualizar casos de uso `SaveSchedule`, `DeleteSchedule`, `SaveCourseSession` y `DeleteCourseSession` (y retirar `ManagePeriods`) en `app/Modules/Scheduling/Application/` para gestionar `CourseOffering`, su único docente asignado (`teacher_id`), la creación/edición/eliminación manual de sesiones individuales (`CourseSession`), validación con `ConflictDetector` y sincronización de `course_teacher`
- [X] T011 [US1] Actualizar `SaveScheduleRequest`, `SchedulingController` y `CourseOffering::nextGroupCodeForCourse`, eliminar `PeriodController` / `SavePeriodRequest` y actualizar rutas en `app/Modules/Scheduling/Infrastructure/Http/` y `routes/web.php` (autogenerando `group_code` secuencialmente por `course_id`, exponiendo `next_group_code` por curso, validando un solo docente por grupo, retirando `/scheduling/periods` y soportando `/scheduling/offerings` y `/scheduling/schedules`)
- [X] T012 [P] [US1] Actualizar `resources/js/design-system/ScheduleCalendar.vue` y `resources/js/composables/useScheduleCalendar.ts` para renderizar sesiones con fecha exacta (`session_date` + `starts_at`/`ends_at`) en vistas mensual, semanal y diaria de FullCalendar en español, asignando un color distintivo por docente (sin leyenda de docentes fuera del calendario), mostrando en líneas separadas dentro de cada ítem del calendario el horario, curso/grupo, docente y aula, y emitiendo `eventClick` al hacer clic sobre una sesión programada
- [X] T013 [US1] Actualizar `resources/js/pages/Scheduling/Index.vue`, `resources/js/pages/Scheduling/Pending.vue`, `resources/js/pages/Scheduling/Classrooms.vue`, `resources/js/pages/Scheduling/MySchedule.vue` y eliminar `resources/js/pages/Scheduling/Periods.vue` para gestionar `CourseOffering` (selector de curso con buscador `SearchableSelect`, código de grupo autogenerado en modo solo lectura, fechas de inicio/fin, cupos máximos, modalidad presencial/virtual/híbrida, estado, selector de docente único con `SearchableSelect`), abrir el modal de edición al hacer clic sobre una programación en el calendario (`@event-click`), y creación/edición manual de sesiones individuales una por una (fecha, horario, aula/enlace y estado, sin campo de título/tema ni referencia a lecciones) con botón `Cancelar` en todos los modales

**Checkpoint**: User Story 1 (`CourseOffering` y `Sessions` manuales por fecha sin periodos) completamente funcional y verificable de forma independiente.

---

## Phase 4: User Story 2 - Explorar cursos disponibles, ver el modal de programación académica y matricular validando cupos y cruces (Priority: P1)

**Goal**: Ofrecer el módulo centralizado de Matrículas (`/enrollments`) protegido por permiso de rol (solo Administrador por defecto), donde se selecciona al estudiante activo, se exploran los cursos publicados disponibles, al hacer clic en un curso se abre el modal con sus `CourseOffering` (detallando grupo, docentes, fechas/días, horarios de sesiones, modalidad y cupos disponibles) y al matricular se validan transaccionalmente los cupos (`< max_students`) y la ausencia de cruce de horario entre sesiones.

**Independent Test**: Ingresar como administrador a `/enrollments`, seleccionar un estudiante activo, abrir el modal de un curso, verificar el rechazo al intentar matricular en un `CourseOffering` sin cupos (`offering_full`) o con sesión traslapada en la misma fecha y hora (`student_schedule_conflict`), y completar exitosamente la matrícula en un `CourseOffering` válido comprobando que las vacantes disponibles disminuyen en 1.

### Tests for User Story 2

- [X] T014 [P] [US2] Crear pruebas Feature en `tests/Feature/Enrollments/StudentClassEnrollmentTest.php` verificando: acceso exclusivo con permiso `enrollments.manage` (403 para otros roles y solo Administrador por defecto), listado de cursos disponibles y `CourseOffering` con cálculo de `available_spots`, matrícula exitosa en `CourseOffering` en estado `published` o `in_progress`, rechazo 422 por `offering_full` (`0` vacantes), rechazo 422 por `student_schedule_conflict` en la misma fecha y franja horaria (permitiendo sesiones consecutivas y sesiones `cancelled`), rechazo 422 por `offering_unavailable` (`draft`, `finished`, `cancelled`) y prevención de doble matrícula activa en el mismo curso

### Implementation for User Story 2

- [X] T015 [US2] Actualizar entidad `Enrollment` y puerto `EnrollmentRepository` en `app/Modules/Enrollments/Domain/Enrollment.php` y `app/Modules/Enrollments/Domain/EnrollmentRepository.php` para incluir `courseOfferingId` y consultas transaccionales con bloqueo (`lockForUpdate`) de cupos y sesiones activas del estudiante
- [X] T016 [US2] Actualizar `EloquentEnrollmentRepository` en `app/Modules/Enrollments/Infrastructure/EloquentEnrollmentRepository.php` y caso de uso `EnrollStudent` en `app/Modules/Enrollments/Application/EnrollStudent.php` para validar estudiante activo, estado del `CourseOffering` (`published | in_progress`), cupos disponibles (`active_count < max_students`), no duplicidad en el curso y ausencia de cruce de horario entre sesiones usando `ConflictDetector`, registrando auditoría (`enrollment.created`)
- [X] T017 [US2] Actualizar `StoreEnrollmentRequest`, `EnrollmentController`, `CourseEnrollmentController` y rutas (`GET /enrollments`, `POST /enrollments`) en `app/Modules/Enrollments/Infrastructure/Http/` y `routes/web.php`
- [X] T018 [US2] Crear la página `resources/js/pages/Enrollments/Index.vue` con selector de estudiante (`SearchableSelect`), catálogo de cursos disponibles con buscador, y el modal de Programación Académica al hacer clic en un curso detallando cada `CourseOffering`, docentes, modalidad, fechas/días, horarios de sesiones, cupos disponibles (`available_spots / max_students`), botón de matrícula y botón explícito `Cancelar`, y añadir el enlace "Matrículas" en la navegación (`resources/js/layouts/AppLayout.vue`) visible solo con `enrollments.manage`

**Checkpoint**: User Story 2 (catálogo de cursos, modal de programación académica y matrícula con validación de cupos y cruces) completamente funcional.

---

## Phase 5: User Story 3 - Visualizar el horario del estudiante en tiempo real y desmatricular de un curso (Priority: P2)

**Goal**: Mostrar en `/enrollments` el horario del estudiante actualizado en tiempo real (`ScheduleCalendar`) a medida que se matricula o desmatricula en algún curso, y permitir desmatricularlo con confirmación previa (`AlertDialog`), liberando de inmediato el cupo en el `CourseOffering`, conservando su progreso de lecciones y permitiendo re-matrícula posterior.

**Independent Test**: Matricular a un estudiante en un curso desde `/enrollments` y comprobar que sus sesiones aparecen en el calendario del estudiante en tiempo real; luego desmatricularlo del curso confirmando en el `AlertDialog` y verificar que las sesiones desaparecen del calendario, el cupo disponible se incrementa en 1 y puede volver a matricularse conservando su `lesson_progress`.

### Tests for User Story 3

- [X] T019 [P] [US3] Ampliar `tests/Feature/Enrollments/StudentClassEnrollmentTest.php` y actualizar `tests/Feature/EnrollmentTest.php` para probar la desmatrícula (`DELETE /enrollments/{enrollment}`), la liberación inmediata de la vacante en el `CourseOffering`, la actualización de las sesiones devueltas para el horario en tiempo real del estudiante, la preservación de `lesson_progress` y la re-matrícula posterior en la misma u otra programación del curso

### Implementation for User Story 3

- [X] T020 [US3] Crear caso de uso `UnenrollStudent` en `app/Modules/Enrollments/Application/UnenrollStudent.php` y exponer `DELETE /enrollments/{enrollment}` en `app/Modules/Enrollments/Infrastructure/Http/EnrollmentController.php` y `routes/web.php` (cambiando la matrícula a `cancelled`, liberando el cupo en el `CourseOffering`, conservando `lesson_progress` y registrando auditoría `enrollment.cancelled`)
- [X] T021 [US3] Integrar en `resources/js/pages/Enrollments/Index.vue` el panel de horario del estudiante en tiempo real usando `ScheduleCalendar.vue` (que se actualiza reactivamente tras cada matrícula o desmatrícula) y la lista de cursos matriculados con botón "Desmatricular" confirmado mediante `requestAlertDialog`
- [X] T022 [P] [US3] Actualizar `resources/js/pages/Courses/Enrollments.vue` y `resources/js/pages/Students/Show.vue` para mostrar las matrículas y el grupo (`CourseOffering`) en modo consulta con acceso directo a `/enrollments?student_id={id}` para usuarios con permiso `enrollments.manage`
- [X] T023 [P] [US3] Añadir prueba de componente Vitest en `resources/js/__tests__/ScheduleCalendar.test.ts` verificando la renderización de sesiones fechadas del horario del estudiante

**Checkpoint**: Todas las historias de usuario (`US1`, `US2`, `US3`) están completas y son verificables de forma independiente.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad de código, estados vacíos, accesibilidad, pruebas integrales y sincronización documental con Obsidian.

- [X] T024 [P] Verificar estados vacíos ("sin datos" vs. "sin resultados para los filtros"), etiquetas accesibles y objetivos táctiles `>= 44px` en `resources/js/pages/Enrollments/Index.vue` y `resources/js/pages/Scheduling/Index.vue`
- [X] T025 Ejecutar `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`, `npm test` y `npm run build` para garantizar cero errores de estilo, tipado, pruebas y compilación
- [X] T026 Validar todos los escenarios de [quickstart.md](quickstart.md) y actualizar el estado de la nota `[[REQ-014 - Modulo de Matricula de Estudiantes en Clases]]` en `/Users/jssmy/Documents/projects/obsidian-vault/Requirements/REQ-014 - Modulo de Matricula de Estudiantes en Clases.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: Sin dependencias; inicia de inmediato.
- **Phase 2 (Foundational)**: Depende de Phase 1; **BLOQUEA** todas las historias de usuario (`US1`, `US2`, `US3`).
- **Phase 3 (US1 - P1)**: Depende de Phase 2. Construye la gestión de `CourseOffering` y `Sessions`.
- **Phase 4 (US2 - P1)**: Depende de Phase 2 (y se integra con los `CourseOffering` de US1). Construye el módulo `/enrollments`, el modal de programación académica y la validación de cupos y cruces.
- **Phase 5 (US3 - P2)**: Depende de Phase 4 (`US2`). Añade la visualización del horario del estudiante en tiempo real en `/enrollments` y el flujo de desmatrícula y re-matrícula.
- **Phase 6 (Polish)**: Depende de completar `US1`, `US2` y `US3`.

### User Story Dependencies

- **User Story 1 (P1)**: Independiente tras completar Phase 2.
- **User Story 2 (P1)**: Utiliza las entidades `CourseOffering` y `CourseSession` creadas en Phase 2 / US1.
- **User Story 3 (P2)**: Extiende la pantalla y flujo de matrícula de US2 con el horario en tiempo real y la acción de desmatricular.

---

## Parallel Opportunities

```bash
# En Phase 2 (Foundational), una vez creada la migración T003:
Task T004: "Implementar enums y entidades de dominio en app/Modules/Scheduling/Domain/"
Task T005: "Crear modelos Eloquent CourseOffering y CourseSession en app/Modules/Scheduling/Infrastructure/Models/"
Task T006: "Actualizar test unitario en tests/Unit/Scheduling/ConflictDetectorTest.php"

# En Phase 3 (US1):
Task T009: "Pruebas Feature de Scheduling en tests/Feature/Scheduling/"
Task T012: "Actualizar ScheduleCalendar.vue y useScheduleCalendar.ts para sesiones por fecha"

# En Phase 5 (US3):
Task T022: "Actualizar Courses/Enrollments.vue y Students/Show.vue"
Task T023: "Prueba Vitest en resources/js/__tests__/ScheduleCalendar.test.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 + User Story 2)

1. Completar **Phase 1: Setup** y **Phase 2: Foundational**.
2. Completar **Phase 3: User Story 1** (`CourseOffering` y `Sessions` manuales por fecha) y validar con `php artisan test --compact --filter=Scheduling`.
3. Completar **Phase 4: User Story 2** (Módulo `/enrollments` con modal de programación y validación de cupos/cruces) y **Phase 5: User Story 3** (horario en tiempo real y desmatrícula).
4. Ejecutar **Phase 6: Polish** (`pint`, suite completa de tests Pest/Vitest y `npm run build`).

