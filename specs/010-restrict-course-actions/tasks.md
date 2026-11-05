# Tasks: Restricción de Acciones de Publicación y Eliminación de Cursos para Docentes

**Input**: Design documents from `specs/010-restrict-course-actions/` (`spec.md`, `plan.md`, `research.md`, `data-model.md`, `quickstart.md`)
**Related Requirement**: [[Requirements/REQ-020 - Restriccion de Acciones de Publicacion y Eliminacion de Cursos para Docentes]]

---

## Phase 1: Setup & Prerequisite Verification

**Purpose**: Confirm current branch, dependencies, and test environment

- [x] T001 Verify existing test suite baseline for teacher course access in `tests/Feature/Courses/TeacherCourseAccessTest.php`

---

## Phase 2: Foundational (Backend Protection & Tests)

**Purpose**: Define the failing tests that verify backend authorization boundary for course actions

- [x] T002 [P] [US1] Write feature test in `tests/Feature/Courses/TeacherCourseAccessTest.php` asserting that `canManageCourses` is `false` for an assigned teacher without `courses.manage` in `GET /courses/{id}`
- [x] T003 [P] [US2] Write feature test in `tests/Feature/Courses/TeacherCourseAccessTest.php` asserting that `PATCH /courses/{id}/status` returns 403 Forbidden for a teacher without `courses.manage`
- [x] T004 [P] [US2] Write feature test in `tests/Feature/Courses/TeacherCourseAccessTest.php` asserting that `DELETE /courses/{id}` returns 403 Forbidden for a teacher without `courses.manage`

---

## Phase 3: User Story 1 - Restringir Controles Administrativos en Vista de Curso (Priority: P1) 🎯 MVP

**Goal**: Evitar que el docente asignado vea o accione los paneles de Publicación y Eliminación en `/courses/{id}`

**Independent Test**: Acceder como docente a `/courses/{id}` y verificar que la prop `canManageCourses` sea `false`, ocultando la sección de cambio de estado y la "Zona de peligro".

- [x] T005 [US1] Update `canManageCourses` in `app/Modules/Courses/Infrastructure/Http/CourseController.php` method `show()` to strictly check `$request->user()->can('courses.manage')`
- [x] T006 [US1] Verify `resources/js/pages/Courses/Show.vue` ensures "Publicación" and "Zona de peligro" remain strictly governed by `v-if="canManageCourses"`

**Checkpoint**: User Story 1 lista. Un docente asignado puede ver la información y el contenido del curso, pero no los controles de publicación ni eliminación.

---

## Phase 4: User Story 2 - Blindaje y Verificación de Endpoints Backend (Priority: P1)

**Goal**: Garantizar que el backend rechace cualquier intento no autorizado de eliminación o cambio de estado

**Independent Test**: Ejecutar `vendor/bin/pest tests/Feature/Courses/TeacherCourseAccessTest.php` y comprobar que responde 403 en `status` y `destroy`.

- [x] T007 [US2] Confirm route middleware bindings in `routes/web.php` for `courses.destroy` and `courses.status` under `can:courses.manage`
- [x] T008 [US2] Run Pest test suite for `tests/Feature/Courses/TeacherCourseAccessTest.php` to ensure all authorization boundaries pass

**Checkpoint**: Backend blindado al 100% contra mutaciones no autorizadas en cursos.

---

## Phase 5: Polish & Quality Assurance

**Purpose**: Code formatting and complete regression test runs

- [x] T009 Format PHP code with `vendor/bin/pint --dirty --format agent`
- [x] T010 Run frontend build and vitest (`npm run build && npx vitest run`)
- [x] T011 Run full test suite for course management (`vendor/bin/pest tests/Feature/Courses/`)

---

## Dependencies & Execution Order

- **Phase 1 (Setup)** → **Phase 2 (Foundational Tests)** → **Phase 3 (Implementation US1)** → **Phase 4 (Validation US2)** → **Phase 5 (Polish)**
- T002, T003 y T004 se pueden escribir en paralelo en `tests/Feature/Courses/TeacherCourseAccessTest.php`.
- T005 corrige la causa raíz y desbloquea el pase de las pruebas.

