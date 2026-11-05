# Implementation Plan: Restricción de Acciones de Publicación y Eliminación de Cursos para Docentes

**Branch**: `010-restrict-course-actions` | **Date**: 2026-11-04 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/010-restrict-course-actions/spec.md`

## Summary

Corregir la brecha de autorización donde los docentes asignados al curso recibían erróneamente `canManageCourses = true` en `CourseController::show`, permitiéndoles ver los bloques de cambio de estado y eliminación en la interfaz. La bandera se ajustará para responder exclusivamente a `$request->user()->can('courses.manage')` y se incorporarán pruebas de regresión en Pest.

## Technical Context

**Language/Version**: PHP 8.4, TypeScript / Vue 3
**Primary Dependencies**: Laravel 12, Inertia.js v3, Spatie Permission, Tailwind CSS
**Storage**: PostgreSQL
**Testing**: Pest (PHP), Vitest (JS)
**Target Platform**: Web application (Desktop/Tablet/Mobile)
**Project Type**: Monolito Modular Laravel + Inertia/Vue
**Performance Goals**: Sin impacto en performance (<1ms en comprobación de Gate/Permission)
**Constraints**: Respetar las directrices de la Constitución (SOLID, Seguridad, Autorización en Backend)
**Scale/Scope**: 1 controlador (`CourseController.php`), 1 vista (`Show.vue`), 1 suite de tests (`TeacherCourseAccessTest.php`)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **Principle I: Monolito Modular con Arquitectura Hexagonal**: La lógica se mantiene en el módulo de cursos y sigue los controladores delgados y políticas existentes.
- [x] **Principle VII: Calidad y Pruebas**: Se agregan pruebas automatizadas en Pest que validan tanto la respuesta de Inertia como la denegación en endpoints de mutación (`PATCH /status` y `DELETE`).
- [x] **Principle VIII: Seguridad**: Autorización centralizada y aplicada en backend mediante Gates/Policies; la UI refleja estrictamente las capacidades autorizadas del usuario.

## Project Structure

### Documentation (this feature)

```text
specs/010-restrict-course-actions/
├── spec.md              # Requerimientos y especificación funcional
├── plan.md              # Este plan de implementación
├── research.md          # Hallazgos de Phase 0 y decisiones técnicas
├── data-model.md        # Contratos de respuesta e interfaz
├── quickstart.md        # Guía de verificación y ejecución
└── tasks.md             # Tareas para ejecución (/speckit-tasks)
```

### Source Code

```text
app/Modules/Courses/Infrastructure/Http/
└── CourseController.php          # Corregir canManageCourses en show()

resources/js/pages/Courses/
└── Show.vue                      # Verifica render condicional v-if="canManageCourses"

tests/Feature/Courses/
└── TeacherCourseAccessTest.php   # Tests para canManageCourses y 403 en status/destroy
```
