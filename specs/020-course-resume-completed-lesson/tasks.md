---
description: "Task list for feature: Posicionamiento por Defecto en la Última Lección Completada"
---

# Tasks: Posicionamiento por Defecto en la Última Lección Completada al Entrar al Curso con Avance

**Input**: Design documents from `specs/020-course-resume-completed-lesson/` (`spec.md`, `plan.md`, `research.md`, `data-model.md`, `contracts/`, `quickstart.md`)  
**Obsidian Reference**: `[[REQ-036]]`  
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/resume-position.md`

## Format: `- [ ] [TaskID] [P?] [Story?] Description with file path`

- **[P]**: Can run in parallel (different files, no blocking dependencies)
- **[Story]**: User story identifier ([US1], [US2], [US3])
- Every task includes explicit file paths.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verificación del entorno de pruebas y herramientas de compilación.

- [X] T001 Verify Vitest and Node 22 test runner execution environment with `package.json`
- [X] T002 Verify backend test suite baseline in `tests/Feature/Study/SlideViewerResumeTest.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Definir los contratos de tipos TypeScript y la firma de la función de resolución previa a la integración del componente.

- [X] T003 [P] Document pure helper contract and type definitions for `resolveLastCompletedLessonIndex` in `resources/js/pages/Study/CourseViewer.vue`

**Checkpoint**: Foundation ready - User Story implementation and tests can begin.

---

## Phase 3: User Story 1 - Reanudación en la última lección completada (Priority: P1) 🎯 MVP

**Goal**: Posicionar automáticamente el visor de contenidos (`CourseViewer.vue`) en la última lección completada cuando el curso registra progreso (> 0%).

**Independent Test**:
- Cargar un curso con lecciones 1 y 2 completadas de 3 totales.
- Verificar que el visor inicia en la lección 2 (Diapositiva 2 / 3) y resalta el tema en el drawer temario.

### Tests for User Story 1 ⚠️
- [X] T004 [P] [US1] Add unit test in `resources/js/__tests__/CourseViewerResume.test.ts` verifying that a student or teacher entering a course with lessons 1 and 2 completed lands on slide 2
- [X] T005 [P] [US1] Add unit test in `resources/js/__tests__/CourseViewerResume.test.ts` verifying 100% completion lands on the final slide of the course

### Implementation for User Story 1
- [X] T006 [US1] Implement `resolveLastCompletedLessonIndex(lessons: FlattenedLesson[]): number` in `resources/js/pages/Study/CourseViewer.vue` using reverse iteration (`for (let i = lessons.length - 1; i >= 0; i--)`)
- [X] T007 [US1] Initialize `activeLessonIndex = ref(resolveLastCompletedLessonIndex(flattenedLessons.value))` in `resources/js/pages/Study/CourseViewer.vue`
- [X] T008 [US1] Ensure drawer and navigation buttons reflect the resolved active lesson on load in `resources/js/pages/Study/CourseViewer.vue`

**Checkpoint**: User Story 1 fully functional and independently testable (MVP complete).

---

## Phase 4: User Story 2 - Robustez y compatibilidad multiplataforma del cálculo de reanudación (Priority: P1)

**Goal**: Garantizar compatibilidad cross-browser (eliminando `findLastIndex`) y reactividad adecuada ante cambios de curso en la SPA de Inertia.

**Independent Test**:
- Simular entorno sin soporte nativo de `findLastIndex` en Vitest y comprobar que el visor funciona sin lanzar excepción.
- Cambiar la prop `course` reactivamente y verificar que `activeLessonIndex` se actualiza al índice correspondiente al nuevo curso.

### Tests for User Story 2 ⚠️
- [X] T009 [P] [US2] Add unit test in `resources/js/__tests__/CourseViewerResume.test.ts` testing `resolveLastCompletedLessonIndex` with non-linear completions (e.g. lessons 0 and 2 completed)
- [X] T010 [P] [US2] Add unit test in `resources/js/__tests__/CourseViewerResume.test.ts` verifying reactive course update when `props.course.id` changes via Inertia navigation

