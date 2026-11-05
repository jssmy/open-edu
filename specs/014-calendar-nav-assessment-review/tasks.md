# Tasks: Navegación Temporal en Calendarios y Detalle de Evaluaciones en Modo Consulta

**Input**: Design documents from `/specs/014-calendar-nav-assessment-review/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to ([US1], [US2], [US3])
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verificación de componentes base y rutas

- [x] T001 Verificar dependencias de FullCalendar en `resources/js/design-system/ScheduleCalendar.vue` y rutas en `routes/web.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Soporte de backend y routing para la nueva ruta `/assessments/{assessment}/review` y optimización de consultas SQL

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Implementar el método `review` en `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php` cargando la evaluación con sus preguntas y choices, el intento del estudiante (`student_id` pasado o del estudiante autenticado), sus respuestas (`attempt_answers`) y calculando aciertos si está completado.
- [x] T003 Registrar la ruta `Route::get('/assessments/{assessment}/review', [AssessmentController::class, 'review'])->name('assessments.review')` en `routes/web.php`.
- [x] T004 [P] Crear prueba Feature en `tests/Feature/Assessments/AssessmentReviewTest.php` para verificar que:
  - Un docente puede ver la revisión de cualquier estudiante con `?student_id={id}`.
  - Un estudiante puede ver la revisión de su propio intento.
  - Se retorna el intento, preguntas, opciones, respuesta elegida y corrección.
- [x] T005 [P] Optimizar consultas SQL en `app/Modules/Scheduling/Infrastructure/Http/TeacherClassController.php` y `app/Modules/Assessments/Infrastructure/Http/AssessmentController.php`:
  - Reemplazar ordenamiento `orderByRaw` por ordenamiento en colección (`sort()`) en memoria en `TeacherClassController`.
  - Aplicar eager-loading selectivo (`with(...)`) con proyección de columnas indispensables (`select(['id', 'course_id', ...])`) evitando traer campos pesados innecesarios.

**Checkpoint**: Foundation ready - backend y ruta de revisión disponibles para la interfaz con consultas optimizadas.

---

## Phase 3: User Story 1 - Controles de Navegación Temporal en el Calendario (Priority: P1) 🎯 MVP

**Goal**: Permitir la navegación temporal ("Anterior", "Hoy", "Siguiente") con indicador dinámico del período en `ScheduleCalendar.vue`.

**Independent Test**: En cualquier vista con calendario (`/scheduling`, `/my/schedule`), presionar "Siguiente" en vista mensual (avanza 1 mes), en vista semanal (avanza 1 semana), en vista diaria (avanza 1 día) y verificar que "Hoy" regresa a la fecha presente.

### Implementation for User Story 1

- [x] T006 [US1] Añadir referencia de FullCalendar (`ref="calendarRef"`) y estado reactivo para el período visible (`currentPeriodTitle`) en `resources/js/design-system/ScheduleCalendar.vue`.
- [x] T007 [US1] Conectar el hook `datesSet` en las opciones de FullCalendar de `resources/js/design-system/ScheduleCalendar.vue` para actualizar `currentPeriodTitle` con `view.title`.
- [x] T008 [US1] Implementar métodos `handlePrev`, `handleNext` y `handleToday` llamando a la API de FullCalendar (`getApi().prev()`, `getApi().next()`, `getApi().today()`) en `resources/js/design-system/ScheduleCalendar.vue`.
- [x] T009 [US1] Renderizar la barra de navegación (botones "Anterior", "Hoy", "Siguiente" y el título del período visible) en el encabezado de `resources/js/design-system/ScheduleCalendar.vue`.

**Checkpoint**: User Story 1 lista y verificable de manera autónoma.

---

## Phase 4: User Story 2 - Detalle de Evaluación en Modo Consulta (Solo Lectura) (Priority: P2)

**Goal**: Crear la vista `Assessments/Review.vue` de solo lectura y conectar las tarjetas de evaluación en `/students/{studentId}` hacia `/assessments/{id}/review?student_id={studentId}`.

**Independent Test**: Ingresar a `/students/2` (o cualquier alumno), hacer clic en una evaluación y visualizar la vista de revisión con enunciados, opciones y respuestas del alumno en modo lectura sin campos editables.

### Implementation for User Story 2

- [x] T010 [US2] Crear la vista Inertia `resources/js/pages/Assessments/Review.vue` implementando el renderizado de preguntas, opciones, alternativa marcada por el alumno, y (si está terminado) badge de correcto/incorrecto y puntaje obtenido en modo solo lectura.
- [x] T011 [US2] Actualizar `resources/js/pages/Students/Show.vue` para que cada evaluación en el listado de cursos tenga un enlace hacia `/assessments/${assessment.id}/review?student_id=${student.id}`.

**Checkpoint**: User Story 2 completa. La revisión es accesible desde la ficha de estudiante en modo consulta.

---

## Phase 5: User Story 3 - Acción de Continuar Rendición para Evaluaciones en Curso (Priority: P3)

**Goal**: Permitir que un estudiante con una evaluación en curso acceda al botón para reanudar el examen en `/assessments/{id}/take`.

**Independent Test**: Entrar como estudiante a una evaluación con intento `in_progress` en `/assessments/{id}/review`. Constatar la presencia del botón "Continuar evaluación" y que redirige a `/assessments/{id}/take`.

### Implementation for User Story 3

- [x] T012 [US3] Agregar un banner de alerta con el botón "Continuar evaluación" / "Rendir examen" en `resources/js/pages/Assessments/Review.vue` cuando `isStudent` sea verdadero y `attempt.status === 'in_progress'`.

**Checkpoint**: Todas las historias de usuario implementadas y funcionales.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación de linters, calidad, pruebas y sincronización en Obsidian

- [x] T013 [P] Ejecutar linters y formateadores (`vendor/bin/pint --dirty --format agent`, `vue-tsc --noEmit`).
- [x] T014 [P] Crear o actualizar pruebas de frontend en `resources/js/__tests__/ScheduleCalendar.test.ts` y `resources/js/__tests__/AssessmentReview.test.ts`.
- [x] T015 Ejecutar la guía de validación `specs/014-calendar-nav-assessment-review/quickstart.md`.
- [x] T016 Actualizar el estado del requerimiento en Obsidian [[REQ-025 - Navegacion Temporal en Calendarios y Detalle de Evaluaciones en Modo Consulta]].

---

## Completion Status

Todas las tareas de implementación (T001 a T016) han sido completadas y verificadas con pruebas automatizadas (`vitest`, `pest`, `vue-tsc`, `pint`).


---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias.
- **Foundational (Phase 2)**: Bloquea US2 y US3. US1 (calendario) puede ejecutarse en paralelo o justo después.
- **User Story 1 (Phase 3 - P1)**: Componente UI independiente en `ScheduleCalendar.vue`.
- **User Story 2 (Phase 4 - P2)**: Depende de Foundational (backend de review).
- **User Story 3 (Phase 5 - P3)**: Extiende la vista creada en US2.
- **Polish (Phase 6)**: Ejecución final de linters, tests y documentación.

---

## Implementation Strategy

### MVP First (User Story 1 Only)
1. Completar T005 - T008 para tener la navegación mensual/semanal/diaria en los calendarios.
2. Validar que los botones "Anterior", "Siguiente" y "Hoy" avanzan de acuerdo a la vista seleccionada.

### Incremental Delivery (User Story 2 & 3)
1. Completar T002 - T004 (backend de revisión y test).
2. Completar T009 y T010 (vista Review y enlaces desde ficha de estudiante).
3. Completar T011 (botón de continuar examen en curso).
4. Completar verificación final (T012 - T015).
