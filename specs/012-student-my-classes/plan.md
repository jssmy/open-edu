# Implementation Plan: Módulo de Mis Clases del Estudiante con Restricciones de Fechas y Estado

**Branch**: `012-student-my-classes` | **Date**: 2026-11-04 | **Spec**: [specs/012-student-my-classes/spec.md](file:///Users/jssmy/Documents/projects/open-edu/specs/012-student-my-classes/spec.md)

**Input**: Feature specification from `/specs/012-student-my-classes/spec.md`

## Summary
Reemplazar la vista y concepto de "Mis cursos" por "Mis clases" (`/my/classes`) para los estudiantes de la plataforma. La interfaz mostrará las clases asociadas a las ofertas académicas en las que el estudiante está activamente matriculado, integrando dos secciones compactas superiores de avisos (hasta 5 clases en curso y hasta 5 próximas a empezar), filtrado por estado con prioridad en clases iniciadas, bloqueo de acceso previo a la fecha de inicio (`starts_on > hoy`) tanto en UI como en backend, y habilitación del modo de solo consulta cuando la clase finaliza (`ends_on < hoy`), impidiendo mutaciones de avance o rendición de evaluaciones.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 12 / 13) + TypeScript / Vue 3.5 (Inertia.js v3)

**Primary Dependencies**: Inertia.js, Tailwind CSS v4, Vue Test Utils, Vitest, Pest PHP

**Storage**: PostgreSQL / SQLite (entidades `enrollments`, `course_offerings`, `courses`, `lesson_progress`, `assessment_attempts`)

**Testing**: Pest PHP (Feature y Unit tests), Vitest (Componentes Vue)

**Target Platform**: Web SPA con renderizado cliente vía Inertia

**Project Type**: Monolito modular con Arquitectura Hexagonal

**Performance Goals**: Carga de listado de clases y progreso en < 200ms

**Constraints**: Preservar inmutabilidad de lecciones y evaluaciones en cursos culminados

**Scale/Scope**: Módulos afectados: `Scheduling`, `Enrollments`, `Progress`, `Assessments`, interfaz de navegación y visores Vue

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Monolito Modular con Arquitectura Hexagonal)**: Los controladores se mantienen delgados. Las consultas y reglas de negocio se implementan en repositorios/servicios de aplicación (`Scheduling`, `Progress`, `Enrollments`).
- **Principio II (SOLID)**: Las restricciones temporales se encapsulan en las políticas (`CourseAccessPolicy`, `AssessmentPolicy`) y reglas de dominio de `CourseOffering` / `Enrollment`.
- **Principio III (DRY, KISS, YAGNI)**: Reutilización de `CourseCard`, `ProgressBar`, `Badge` y componentes del design system. Redirección limpia de `/my/courses` para evitar duplicidad de vistas.
- **Principio IV (Clean Code & Linters)**: Cobertura con pruebas Pest y Vitest, formateo con Laravel Pint.
- **Principio V (Frontend Vue y Componentes Reutilizables)**: Sin frameworks UI externos adicionales; uso de Tailwind CSS y el design system existente.

## Project Structure

### Documentation (this feature)

```text
specs/012-student-my-classes/
├── plan.md              # Plan de implementación
├── research.md          # Registro de decisiones técnicas
├── data-model.md        # Entidades, estados y tipos
├── quickstart.md        # Guía de verificación rápida
├── contracts/           # Contratos de API y respuestas
│   └── student-classes-api.md
└── tasks.md             # Tareas ordenadas (generadas en /speckit-tasks)
```

### Source Code

```text
app/
├── Modules/
│   ├── Enrollments/
│   │   └── Infrastructure/
│   │       └── Policies/CourseAccessPolicy.php
│   ├── Progress/
│   │   └── Infrastructure/
│   │       └── Http/StudyController.php
│   ├── Assessments/
│   │   └── Infrastructure/
│   │       └── Policies/AssessmentPolicy.php
│   └── Scheduling/
│       ├── Application/GetStudentClasses.php
│       └── Infrastructure/
│           ├── Http/StudentClassController.php
│           └── Http/ClassNavigationController.php
routes/
└── web.php
resources/js/
├── layouts/AppLayout.vue
├── pages/
│   ├── Dashboard.vue
│   ├── Student/MyClasses.vue
│   └── Study/CourseViewer.vue
└── components/
    └── courses/CourseCard.vue
tests/
├── Feature/
│   ├── Study/StudentClassesTest.php
│   └── Study/CourseTemporalRestrictionsTest.php
└── js/
    └── __tests__/StudentClasses.test.ts
```
