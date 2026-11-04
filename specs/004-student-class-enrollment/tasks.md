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

**Goal**: Permitir al administrador crear y editar programaciones (`CourseOffering`) de cursos con código autogenerado secuencialmente (`GRP-01`, `GRP-02`, ...), fechas de inicio y fin, capacidad máxima, modalidad y un único docente asignado, agregando y editando manualmente sesiones individuales por fecha (validando traslapes de docente y aula) y distinguiendo en el calendario (`ScheduleCalendar`) a cada docente por color propio con los datos en líneas separadas y clic para editar.

**Independent Test**: Crear una programación con 1 docente y 3 sesiones individuales en fechas distintas, comprobar la distinción visual de docente/horario/aula en el calendario, hacer clic para editarla y verificar el rechazo ante traslapes con otras programaciones.

### Tests for User Story 1

- [X] T009 [P] [US1] Actualizar pruebas Feature de `Scheduling` en `tests/Feature/Scheduling/CreateScheduleTest.php`, `tests/Feature/Scheduling/UpdateDeleteScheduleTest.php` y `tests/Feature/Scheduling/ScheduleConflictTest.php` adaptándolas a `CourseOffering` con un solo docente, sesiones individuales por fecha exacta, autogeneración de `group_code` y validación de conflictos
- [X] T010 [P] [US1] Actualizar `tests/Feature/Scheduling/ClassroomPeriodTest.php` para retirar pruebas de `academic_periods` y preservar la gestión del catálogo de aulas

### Implementation for User Story 1

- [X] T011 [US1] Actualizar casos de uso `SaveSchedule`, `DeleteSchedule`, `SaveCourseSession`, `DeleteCourseSession` en `app/Modules/Scheduling/Application/` adaptándolos a `CourseOffering` y `CourseSession` (sin campo título/tema ni referencia a lecciones)
- [X] T012 [P] [US1] Actualizar `resources/js/design-system/ScheduleCalendar.vue` y `resources/js/composables/useScheduleCalendar.ts` para soportar sesiones por fecha exacta, asignación de color por docente sin leyenda externa, visualización en líneas separadas de curso/grupo, docente y aula, y emisión del evento `@event-click`
- [X] T013 [US1] Actualizar `resources/js/pages/Scheduling/Index.vue` para gestionar `CourseOffering` (con `SearchableSelect` para curso y docente único, código de grupo autogenerado, fechas inicio/fin, cupos, modalidad y sesiones manuales), apertura del modal de edición al hacer clic sobre un bloque del calendario y botón `Cancelar`

**Checkpoint**: User Story 1 completamente funcional y verificada.

---

## Phase 4: User Story 2 - Explorar cursos disponibles, ver el modal de programación académica y matricular validando cupos y cruces (Priority: P1)

**Goal**: Ofrecer el módulo centralizado de Matrículas (`/enrollments`) protegido por permiso de rol (solo Administrador por defecto), donde se selecciona al estudiante activo, se exploran los cursos publicados disponibles en una cuadrícula paginada con tarjeta reutilizable `CourseCard` (ordenando primero aquellos con cupos disponibles), al hacer clic en un curso se abre el modal con sus `CourseOffering` (detallando grupo, docentes, fechas/días, horarios de sesiones, modalidad y cupos disponibles) y al matricular se validan transaccionalmente los cupos (`< max_students`) y la ausencia de cruce de horario entre sesiones.

**Independent Test**: Ingresar como administrador a `/enrollments`, seleccionar un estudiante activo, verificar el ordenamiento por cupos disponibles en el catálogo de tarjetas paginadas, abrir el modal de un curso, verificar el rechazo al intentar matricular en un `CourseOffering` sin cupos (`offering_full`) o con sesión traslapada en la misma fecha y hora (`student_schedule_conflict`), y completar exitosamente la matrícula en un `CourseOffering` válido comprobando que las vacantes disponibles disminuyen en 1.

### Tests for User Story 2

- [X] T014 [P] [US2] Crear pruebas Feature en `tests/Feature/Enrollments/StudentClassEnrollmentTest.php` verificando: acceso exclusivo con permiso `enrollments.manage` (403 para otros roles y solo Administrador por defecto), listado de cursos disponibles y `CourseOffering` con cálculo de `available_spots`, ordenamiento prioritario por cupos disponibles y paginación en servidor, matrícula exitosa en `CourseOffering` en estado `published` o `in_progress`, rechazo 422 por `offering_full` (`0` vacantes), rechazo 422 por `student_schedule_conflict` en la misma fecha y franja horaria (permitiendo sesiones consecutivas y sesiones `cancelled`), rechazo 422 por `offering_unavailable` (`draft`, `finished`, `cancelled`) y prevención de doble matrícula activa en el mismo curso

