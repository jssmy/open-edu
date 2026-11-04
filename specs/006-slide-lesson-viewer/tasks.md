# Tasks: Visor de Lecciones en Modo Diapositivas y Módulo de Clases para Docentes

**Input**: Design documents from `/specs/006-slide-lesson-viewer/`  
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/classes-and-viewer.md`  
**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`
- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Target user story (US1, US2, US3, US4)

---

## Phase 1: Setup & Foundations

**Purpose**: Preparar políticas, permisos y utilidades compartidas.

- [x] T001 [P] Actualizar `App\Modules\Courses\Infrastructure\Policies\CoursePolicy.php` para restringir `manageContent` y `update` a docentes explícitamente asignados en `course_teacher`.
- [x] T002 [P] Ajustar `App\Modules\Scheduling\Infrastructure\Policies\SchedulePolicy.php` para asegurar que `viewOwn` valide la relación `teachers` o sesiones del docente.
- [x] T003 Ajustar la navegación en `resources/js/layouts/AppLayout.vue` para condicionar el enlace "Cursos" a los permisos `courses.view`/`courses.manage` e incluir el enlace a "Clases" (`/my/classes`) para docentes con `scheduling.view-own`.

**Checkpoint**: Base de permisos y navegación lista.

---

## Phase 2: User Story 1 - Visor de Lecciones en Modo Diapositiva (Priority: P1) 🎯 MVP

**Goal**: Transformar el visor de lecciones (`CourseViewer.vue`) para que presente una única lección por diapositiva activa con controles Anterior / Siguiente, navegación libre y drawer lateral con el temario completo.

### Tests for User Story 1
- [x] T004 [P] [US1] Crear test de integración en `tests/Feature/Study/SlideCourseViewerTest.php` que verifique que el estudiante matriculado puede cargar el curso, marcar lecciones y navegar por el contenido.

### Implementation for User Story 1
- [x] T005 [US1] Rediseñar `resources/js/pages/Study/CourseViewer.vue` implementando:
  - Estructura plana de diapositivas (`flattenedLessons`) a partir del árbol de unidades y lecciones.
  - Estado reactivo `activeLessonIndex` para proyectar una sola lección activa a la vez.
  - Barra de control de diapositiva con botones "Anterior" y "Siguiente" e indicador de posición ("Lección X de Y").
  - Atajos de teclado (`ArrowLeft` y `ArrowRight`) en `window` para cambiar de diapositiva.
  - Drawer / Sidebar lateral colapsable con el árbol de módulos para salto directo e indicador de progreso por lección.
  - Mantenimiento del botón "Marcar como completada" para estudiantes con feedback visual inmediato.

**Checkpoint**: El visor en diapositivas es completamente funcional para estudiantes y valida el MVP.

---

## Phase 3: User Story 2 & 4 - Módulo "Clases" para Docentes y Proyección de Curso (Priority: P1)

**Goal**: Proveer al docente su propio módulo de "Clases" (`/my/classes`) con tarjetas de cursos que enseña y acceso directo al visor de diapositivas para impartir o proyectar la clase.

### Tests for User Story 2
- [x] T006 [P] [US2] Crear test de características en `tests/Feature/Scheduling/TeacherClassesTest.php` verificando que un docente ve únicamente sus clases asignadas y puede abrir el visor de diapositivas correspondiente.

### Implementation for User Story 2 & 4
- [x] T007 [US2] Crear controlador `App\Modules\Scheduling\Infrastructure\Http\TeacherClassController.php` con métodos:
  - `index`: Lista las `CourseOffering` del docente autenticado con el curso asociado y estadísticas de unidades/lecciones.
  - `viewer`: Autoriza al docente vía `SchedulePolicy::viewOwn` y renderiza `Study/CourseViewer` con el curso asociado y flag `isTeacherView: true`.
- [x] T008 [US2] Registrar las rutas en `routes/web.php`:
  - `GET /my/classes` -> `TeacherClassController@index`
  - `GET /my/classes/{offering}/viewer` -> `TeacherClassController@viewer`
- [x] T009 [US2] Crear vista `resources/js/pages/Teacher/Classes.vue` utilizando `CourseCard.vue`, badges de horario y botón de acción "Entrar a clase / Ver lecciones".
- [x] T010 [US2] Adaptar `resources/js/pages/Study/CourseViewer.vue` para reconocer el modo docente (`isTeacherView`), adaptando las rutas de retorno (`backUrl`), ocultando el botón de completar lección y mostrando un indicador de "Modo Clase / Docente".

**Checkpoint**: El docente cuenta con su módulo de Clases y puede abrir y proyectar el visor de diapositivas idéntico al del estudiante.

---

## Phase 4: User Story 3 - Restricción y Seguridad de Cursos (Priority: P2)

**Goal**: Garantizar que un docente sin permisos no vea ni acceda a `/courses`, y si tiene permisos, solo pueda modificar sus cursos asignados.

### Tests for User Story 3
- [x] T011 [P] [US3] Crear test en `tests/Feature/Courses/TeacherCourseAccessTest.php` para verificar rechazo 403 al intentar editar un curso no asignado o acceder a `/courses` sin permiso.

### Implementation for User Story 3
- [x] T012 [US3] Verificar y reforzar en `App\Modules\Courses\Infrastructure\Http\CourseController.php` las autorizaciones `Gate::authorize('view', $course)` y `Gate::authorize('manageContent', $course)`.
- [x] T013 [US3] Actualizar botones de acción en `resources/js/pages/Courses/Index.vue` y `Show.vue` para condicionar las opciones de edición a `course.can_edit` (true si es admin/gestor o docente asignado).

**Checkpoint**: Módulos y acciones debidamente blindados por permisos.

---

## Phase 5: Polish & Validation

- [x] T014 Ejecutar tests con `php artisan test --filter=SlideCourseViewerTest` y `TeacherClassesTest`.
- [x] T015 Ejecutar linter con `vendor/bin/pint --dirty --format agent`.
- [x] T016 Ejecutar validación final según `specs/006-slide-lesson-viewer/quickstart.md`.

---

## Dependencies & Execution Order

1. **Phase 1 (Setup & Foundations)**: Desbloquea el resto de tareas.
2. **Phase 2 (US1)**: Implementa el nuevo visor de diapositivas en `CourseViewer.vue`.
3. **Phase 3 (US2 & US4)**: Construye el módulo de Clases docente y reutiliza `CourseViewer.vue`.
4. **Phase 4 (US3)**: Audita y refuerza el aislamiento de permisos en Cursos.
5. **Phase 5 (Polish)**: Pruebas automatizadas y validación general.

