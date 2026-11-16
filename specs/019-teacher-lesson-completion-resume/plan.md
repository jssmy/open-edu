# Implementation Plan: Marcado de Lección Completada y Reanudación de Avance para Docentes

**Branch**: `019-teacher-lesson-completion-resume` | **Date**: 2026-11-16 | **Spec**: [specs/019-teacher-lesson-completion-resume/spec.md](spec.md)

**Input**: Feature specification from `/specs/019-teacher-lesson-completion-resume/spec.md`

## Summary

Implementa un modelo dual de completitud y reanudación de lecciones:
1. **Cursos con Programación Académica (`CourseOffering`)**: El docente dicta la clase en vivo en `/my/classes/{offering}/viewer` y al marcar una lección como completada (con posibilidad de desmarcarla en caso de error), la completitud se registra para la oferta académica (`course_offering_id`), viéndose reflejada automáticamente para todos los estudiantes inscritos en ese grupo en modo sincronizado (sin botón de marcado manual para los alumnos en esta modalidad).
2. **Cursos Asíncronos / Offline / Autogestionados**: En cursos sin programación académica (como capacitaciones de docentes autoinscritos según REQ-034), el participante cursa a su propio ritmo en `/my/courses/{course}` y su marcado es estrictamente individual (`user_id`), disponiendo de su botón interactivo para completar temas.
3. **Reanudación Automática Unificada**: Tanto en el visor de clase como en el visor de estudio, al reingresar se localiza la última lección completada (`findLastIndex((l) => l.is_completed)`) y la pantalla se posiciona directamente en ella (o en la primera lección si no hay historial).

## Technical Context

**Language/Version**: PHP 8.4, TypeScript 5.x, Vue 3.5+ (Composition API, `<script setup lang="ts">`)

**Primary Dependencies**: Laravel 12 (Modular Monolith), Inertia.js v3, Tailwind CSS v3, Spatie Laravel Permission

**Storage**: PostgreSQL / SQLite (entorno de pruebas y local), tabla `lesson_progress` extendida con `course_offering_id`

**Testing**: Pest 4 (Laravel Feature & Unit tests), Vitest (Vue component tests)

**Target Platform**: Navegador web moderno (Desktop y Mobile Responsive)

**Project Type**: Aplicación web SPA sobre monolito modular Laravel + Inertia.js

**Performance Goals**: Actualización optimista o < 200ms en el marcado de lección; renderizado y posicionamiento instantáneo de diapositiva sin recarga de página

**Constraints**:
- Cumplimiento estricto de la Constitución del proyecto Open-Edu y reglas Laravel Boost.
- No alterar ni romper los registros existentes de progreso de estudiantes ni docentes autoinscritos.
- Componentes Vue con raíz única (`single root element`).
- Formato de código automatizado con Laravel Pint.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio Constitucional | Estado | Justificación / Cumplimiento |
| :--- | :--- | :--- |
| **I. Monolito Modular con Arquitectura Hexagonal** | **PASS** | El cambio se localiza en los módulos `Progress` y `Scheduling`, comunicados a través de casos de uso y repositorios de dominio sin acoplamiento indebido. |
| **II. Principios SOLID** | **PASS** | `CompleteLesson` y `ProgressRepository` extienden limpiamente sus parámetros sin violar contratos existentes (SRP, OCP). |
| **III. DRY, KISS y YAGNI** | **PASS** | Se reutiliza la tabla `lesson_progress` agregando `course_offering_id` en lugar de crear una tabla duplicada. Se reutiliza `CourseViewer.vue` adaptando dinámicamente sus controles para docentes. |
| **IV. Clean Code & Pint** | **PASS** | Estilo PSR-12 verificado con Laravel Pint y TypeScript sin tipos `any`. |
| **V. Frontend con Vue y Componentes Reutilizables** | **PASS** | Composition API con tipado explícito en `CourseViewer.vue`. |
| **VI. Sistema de Diseño** | **PASS** | Uso de componentes del sistema de diseño (`Button`, `Badge`, `Card`, `ProgressBar`, `Alert`). |
| **VII. Calidad y Pruebas** | **PASS** | Pruebas Feature en Pest para el visor docente, progreso de oferta, toggle de completitud y posicionamiento inicial. |
| **VIII. Seguridad y Autorización** | **PASS** | Verificación en backend: solo el docente asignado a la oferta académica (o usuarios con `courses.manage`) puede marcar lecciones para ese grupo. |

## Project Structure

### Documentation (this feature)

```text
specs/019-teacher-lesson-completion-resume/
├── plan.md              # Este plan de implementación
├── research.md          # Decisiones de arquitectura y diseño resueltas
├── data-model.md        # Modelo de datos y cambios en esquema lesson_progress
├── quickstart.md        # Guía paso a paso de verificación y pruebas
├── contracts/           # Contratos de APIs y props de Inertia
│   └── lesson-completion.md
└── checklists/
    └── requirements.md  # Checklist de calidad del requerimiento
```

### Source Code (repository root)

```text
app/
├── Modules/
│   ├── Progress/
│   │   ├── Application/
│   │   │   └── CompleteLesson.php
│   │   ├── Domain/
│   │   │   └── ProgressRepository.php
│   │   └── Infrastructure/
│   │       ├── EloquentProgressRepository.php
│   │       └── Http/
│   │           └── StudyController.php
│   └── Scheduling/
│       └── Infrastructure/
│           └── Http/
│               └── TeacherClassController.php
database/
└── migrations/
    └── 2026_11_16_000001_add_course_offering_to_lesson_progress_table.php
resources/
└── js/
    └── pages/
        └── Study/
            └── CourseViewer.vue
tests/
└── Feature/
    ├── Progress/
    │   └── LessonCompletionTest.php
    └── Scheduling/
        └── TeacherClassViewerTest.php
```

**Structure Decision**: Monolito modular existente. Se aplican cambios quirúrgicos en `Modules/Progress` (para la gestión y persistencia del progreso de lección por oferta), `Modules/Scheduling` (para inyectar el progreso real en `TeacherClassController@viewer`) y en el componente Vue `CourseViewer.vue` (para habilitar el botón y el cálculo del índice de la última lección completada).

## Complexity Tracking

> *Sin violaciones a la Constitución ni complejidad innecesaria añadida.*
