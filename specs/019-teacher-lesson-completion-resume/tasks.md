# Tasks: Marcado de Lección Completada y Reanudación de Avance para Docentes

**Input**: Design documents from `/specs/019-teacher-lesson-completion-resume/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/lesson-completion.md](contracts/lesson-completion.md)
**Tests**: Se incluyen pruebas Feature Pest y Vitest para validar los flujos críticos de permisos, sincronización y reanudación.

## Format: `[ID] [P?] [Story] Description with file path`

- **[P]**: Ejecutable en paralelo (archivos independientes sin bloqueos)
- **[Story]**: Identificador de historia de usuario ([US1], [US2], [US3])
- Rutas de archivo exactas en cada tarea

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verificación de estructura base y preparación de dependencias

- [x] T001 Verificar entorno local y dependencias requeridas en `composer.json` y `package.json`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura de datos y contratos en el backend que bloquean las historias de usuario

**⚠️ CRITICAL**: Completar esta fase antes de implementar las historias de usuario

- [x] T002 Crear migración `database/migrations/2026_11_16_000001_add_course_offering_to_lesson_progress_table.php` agregando columna nullable `course_offering_id` con foreign key a `course_offerings(id) ON DELETE CASCADE` e índice único `unique(['course_offering_id', 'lesson_id'])`
- [x] T003 Actualizar contrato de repositorio `app/Modules/Progress/Domain/ProgressRepository.php` extendiendo `markLessonCompleted` para recibir `?int $courseOfferingId` y `string $action = 'complete'` y soportar consulta por oferta
- [x] T004 Implementar lógica de persistencia y toggle por oferta en `app/Modules/Progress/Infrastructure/EloquentProgressRepository.php` en los métodos `markLessonCompleted` y `courseStudyData`

**Checkpoint**: Base de datos y contratos de repositorio listos para recibir avance de grupos y participantes individuales.

---

## Phase 3: User Story 1 - Marcado docente en clases programadas y sincronización grupal (Priority: P1) 🎯 MVP

**Goal**: Permitir que el docente asignado marque y desmarque lecciones desde `/my/classes/{offering}/viewer`, reflejando automáticamente el avance para todos los alumnos del grupo en modo guiado.

**Independent Test**: Asignar un docente a una oferta de clase, marcar la lección 1 como completada; verificar que el estudiante inscrito en ese grupo ve la lección 1 como completada en `/my/courses/{course}` sin botón de marcado manual.

### Tests for User Story 1 ⚠️

- [x] T005 [P] [US1] Crear prueba Feature en `tests/Feature/Scheduling/TeacherClassLessonCompletionTest.php` para validar marcado, toggle/desmarcado y autorización de docentes en `/lessons/{lesson}/complete`
- [x] T006 [P] [US1] Crear prueba Feature en `tests/Feature/Progress/StudentClassProgressSyncTest.php` para validar que los alumnos de una oferta académica ven completadas las lecciones dictadas por el profesor en `/my/courses/{course}` sin botón de marcado propio

### Implementation for User Story 1

- [x] T007 [US1] Actualizar caso de uso `app/Modules/Progress/Application/CompleteLesson.php` soportando `?int $courseOfferingId` y `string $action = 'complete'`
- [x] T008 [US1] Actualizar endpoint `app/Modules/Progress/Infrastructure/Http/StudyController.php` (método `complete`) para validar que el usuario es docente asignado a la oferta y procesar el toggle de lección
- [x] T009 [US1] Actualizar `app/Modules/Scheduling/Infrastructure/Http/TeacherClassController.php` (método `viewer`) para consultar lecciones completadas de la oferta y pasar `is_completed` real, `progress` y `course_offering_id`
- [x] T010 [US1] Actualizar `resources/js/pages/Study/CourseViewer.vue` para habilitar el botón `"✓ Marcar completada"` / `"✓ Lección dictada (Desmarcar)"` en `isTeacherView` y ocultar el botón manual para estudiantes en clases programadas (`canMarkComplete: false`)

**Checkpoint**: User Story 1 completamente funcional y verificable como incremento MVP independiente.

---

## Phase 4: User Story 2 - Avance individual en cursos asíncronos / offline (Priority: P2)

**Goal**: Garantizar que en cursos sin horario ni programación académica (como capacitaciones de docentes autoinscritos), cada participante avance a su propio ritmo con progreso estrictamente personal.

**Independent Test**: Un usuario autoinscrito en un curso de capacitación marca lecciones en `/my/courses/{course}`; verificar que ningún otro participante ve alterado su progreso.

### Tests for User Story 2 ⚠️

- [x] T011 [P] [US2] Crear prueba Feature en `tests/Feature/Progress/AsyncCourseIndividualProgressTest.php` para validar avance individual e independiente en cursos con `course_offering_id = null`

### Implementation for User Story 2

- [x] T012 [US2] Asegurar en `app/Modules/Progress/Infrastructure/EloquentProgressRepository.php` y `StudyController.php` que las matrículas con `course_offering_id = null` operen en modo asíncrono individual y muestren el botón de completitud personal

**Checkpoint**: User Story 1 y User Story 2 operan de forma limpia e independiente según la modalidad del curso.

---

## Phase 5: User Story 3 - Reanudación automática en la última lección completada (Priority: P1)

**Goal**: Al abrir el visor (tanto docente en su clase como participante en su curso), la interfaz se posiciona automáticamente en la última lección marcada como completada (o en la primera si no hay avance).

**Independent Test**: Tener lecciones 1 y 2 completadas y lección 3 pendiente; al abrir el visor, la diapositiva activa debe ser la lección 2 sin interacción manual.

### Tests for User Story 3 ⚠️

- [x] T013 [P] [US3] Crear prueba Feature en `tests/Feature/Study/SlideViewerResumeTest.php` validando el estado inicial retornado por los controladores
- [x] T014 [P] [US3] Crear prueba de componente frontend en `resources/js/__tests__/CourseViewerResume.test.ts` verificando el cálculo de `activeLessonIndex` inicial

### Implementation for User Story 3

- [x] T015 [US3] Implementar algoritmo `findLastIndex((l) => l.is_completed)` en `resources/js/pages/Study/CourseViewer.vue` tanto para `isTeacherView` como para vista de estudiante

**Checkpoint**: Todas las historias de usuario implementadas y verificadas.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación final de calidad, linters y validación integral

- [x] T016 [P] Ejecutar `vendor/bin/pint --format agent` para formatear y verificar PSR-12 en todos los archivos PHP modificados
- [x] T017 [P] Ejecutar compilación de frontend con `npm run build` y suite de pruebas Vitest con `npm run test`
- [x] T018 Ejecutar suite completa de pruebas Pest (`vendor/bin/pest`) y validar escenarios de `quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Setup (Phase 1)**: Sin dependencias.
- **Foundational (Phase 2)**: Depende de Phase 1. Bloquea todas las historias de usuario.
- **User Stories (Phase 3+)**: Dependen de Phase 2.
  - US1 (P1): Implementación nuclear MVP de dictado docente y sincronización.
  - US2 (P2): Aseguramiento de modo asíncrono individual.
  - US3 (P1): Reanudación automática en la última lección completada.
- **Polish (Phase 6)**: Depende de la finalización de las historias de usuario.

### Parallel Opportunities
- T005 y T006 pueden escribirse en paralelo.
- T011, T013 y T014 son pruebas en archivos independientes y pueden crearse en paralelo.
- T016 y T017 pueden ejecutarse en paralelo antes de la validación integral T018.

---

## Implementation Strategy (MVP First)

1. Ejecutar **Fase 2 (Foundational)**: migración y soporte en repositorio para `course_offering_id`.
2. Ejecutar **Fase 3 (User Story 1 - MVP)**: pruebas y lógica para que el docente marque/desmarque en `/my/classes/{offering}/viewer` y los alumnos vean el avance del grupo.
3. Ejecutar **Fase 5 (User Story 3)**: reanudación en la última lección completada con `findLastIndex`.
4. Ejecutar **Fase 4 (User Story 2)**: verificación y soporte de cursos asíncronos individuales.
5. Ejecutar **Fase 6 (Polish)**: Pint, Vitest y Pest completo.