### Implementation for User Story 2

- [X] T015 [US2] Actualizar entidad `Enrollment` y puerto `EnrollmentRepository` en `app/Modules/Enrollments/Domain/Enrollment.php` y `app/Modules/Enrollments/Domain/EnrollmentRepository.php` para incluir `courseOfferingId` y consultas transaccionales con bloqueo (`lockForUpdate`) de cupos y sesiones activas del estudiante
- [X] T016 [US2] Actualizar `EloquentEnrollmentRepository` en `app/Modules/Enrollments/Infrastructure/EloquentEnrollmentRepository.php` y caso de uso `EnrollStudent` en `app/Modules/Enrollments/Application/EnrollStudent.php` para validar estudiante activo, estado del `CourseOffering` (`published | in_progress`), cupos disponibles (`active_count < max_students`), no duplicidad en el curso y ausencia de cruce de horario entre sesiones usando `ConflictDetector`, registrando auditoría (`enrollment.created`)
- [X] T017 [US2] Actualizar `StoreEnrollmentRequest`, `EnrollmentController`, `CourseEnrollmentController` y rutas (`GET /enrollments`, `POST /enrollments`) en `app/Modules/Enrollments/Infrastructure/Http/` y `routes/web.php`
- [X] T018 [US2] Crear la página `resources/js/pages/Enrollments/Index.vue` con selector de estudiante (`SearchableSelect`), catálogo de cursos disponibles con buscador, y el modal de Programación Académica al hacer clic en un curso detallando cada `CourseOffering`, docentes, modalidad, fechas/días, horarios de sesiones, cupos disponibles (`available_spots / max_students`), botón de matrícula y botón explícito `Cancelar`, y añadir el enlace "Matrículas" en la navegación (`resources/js/layouts/AppLayout.vue`) visible solo con `enrollments.manage`
- [X] T018b [US2] Implementar el componente reutilizable de tarjeta de curso `resources/js/components/courses/CourseCard.vue` (compartido entre `Courses/Index.vue` y `Enrollments/Index.vue`) y actualizar `EnrollmentController.php` para paginar en servidor (`Pagination`) y ordenar prioritariamente primero los cursos y grupos que cuentan con cupos disponibles (`total_available_spots > 0`)

**Checkpoint**: User Story 2 (catálogo de cursos con `CourseCard` reutilizable, ordenamiento por cupos, paginación, modal de programación académica y matrícula con validación de cupos y cruces) completamente funcional.

---

## Phase 5: User Story 3 - Visualizar el horario del estudiante en tiempo real y desmatricular de un curso (Priority: P2)

**Goal**: Mostrar en `/enrollments` el horario del estudiante actualizado en tiempo real a ancho completo (`ScheduleCalendar`) a medida que se matricula o desmatricula en algún curso, mantener la lista superior de cursos matriculados en un contenedor acotado (`max-h` con scroll vertical interno) y permitir desmatricular al estudiante previa confirmación (`AlertDialog`) tanto desde el botón del listado como al hacer clic directamente sobre cualquier bloque de programación académica en el calendario, liberando de inmediato el cupo en el `CourseOffering`, conservando su progreso de lecciones y permitiendo re-matrícula posterior.

**Independent Test**: Matricular a un estudiante en un curso desde `/enrollments` y comprobar que sus sesiones aparecen en el horario a ancho completo del estudiante en tiempo real; luego hacer clic en un bloque del calendario o en "Desmatricular" en la lista compacta, confirmar en el `AlertDialog` y verificar que las sesiones desaparecen del calendario, el cupo disponible se incrementa en 1 y puede volver a matricularse conservando su `lesson_progress`.

### Tests for User Story 3

