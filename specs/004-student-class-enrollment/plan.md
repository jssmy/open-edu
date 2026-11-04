# Implementation Plan: Módulo de programación académica (CourseOffering, sesiones) y matrícula de estudiantes

**Branch**: `004-student-class-enrollment` | **Date**: 2026-11-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/004-student-class-enrollment/spec.md`

## Summary

Evolucionar el módulo `Scheduling` hacia el modelo de `CourseOffering` (edición/grupo de un curso con código de grupo autogenerado, fechas de inicio y fin sin restricción de periodos, capacidad máxima de estudiantes, modalidad presencial/virtual/híbrida, estado con transición automática a `en_curso` y `finalizada` según fechas, y un único docente asignado al grupo) y `CourseSession` (sesiones individuales por fecha creadas y editadas manualmente con fecha, inicio/fin, aula o enlace de videoconferencia y estado automático/cancelado, sin campo de título/tema ni referencia a lecciones), retirando la entidad de Periodos Académicos (`academic_periods`). En el calendario (`ScheduleCalendar`), cada docente se distingue por un color propio (`color`/`contrastColor` en FullCalendar v7, sin leyenda de docentes fuera del calendario), cada ítem/bloque dentro del calendario muestra en líneas separadas el horario, curso/grupo, docente y aula (`#eventContent`), y hacer clic en un bloque en Programación académica abre el modal de edición de esa programación (`Editar programación académica`). Integrar el nuevo módulo centralizado de Matrículas (`/enrollments`), protegido por el permiso `enrollments.manage` (por defecto solo Administrador), donde se selecciona al estudiante, se exploran los cursos disponibles en un catálogo paginado en servidor mediante el componente reutilizable `CourseCard` (ordenando primero los cursos con vacantes disponibles), se abre el modal de `CourseOffering` con sus sesiones, docente y cupos, se valida transaccionalmente el cupo disponible y los cruces de horario entre sesiones, se visualiza el horario del estudiante en tiempo real a ancho completo (`ScheduleCalendar` / FullCalendar) permitiendo desmatricular al hacer clic sobre cualquier bloque del horario o desde el listado compacto superior con altura máxima fija y scroll interno (liberando el cupo de inmediato previa confirmación con `AlertDialog`).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 12), TypeScript (Vue 3 `<script setup>`)

**Primary Dependencies**: Inertia v3 (`@inertiajs/vue3`), Spatie Laravel Permission (existente), FullCalendar (`@fullcalendar/vue3`, `@fullcalendar/core`, `@fullcalendar/daygrid`, `@fullcalendar/timegrid`, `@fullcalendar/interaction` — ya instalada en el proyecto)

**Storage**: PostgreSQL / SQLite (tests); migración evolutiva para `course_offerings`, `course_offering_teacher`, `course_sessions`, eliminación de `academic_periods` y adición de `course_offering_id` en `enrollments`; `course_teacher` mantenida como tabla derivada

**Testing**: Pest (`tests/Feature/Scheduling`, `tests/Feature/Enrollments`, `tests/Unit/Scheduling`) y Vitest (`resources/js/__tests__`)

**Target Platform**: Aplicación web responsive (WCAG 2.1 AA)

**Project Type**: Monolito modular Laravel + Vue con arquitectura hexagonal

**Performance Goals**: Renderizado y actualización en tiempo real del horario del estudiante y modal de programación en < 1 s; validación transaccional de matrícula < 200 ms

**Constraints**: Reglas críticas de negocio (cruces de horario de sesiones y límite de cupos `max_students`) garantizadas en el backend con bloqueo transaccional (`lockForUpdate`); sin dependencias nuevas de terceros; uso obligatorio de `ScheduleCalendar`, `SearchableSelect`, `Modal` (con botón "Cancelar") y `AlertDialog` (`requestAlertDialog`)

