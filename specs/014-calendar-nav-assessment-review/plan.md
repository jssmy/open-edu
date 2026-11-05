# Implementation Plan: Navegación Temporal en Calendarios y Detalle de Evaluaciones en Modo Consulta

**Branch**: `014-calendar-nav-assessment-review` | **Date**: 2026-11-04 | **Spec**: [specs/014-calendar-nav-assessment-review/spec.md](spec.md)

**Input**: Feature specification from `specs/014-calendar-nav-assessment-review/spec.md`

## Summary

La solución implementa mejoras en navegación, experiencia de usuario y optimización de base de datos:
1. **Navegación temporal y título de rango en `ScheduleCalendar`**: Se dota al componente de calendario interactivo de controles de navegación (Anterior, Hoy, Siguiente) y la visualización del período visible (ej. "Noviembre 2026", rango de semana, día), interactuando con la API nativa de FullCalendar (`prev()`, `next()`, `today()`, `getDate()`) y reaccionando según la vista activa (mes, semana o día).
2. **Página de revisión de evaluaciones en modo consulta (`Assessments/Review.vue`)**: Se añade el endpoint `GET /assessments/{assessment}/review?student_id={studentId}` con políticas de autorización seguras para docentes, administradores y el propio estudiante. Muestra las preguntas, opciones, selecciones del alumno y puntajes/corrección en modo solo lectura, y brinda al estudiante la acción de "Continuar evaluación" (`/assessments/{id}/take`) si el intento sigue `in_progress`. Además, en la ficha del estudiante (`/students/{studentId}`), cada evaluación enlaza directamente a esta nueva vista.
3. **Optimización de consultas SQL y proyecciones**:
   - En `TeacherClassController::index()` y endpoints de listado, delegar el ordenamiento condicional por vigencia a Laravel Collection (`sort()`) en vez de `orderByRaw` con sentencias complejas en SQL, reduciendo bloqueo y procesamiento en motor de BD.
   - Restringir la selección de columnas cargadas en eager-loading (`with(...)`) trayendo solo los campos requeridos (`id`, `name`, `group_code`, etc.) y descartando campos voluminosos no utilizados (`objectives`, `content`, `intro_video_*`, etc.).
   - Optimizar consultas en `AssessmentController::review` limitando campos de `Student`, `Attempt` y `Choice`.

## Technical Context

**Language/Version**: PHP 8.4 / TypeScript 5 / Vue 3
**Primary Dependencies**: Laravel 12, Inertia.js v3, `@fullcalendar/vue3`, Tailwind CSS
**Storage**: PostgreSQL (Tablas `assessments`, `questions`, `choices`, `attempts`, `attempt_answers`, `students`, `users`)
**Testing**: Pest (`php artisan test`), Vitest (`npm run test`)
**Target Platform**: Navegador Web moderno (Desktop y Móvil)
**Project Type**: Monolito Modular con arquitectura hexagonal e Inertia SPA
**Performance Goals**: Navegación de calendario instantánea (<100ms en cliente) y carga de revisión en <1s.
**Constraints**: Modo estrictamente solo lectura en `Review.vue` (sin botones de guardar ni inputs editables). FullCalendar encapsulado en `ScheduleCalendar.vue` sin exponer la librería a componentes externos.
**Scale/Scope**: Módulos `Scheduling` (`ScheduleCalendar.vue`), `Students` (`Students/Show.vue`) y `Assessments` (`AssessmentController.php`, `Assessments/Review.vue`).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principle I: Monolito Modular**: PASS. Las rutas y lógica pertenecen a `Assessments` y la UI compartida a `ScheduleCalendar`.
- **Principle V: Frontend con Vue y Componentes Reutilizables**: PASS. Uso de Composition API (`<script setup>`), TypeScript y componentes del design system (`Badge`, `Button`, `RichTextContent`, `Alert`, `Card`).
- **Principle VI: Sistema de Diseño**: PASS. Se reutilizan botones e iconos del sistema sin estilos ad-hoc.
- **Principle XI: FullCalendar**: PASS. La navegación y vista de horarios se mantienen exclusivamente mediante FullCalendar dentro de `ScheduleCalendar.vue`.
- **Principle VII: Calidad y Pruebas**: PASS. Se añaden tests de Feature en Pest para `/review` y pruebas Vitest para `ScheduleCalendar` y `Review`.

## Project Structure

### Documentation (this feature)

```text
specs/014-calendar-nav-assessment-review/
├── plan.md              # Este plan de implementación
├── research.md          # Fase 0: Decisiones de FullCalendar API y estructura de Review
├── data-model.md        # Fase 1: Estructura de Attempt, Answers y Choices
├── quickstart.md        # Fase 1: Guía de validación y escenarios de prueba
├── contracts/           # Fase 1: Contratos de props y rutas
│   └── assessment-review.md
└── checklists/
    └── requirements.md
```

### Source Code (repository root)

```text
app/Modules/Assessments/
├── Infrastructure/
│   ├── Http/
│   │   └── AssessmentController.php   # Método review() optimizado con proyección de columnas
app/Modules/Scheduling/
├── Infrastructure/
│   ├── Http/
│   │   └── TeacherClassController.php # Optimización SQL: ordenamiento por Collection y eager loading selectivo
routes/
└── web.php                            # Ruta GET /assessments/{assessment}/review
resources/js/
├── design-system/
│   └── ScheduleCalendar.vue           # Botones prev, next, today y title dinámico
├── pages/
│   ├── Assessments/
│   │   └── Review.vue                 # Vista exclusiva de solo lectura
│   └── Students/
│       └── Show.vue                   # Enlaces en tarjetas de evaluación hacia /review
```
