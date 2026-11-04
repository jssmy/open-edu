# Implementation Plan: Programación académica

**Branch**: `003-academic-scheduling` | **Date**: 2026-11-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/003-academic-scheduling/spec.md`

## Summary

Nuevo módulo `Scheduling` para gestionar periodos, aulas y programaciones (curso + docente + bloques semanales + aula o virtual) con detección de conflictos en backend y un calendario FullCalendar (constitución v1.5.0). La asignación de docentes sale del detalle del curso (solo lectura); `course_teacher` se mantiene como tabla derivada. Las asignaciones existentes migran a "Por programar".

## Technical Context

**Language/Version**: PHP 8.4 (Laravel), TypeScript (Vue 3 `<script setup>`)
**Primary Dependencies**: Inertia v3, Spatie permissions (existente), FullCalendar `@fullcalendar/vue3` (nueva, requiere aprobación)
**Storage**: PostgreSQL; tablas `academic_periods`, `classrooms`, `course_schedules`, `schedule_blocks`; `course_teacher` derivada
**Testing**: Pest (conflictos, permisos, migración, sincronización docente); Vitest (`ScheduleCalendar`)
**Target Platform**: Web responsive (WCAG 2.1 AA)
**Project Type**: Monolito modular Laravel + Vue
**Performance Goals**: Carga del calendario de un periodo < 2 s con ~500 programaciones
**Constraints**: Conflictos validados en backend y transaccionales; periodos finalizados bloquean sus programaciones (FR-014, sin reapertura); paginación en servidor en listados; confirmaciones con `AlertDialog`
**Scale/Scope**: Cientos de cursos/docentes; 1 módulo, ~5 pantallas

## Constitution Check

| Principio | Estado | Nota |
|---|---|---|
| I Monolito hexagonal | Pasa | Módulo `Scheduling`; dominio sin Eloquent |
| III KISS/YAGNI | Pasa | Sin sesiones por fecha ni capacidad validada |
| V Vue/TS/composables | Pasa | `useScheduleCalendar`, `useSearchableSelect` + componentes pequeños |
| VI Sistema de diseño | Pasa | `ScheduleCalendar` envuelve FullCalendar; `SearchableSelect` reutilizable (máx. 5 elementos visibles con scroll) |
| VII Pruebas | Pasa | Pest + Vitest |
| VIII Seguridad | Pasa | Policy/Gate `scheduling.manage` |
| X Dependencias | Pendiente de aprobación | FullCalendar exigido por XI |
| XI Calendario único / listados / AlertDialog | Pasa | Según contrato |

**Re-check post-diseño**: sin violaciones; solo la aprobación de la dependencia.

## Project Structure

```text
specs/003-academic-scheduling/
├── plan.md  research.md  data-model.md  quickstart.md
└── contracts/scheduling-routes.md

app/Modules/Scheduling/
├── Domain/          # CourseSchedule, ScheduleBlock, ConflictDetector, repositorios
├── Application/     # CreateSchedule, UpdateSchedule, DeleteSchedule, ManageClassrooms, ManagePeriods
└── Infrastructure/  # Models, Http (controllers, requests), Policies, Eloquent repos
app/Providers/SchedulingServiceProvider.php
database/migrations/  # 4 tablas + migración de course_teacher
resources/js/pages/Scheduling/  # Index, Pending, MySchedule, Classrooms, Periods
resources/js/design-system/ScheduleCalendar.vue
resources/js/design-system/SearchableSelect.vue
resources/js/composables/useScheduleCalendar.ts
resources/js/composables/useSearchableSelect.ts
```

Cambios en `Courses`: retirar `AssignTeachers`, ruta `courses.teachers` y el selector en `Courses/Show.vue` (lista de solo lectura; reemplaza `TeacherPicker` de la feature 002).

## Complexity Tracking

| Desviación | Justificación |
|---|---|
| Tabla `course_teacher` derivada | Evita regresiones en políticas y consultas existentes |