**Scale/Scope**: Cientos de cursos, grupos (`CourseOffering`), sesiones por meses y miles de matrículas; 2 módulos (`Scheduling` y `Enrollments`), ~4 pantallas actualizadas/nuevas

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Estado | Nota |
|---|---|---|
| I. Monolito Modular Hexagonal | Pasa | Módulos `Scheduling` y `Enrollments` con capas `Domain`, `Application` e `Infrastructure` desacopladas; dominio libre de Eloquent/Facades. |
| II. SOLID / III. DRY, KISS, YAGNI | Pasa | Unifica `Scheduling` en `CourseOffering` + `CourseSession` en lugar de duplicar módulos; transición de estados `en_curso`/`finalizada` determinista por fecha/hora sin cron jobs. |
| IV. Clean Code | Pasa | Tipado estricto en PHP 8.4 y TypeScript; formateo con Laravel Pint y ESLint/Prettier. |
| V. Frontend con Vue y Componentes Reutilizables | Pasa | Uso de Composition API `<script setup>`, tipos explícitos y composables reutilizables. |
| VI. Sistema de Diseño | Pasa | Reutiliza `Modal`, `Button`, `Badge`, `Card`, `SearchableSelect`, `ScheduleCalendar` y `AlertDialog`. |
| VII. Calidad y Pruebas | Pasa | Pruebas unitarias para detección de conflictos y transición de estados; pruebas Feature (Pest) para programación, cupos, cruces de horario, permisos y desmatrícula. |
| VIII. Seguridad | Pasa | Autorización en backend mediante middleware/Gate `can:scheduling.manage` y `can:enrollments.manage` (solo Administrador por defecto). |
| X. Gestión de Dependencias | Pasa | Cero paquetes nuevos requeridos (FullCalendar y Spatie ya están instalados). |
| XI. UX, Calendario Único y AlertDialog | Pasa | Horario en tiempo real y calendario de sesiones usan exclusivamente `ScheduleCalendar` (`@fullcalendar/vue3`); desmatrícula y bajas piden confirmación con `requestAlertDialog`. |

**Re-evaluación post-diseño (Phase 1)**: Todos los gates de la Constitución v1.5.0 se cumplen sin violaciones.

## Project Structure

### Documentation (this feature)

```text
specs/004-student-class-enrollment/
├── plan.md              # Este archivo (/speckit-plan)
├── research.md          # Decisiones de arquitectura y diseño (Phase 0)
├── data-model.md        # Modelo de datos y reglas de estado (Phase 1)
├── quickstart.md        # Guía de validación de extremo a extremo (Phase 1)
├── contracts/
│   └── enrollment-and-scheduling-routes.md  # Contratos HTTP/Inertia y UI (Phase 1)
└── tasks.md             # Fase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
app/Modules/Scheduling/
├── Domain/
│   ├── CourseOffering.php
│   ├── CourseSession.php
│   ├── OfferingStatus.php
│   ├── SessionStatus.php
│   ├── Modality.php
│   ├── ConflictDetector.php
│   ├── SchedulingRuleViolation.php
│   └── ScheduleRepository.php
├── Application/
│   ├── SaveSchedule.php
│   ├── DeleteSchedule.php
│   ├── SaveCourseSession.php
│   ├── DeleteCourseSession.php
│   └── ManageClassrooms.php
└── Infrastructure/
    ├── EloquentScheduleRepository.php
    ├── Models/
    │   ├── CourseOffering.php
    │   ├── CourseSession.php
    │   └── Classroom.php
    ├── Policies/
    │   └── SchedulePolicy.php
    └── Http/
        ├── SchedulingController.php
        ├── ClassroomController.php
        └── Requests/
            ├── SaveScheduleRequest.php
            ├── SaveSessionRequest.php
            └── SaveClassroomRequest.php

app/Modules/Enrollments/
├── Domain/
│   ├── Enrollment.php
│   ├── EnrollmentStatus.php
│   └── EnrollmentRepository.php
├── Application/
│   ├── EnrollStudent.php
│   ├── UnenrollStudent.php
│   └── ChangeEnrollmentStatus.php
└── Infrastructure/
    ├── EloquentEnrollmentRepository.php
    ├── Models/
    │   └── Enrollment.php
    ├── Policies/
    │   └── CourseAccessPolicy.php
    └── Http/
        ├── EnrollmentController.php
        ├── CourseEnrollmentController.php
        └── Requests/
            ├── StoreEnrollmentRequest.php
            └── ChangeEnrollmentStatusRequest.php

database/migrations/
└── 2026_11_04_000000_evolve_scheduling_to_course_offerings_and_sessions.php

resources/js/
├── pages/
│   ├── Scheduling/
│   │   ├── Index.vue
│   │   ├── Pending.vue
│   │   ├── Classrooms.vue
│   │   └── MySchedule.vue
│   ├── Enrollments/
│   │   └── Index.vue
│   ├── Courses/
│   │   └── Enrollments.vue
│   └── Students/
│       └── Show.vue
├── design-system/
│   ├── ScheduleCalendar.vue
│   └── SearchableSelect.vue
└── composables/
    └── useScheduleCalendar.ts
```

**Structure Decision**: Se mantiene la estructura de monolito modular con arquitectura hexagonal bajo `app/Modules/Scheduling` y `app/Modules/Enrollments`, añadiendo la vista `resources/js/pages/Enrollments/Index.vue` para el flujo interactivo de matrícula con modal de `CourseOffering` y horario del estudiante en tiempo real.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Ninguna | — | — |
