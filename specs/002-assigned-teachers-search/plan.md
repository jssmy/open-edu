# Implementation Plan: Lista priorizada y buscador de docentes asignados

**Branch**: `002-assigned-teachers-search` | **Date**: 2026-11-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/002-assigned-teachers-search/spec.md`

## Summary

En la sección "Docentes asignados" de `Courses/Show.vue`, ordenar la lista con los docentes asignados primero, limitar su alto con scroll interno, completar hasta 10 filas cuando haya menos de 10 asignados y añadir un buscador. Enfoque: lógica de ordenado, tope de filas y filtrado en un composable de frontend puro (`useTeacherPicker`) y un componente `TeacherPicker.vue` que reemplaza el bloque actual. Sin cambios de backend, rutas ni base de datos: `availableTeachers` ya entrega a todos los docentes (`id`, `name`).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel), TypeScript (Vue 3 Composition API, `<script setup>`)

**Primary Dependencies**: Inertia v3, componentes del sistema de diseño existentes (`Input`, `Checkbox`, `IconButton`, `EmptyState`). Sin dependencias nuevas.

**Storage**: N/A (sin cambios; asignación existente vía `assignCourseTeachers`)

**Testing**: Vitest (`resources/js/__tests__/`) para composable y componente; Pest existente (`TeacherScopeTest`, `TeacherUnassignTest`) como regresión del guardado

**Target Platform**: Web responsive (WCAG 2.1 AA)

**Project Type**: Aplicación web monolítica modular (Laravel + Vue)

**Performance Goals**: Filtrado percibido instantáneo (<1 s, SC-004) con cientos de docentes

**Constraints**: Selecciones ocultas por el filtro se conservan; el orden no cambia al marcar/desmarcar hasta guardar; objetivos táctiles ≥44px

**Scale/Scope**: Cientos de docentes (filtrado local); un único componente nuevo

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Estado | Nota |
|---|---|---|
| I Monolito/hexagonal, sin lógica en controlador | Pasa | Sin cambios de backend |
| III KISS/YAGNI | Pasa | Filtro local; sin endpoint nuevo |
| V Vue, composables, TypeScript | Pasa | Lógica en composable tipado, componente pequeño |
| VI Sistema de diseño | Pasa | Reutiliza `Input`, `Checkbox`, `EmptyState`, `IconButton` |
| VII Pruebas | Pasa | Vitest para orden, tope de 10, búsqueda y conservación de selección |
| VIII Seguridad | Pasa | Autorización existente (`courses.manage`) intacta |
| X Dependencias | Pasa | Ninguna nueva |
| XI Listados: paginación en servidor y filtros en URL | Excepción justificada | Es un selector de casillas dentro de un formulario, no un listado tabular; ver Complexity Tracking |
| XI Estados vacíos distintos (sin datos / sin resultados) | Pasa | Dos mensajes distintos |

**Re-check post-diseño**: igual; sin violaciones nuevas.

## Project Structure

### Documentation (this feature)

```text
specs/002-assigned-teachers-search/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── teacher-picker-ui.md
└── tasks.md             # /speckit-tasks (no creado aquí)
```

### Source Code (repository root)

```text
resources/js/
├── components/courses/TeacherPicker.vue      # nuevo: buscador + lista con scroll
├── composables/useTeacherPicker.ts           # nuevo: orden, tope de 10, filtro
├── pages/Courses/Show.vue                    # usa TeacherPicker en lugar del bloque actual
└── __tests__/
    ├── useTeacherPicker.test.ts              # nuevo
    └── TeacherPicker.test.ts                 # nuevo
```

**Structure Decision**: Solo frontend, siguiendo la estructura existente (`components/courses`, `composables`, `__tests__`). Backend sin cambios.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Lista sin paginación de servidor ni filtro en URL (Principio XI) | Es un selector de casillas con selección pendiente de guardar; el filtro es transitorio | Paginar en servidor perdería selecciones no guardadas y complicaría el guardado; el estado en URL no aporta valor a un filtro efímero |