### Implementation for User Story 2
- [X] T011 [US2] Add `watch` on `() => props.course.id` in `resources/js/pages/Study/CourseViewer.vue` to recalculate `activeLessonIndex.value = resolveLastCompletedLessonIndex(flattenedLessons.value)` on client navigation
- [X] T012 [US2] Add bounds-check verification inside `onMounted` hook in `resources/js/pages/Study/CourseViewer.vue` to safeguard against asynchronous hydration mismatch

**Checkpoint**: User Story 2 robust and compatible across all browser engines.

---

## Phase 5: User Story 3 - Cursos sin avance y estabilidad en marcado en vivo (Priority: P2)

**Goal**: Asegurar que cursos con 0% de avance inicien en la lección 1 (índice 0) y que al pulsar "✓ Marcar completada" la pantalla permanezca en la lección actual.

**Independent Test**:
- Entrar a un curso con 0 lecciones completadas y comprobar que la diapositiva es la 1.
- Pulsar "✓ Marcar completada" en la diapositiva actual y comprobar que no salta automáticamente de lección.

### Tests for User Story 3 ⚠️
- [X] T013 [P] [US3] Add unit test in `resources/js/__tests__/CourseViewerResume.test.ts` verifying empty course / 0% progress stays on slide 1 (index 0)
- [X] T014 [P] [US3] Add unit test in `resources/js/__tests__/CourseViewerResume.test.ts` verifying that toggling completion keeps current slide index unchanged

### Implementation for User Story 3
- [X] T015 [US3] Verify and safeguard `toggleCompletion` in `resources/js/pages/Study/CourseViewer.vue` so that asynchronous Inertia reload retains `activeLessonIndex.value`
- [X] T016 [US3] Validate fallback to index 0 when `lessons.length === 0` or no lesson has `is_completed: true` in `resources/js/pages/Study/CourseViewer.vue`

**Checkpoint**: All user stories functionally complete and verified.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación integral, formato de código y pruebas de regresión.

- [X] T017 Run Vitest suite `resources/js/__tests__/CourseViewerResume.test.ts` with Node 22
- [X] T018 Run Pest feature tests `tests/Feature/Study/SlideViewerResumeTest.php` and `tests/Feature/Progress/StudentClassProgressSyncTest.php`
- [X] T019 Run frontend build `npm run build` with Node 22
- [X] T020 Run code formatting `vendor/bin/pint --format agent`
- [X] T021 Update Obsidian requirement note `[[REQ-036]]` marking acceptance criteria as completed

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup)**: Can start immediately.
- **Phase 2 (Foundational)**: Depends on Phase 1; establishes contracts.
- **Phase 3 (US1 - MVP)**: Depends on Phase 2; implements primary resume capability.
- **Phase 4 (US2 - Robustness)**: Depends on Phase 3; adds cross-browser resilience and Inertia reactivity.
- **Phase 5 (US3 - Zero progress & stability)**: Depends on Phase 4; ensures zero-progress defaults and slide stability.
- **Phase 6 (Polish)**: Depends on all user stories completing.

### Parallel Opportunities
- T004 & T005 can run in parallel.
- T009 & T010 can run in parallel.
- T013 & T014 can run in parallel.
- T017 & T018 can run in parallel.

---

## Implementation Strategy (MVP First)

1. Complete Phase 1 & 2.
2. Complete Phase 3 (US1 - MVP): deliver the backwards iteration helper and initial index assignment. Validate with Vitest.
3. Complete Phase 4 (US2): add `watch(() => props.course.id)` and eliminate browser incompatibilities.
4. Complete Phase 5 (US3): verify zero-progress cases and in-vivo completion stability.
5. Complete Phase 6: run complete Vitest + Pest test suites, Pint, build, and update Obsidian `[[REQ-036]]`.