- [X] T019 [P] [US3] Ampliar `tests/Feature/Enrollments/StudentClassEnrollmentTest.php` y actualizar `tests/Feature/EnrollmentTest.php` para probar la desmatrícula (`DELETE /enrollments/{enrollment}`), la liberación inmediata de la vacante en el `CourseOffering`, la actualización de las sesiones devueltas para el horario en tiempo real del estudiante, la preservación de `lesson_progress` y la re-matrícula posterior en la misma u otra programación del curso
- [X] T019b [P] [US3] Ampliar pruebas en `resources/js/__tests__/ScheduleCalendar.test.ts` y/o pruebas e2e en `tests/e2e/enrollments.spec.ts` para verificar la apertura del modal de confirmación de desmatrícula al hacer clic sobre un bloque del horario del estudiante en `/enrollments`

### Implementation for User Story 3

- [X] T020 [US3] Crear caso de uso `UnenrollStudent` en `app/Modules/Enrollments/Application/UnenrollStudent.php` y exponer `DELETE /enrollments/{enrollment}` en `app/Modules/Enrollments/Infrastructure/Http/EnrollmentController.php` y `routes/web.php` (cambiando la matrícula a `cancelled`, liberando el cupo en el `CourseOffering`, conservando `lesson_progress` y registrando auditoría `enrollment.cancelled`)
- [X] T021 [US3] Integrar en `resources/js/pages/Enrollments/Index.vue` el horario del estudiante a ancho completo usando `ScheduleCalendar.vue` y el listado de cursos matriculados compacto con altura máxima acotada y scroll interno (`max-h-44 overflow-y-auto`) con botón "Desmatricular" confirmado mediante `requestAlertDialog`
- [X] T021b [US3] Conectar el evento `@event-click` en `ScheduleCalendar` dentro de `resources/js/pages/Enrollments/Index.vue` para que al hacer clic en un bloque de programación académica del horario del estudiante se abra el diálogo de confirmación (`requestAlertDialog`) preguntando si desea desmatricular al estudiante de ese curso/grupo y proceda a ejecutar la desmatrícula
- [X] T022 [P] [US3] Actualizar `resources/js/pages/Courses/Enrollments.vue` y `resources/js/pages/Students/Show.vue` para mostrar las matrículas y el grupo (`CourseOffering`) en modo consulta con acceso directo a `/enrollments?student_id={id}` para usuarios con permiso `enrollments.manage`
- [X] T023 [P] [US3] Añadir prueba de componente Vitest en `resources/js/__tests__/ScheduleCalendar.test.ts` verificando la renderización de sesiones fechadas del horario del estudiante y la emisión de `@event-click`

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
- **Phase 4 (US2 - P1)**: Depende de Phase 2 (y se integra con los `CourseOffering` de US1). Construye el módulo `/enrollments`, el catálogo paginado con `CourseCard`, modal de programación académica y validación de cupos/cruces.
- **Phase 5 (US3 - P2)**: Depende de Phase 4 (`US2`). Añade el horario del estudiante a ancho completo en `/enrollments`, contenedor acotado para cursos matriculados y desmatrícula por botón o clic en el bloque del calendario.
- **Phase 6 (Polish)**: Depende de completar `US1`, `US2` y `US3`.

### User Story Dependencies

- **User Story 1 (P1)**: Independiente tras completar Phase 2.
- **User Story 2 (P1)**: Utiliza las entidades `CourseOffering` y `CourseSession` creadas en Phase 2 / US1.
- **User Story 3 (P2)**: Extiende la pantalla y flujo de matrícula de US2 con el horario en tiempo real y la acción de desmatricular por interfaz gráfica.

---

## Parallel Opportunities

```bash
# En User Story 3 (US3):
Task T019b: "Pruebas de clic en bloque del horario para desmatrícula"
Task T021b: "Conectar @event-click en ScheduleCalendar dentro de Enrollments/Index.vue"
```

---

## Implementation Strategy

### MVP First (User Story 1 + User Story 2)

1. Completar **Phase 1: Setup** y **Phase 2: Foundational**.
2. Completar **Phase 3: User Story 1** (`CourseOffering` y `Sessions` manuales por fecha) y validar con `php artisan test --compact --filter=Scheduling`.
3. Completar **Phase 4: User Story 2** (Módulo `/enrollments` con catálogo paginado, `CourseCard`, modal de programación y validación de cupos/cruces) y **Phase 5: User Story 3** (horario a ancho completo, lista acotada de matrículas y desmatrícula por botón o clic en bloque).
4. Ejecutar **Phase 6: Polish** (`pint`, suite completa de tests Pest/Vitest y `npm run build`).
