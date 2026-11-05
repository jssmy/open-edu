# Implementation Plan: Programación Académica Basada en Docente y Reorganización de Filtros

**Branch**: `013-teacher-scheduling-workflow` | **Date**: 2026-11-04 | **Spec**: [specs/013-teacher-scheduling-workflow/spec.md](spec.md)

**Input**: Feature specification from `specs/013-teacher-scheduling-workflow/spec.md`

## Summary

La funcionalidad reorganiza la vista de Programación Académica (`/scheduling`) para adoptar un flujo de trabajo centrado en el docente. La cabecera superior contendrá un selector interactivo de docente (`SearchableSelect`) que inicializa por defecto con el primer docente activo (o "Todos los docentes" como opción global) y actualiza el calendario semanal interactivo (`FullCalendar` / `ScheduleCalendar`) y la tabla inferior sincrónicamente. Los filtros de búsqueda tabular (búsqueda de texto, curso, aula y estado) se reubican justo arriba de la tabla inferior para permitir auditar y refinar las ofertas asignadas al docente seleccionado.

## Technical Context

**Language/Version**: PHP 8.4 / TypeScript 5 / Vue 3
**Primary Dependencies**: Laravel 12, Inertia.js v3, `@inertiajs/vue3`, `@fullcalendar/vue3`, Tailwind CSS
**Storage**: PostgreSQL (Tablas `course_offerings`, `course_sessions`, `course_teacher`, `users`)
**Testing**: Pest (`php artisan test`), Vitest (`npm run test`)
**Target Platform**: Navegador Web moderno (Desktop y Responsive)
**Project Type**: Web Application (Monolito Modular con arquitectura hexagonal e Inertia SPA)
**Performance Goals**: Carga y cambio reactivo de docente en menos de 2 segundos.
**Constraints**: Respetar componentes existentes de diseño (`SearchableSelect`, `Select`, `Input`, `Table`, `ScheduleCalendar`, `Badge`, `Button`), sin alterar contratos REST/Inertia incompatibles.
**Scale/Scope**: Módulo `Scheduling`, vista `resources/js/pages/Scheduling/Index.vue` y controlador `SchedulingController.php`.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principle I: Monolito Modular con Arquitectura Hexagonal**: PASS. Se mantiene la estructura modular en `App\Modules\Scheduling`. El controlador delega y recibe DTOs/Resources sin romper capas.
- **Principle V: Frontend con Vue y Componentes Reutilizables**: PASS. Uso estricto de Vue 3 Composition API (`<script setup>`), TypeScript y componentes del design system.
- **Principle VI: Sistema de Diseño**: PASS. Se reutilizan `SearchableSelect`, `Select`, `Input`, `Button`, `Table` y `ScheduleCalendar`.
- **Principle XI: Interfaz y FullCalendar**: PASS. El calendario semanal interactivo sigue implementado a través del wrapper del design system `ScheduleCalendar.vue` sobre `@fullcalendar/vue3`.
- **Principle VII: Calidad y Pruebas**: PASS. Se añaden o ajustan tests unitarios/feature de Laravel y pruebas frontend correspondientes.

## Project Structure

### Documentation (this feature)

```text
specs/013-teacher-scheduling-workflow/
├── plan.md              # Este plan de implementación
├── research.md          # Fase 0: Decisiones de arquitectura y UX
├── data-model.md        # Fase 1: Entidades y flujos de datos
├── quickstart.md        # Fase 1: Guía de validación y escenarios de prueba
├── contracts/           # Fase 1: Contratos de props y query params
│   └── scheduling-ui.md # Contrato de props Inertia y eventos Vue
└── checklists/
    └── requirements.md  # Checklist de validación de calidad
```

### Source Code (repository root)

```text
app/Modules/Scheduling/
├── Infrastructure/
│   ├── Http/
│   │   └── SchedulingController.php   # Manejo de query params (teacher, course, classroom, status, q) y defaults
resources/js/
├── pages/
│   └── Scheduling/
│       └── Index.vue                  # Reorganización: selector de docente en cabecera + filtros sobre la tabla
```
