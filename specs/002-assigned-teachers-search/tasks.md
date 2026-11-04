---

description: "Tasks for lista priorizada y buscador de docentes asignados"
---

# Tasks: Lista priorizada y buscador de docentes asignados

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/teacher-picker-ui.md](contracts/teacher-picker-ui.md)

**Tests**: Incluidos (la constitución, principio VII, los exige).

## Phase 1: Setup

- [x] T001 Revisar el bloque actual de docentes en `resources/js/pages/Courses/Show.vue` (selectedTeachers, saveTeachers) y los tests existentes `resources/js/__tests__/CourseContentEditor.test.ts` para seguir sus convenciones

## Phase 2: Foundational

- [x] T002 Crear `resources/js/composables/useTeacherPicker.ts` con los tipos `TeacherOption` y la función de normalización (minúsculas, sin tildes, trim) según research.md decisión 5

**Checkpoint**: composable base listo.

## Phase 3: User Story 1 - Ver primero a los docentes asignados (Priority: P1) 🎯 MVP

**Goal**: Asignados primero, scroll interno, completar hasta 10 filas si hay menos de 10 asignados.

**Independent Test**: Curso con 3 asignados muestra 10 filas con los 3 primero; con 100 asignados se muestran todos con scroll.

### Tests

- [x] T003 [P] [US1] Test del composable en `resources/js/__tests__/useTeacherPicker.test.ts`: asignados primero; <10 asignados completa a exactamente 10 filas; ≥10 muestra todos; <10 docentes totales muestra todos; el grupo no cambia al marcar/desmarcar sin guardar
- [x] T004 [P] [US1] Test del componente en `resources/js/__tests__/TeacherPicker.test.ts`: contenedor con alto máximo y scroll, estado vacío "No hay docentes disponibles.", emite `update:modelValue`

### Implementation

- [x] T005 [US1] Implementar en `resources/js/composables/useTeacherPicker.ts` el ordenado por `assignedIds` y la regla de filas visibles (research.md decisiones 2 y 3)
- [x] T006 [US1] Crear `resources/js/components/courses/TeacherPicker.vue` según contracts/teacher-picker-ui.md: lista con `max-h` y `overflow-y-auto`, `Checkbox` del sistema de diseño, objetivos táctiles ≥44px, `v-model` con ids seleccionados
- [x] T007 [US1] Reemplazar en `resources/js/pages/Courses/Show.vue` el bloque de checkboxes por `TeacherPicker`, pasando `availableTeachers`, `course.teachers` ids y conservando `saveTeachers`

**Checkpoint**: US1 funcional y probable por sí sola.

## Phase 4: User Story 2 - Buscar docentes (Priority: P2)

**Goal**: Buscador por nombre que conserva el orden y las selecciones ocultas.

**Independent Test**: Escribir parte de un nombre filtra; borrar restaura la vista priorizada; guardar conserva las selecciones ocultas.

### Tests

- [x] T008 [P] [US2] Ampliar `resources/js/__tests__/useTeacherPicker.test.ts`: filtro insensible a mayúsculas/tildes, trim, asignados coincidentes primero, borrar restaura vista priorizada, selecciones ocultas se conservan
- [x] T009 [P] [US2] Ampliar `resources/js/__tests__/TeacherPicker.test.ts`: campo con etiqueta "Buscar docente", botón limpiar, mensaje "No se encontraron docentes" distinto del de sin datos

### Implementation

- [x] T010 [US2] Añadir `query` y el filtrado a `resources/js/composables/useTeacherPicker.ts`
- [x] T011 [US2] Añadir a `resources/js/components/courses/TeacherPicker.vue` el `Input` de búsqueda con etiqueta visible, `IconButton` icono `clear` y el estado "No se encontraron docentes"

**Checkpoint**: US1 y US2 funcionan juntas.

## Phase 5: Polish & Cross-Cutting

- [x] T012 Ejecutar `npx vitest run` y `npx vue-tsc --noEmit`; corregir errores introducidos
- [x] T013 Ejecutar `php artisan test --compact tests/Feature/Courses/TeacherUnassignTest.php tests/Feature/Courses/TeacherScopeTest.php` como regresión del guardado
- [ ] T014 Validar manualmente los 5 pasos de [quickstart.md](quickstart.md) en `/courses/{id}`

## Dependencies & Execution Order

- Setup → Foundational (T002) → US1 → US2 → Polish.
- US2 extiende composable y componente de US1 (mismos archivos), por eso va después.
- Dentro de cada historia: tests (fallan primero) → composable → componente → integración.

## Parallel Opportunities

- T003 y T004 en paralelo (archivos distintos); T008 y T009 en paralelo.

## Implementation Strategy

- **MVP**: Phases 1–3 (US1): lista priorizada con scroll y relleno a 10.
- Luego US2 (buscador) y Polish.
