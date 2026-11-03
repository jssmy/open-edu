# Implementation Plan: Sistema de Gestión de Aprendizaje (LMS)

**Branch**: `001-lms-learning-platform` | **Date**: 2026-11-03 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-lms-learning-platform/spec.md`

## Summary

LMS para una sola organización: autenticación y roles, cursos con unidades/lecciones, estudiantes e
inscripciones, evaluaciones auto-calificadas, seguimiento de progreso y reportes. Se construye como
un monolito modular Laravel + Vue.js (Inertia), con PostgreSQL, aplicando arquitectura hexagonal
dentro de cada módulo (Dominio / Aplicación / Infraestructura) y un sistema de diseño propio de
componentes Vue. Detalle de decisiones en [research.md](./research.md).

## Technical Context

**Language/Version**: PHP 8.3 · Laravel 13 · TypeScript 5 · Vue 3 (Composition API, `<script setup>`)

**Primary Dependencies**: Inertia.js (Laravel↔Vue sin API separada), Vite, Tailwind CSS (tokens del
sistema de diseño), spatie/laravel-permission (permisos de rol editables, ver research)

**Storage**: PostgreSQL 16; archivos subidos en disco local de Laravel (abstracción Storage)

**Testing**: Pest (unitarias de dominio/aplicación, Feature de Laravel), Vitest + Vue Test Utils
(componentes), Playwright (E2E de flujos críticos)

**Target Platform**: Servidor Linux, navegadores modernos de escritorio y móvil

**Project Type**: Aplicación web monolítica (un repositorio, un despliegue)

**Performance Goals**: Resultado de evaluación < 5 s; reporte de 500 estudiantes < 30 s; 500
usuarios concurrentes (SC-005, SC-007, SC-008)

**Constraints**: Sin microservicios ni colas al inicio; reglas críticas solo en backend; WCAG 2.1 AA

**Scale/Scope**: Una organización; ~500 usuarios concurrentes; ~25 pantallas

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Evaluación | Estado |
|-----------|-----------|--------|
| I. Monolito modular + hexagonal | Un solo despliegue; módulos con Domain/Application/Infrastructure; puertos solo en persistencia y almacenamiento de archivos | PASS |
| II. SOLID | Casos de uso con dependencias inyectadas por constructor | PASS |
| III. DRY/KISS/YAGNI | Sin colas, sin API separada, sin PDF ni multi-tenant (fuera de alcance) | PASS |
| IV. Clean Code | Pint (PSR-12) + ESLint/Prettier en CI | PASS |
| V. Vue + componentes | Composition API, composables y servicios; TypeScript resuelve el TODO de la constitución | PASS |
| VI. Sistema de diseño | Tokens + biblioteca base en `resources/js/design-system` | PASS |
| VII. Calidad y pruebas | Pest, Vitest, Playwright | PASS |
| VIII. Seguridad | Policies/Gates, Form Requests, CSRF, rate limiting | PASS |
| IX. Errores y logs | Excepciones de dominio mapeadas a respuestas; auditoría de eventos | PASS |
| X. Dependencias | Cada dependencia justificada en research.md | PASS |
| XI. UX | Estados de carga/vacío/error en el sistema de diseño | PASS |
| XII. IA | Plan y spec revisados antes de implementar | PASS |

**Post-diseño (re-evaluación)**: PASS. Sin violaciones; Complexity Tracking no aplica.

## Project Structure

### Documentation (this feature)

```text
specs/001-lms-learning-platform/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── web-routes.md
└── tasks.md             # lo genera /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Modules/
│   ├── Identity/          # usuarios, roles, permisos, autenticación
│   ├── Courses/           # cursos, unidades, lecciones
│   ├── Students/          # estudiantes
│   ├── Enrollments/       # inscripciones
│   ├── Assessments/       # evaluaciones, preguntas, intentos
│   ├── Progress/          # progreso por lección
│   └── Reports/           # consultas y exportación (sin capa Domain: solo lectura)
│       └── <Módulo>/
│           ├── Domain/          # entidades, value objects, excepciones, puertos (interfaces)
│           ├── Application/     # casos de uso (acciones), DTOs
│           └── Infrastructure/  # Eloquent, repositorios, controladores, Form Requests, Policies
└── Shared/                # kernel compartido mínimo (excepciones base, auditoría)

database/
├── migrations/
├── factories/
└── seeders/

resources/js/
├── design-system/         # tokens + componentes base (Button, Input, Table, Modal, Alert…)
├── components/            # componentes compuestos reutilizables
├── composables/
├── services/              # llamadas al backend
├── layouts/
└── pages/                 # una carpeta por módulo

tests/
├── Unit/                  # dominio y aplicación sin framework ni BD
├── Feature/               # endpoints y flujos (PostgreSQL)
└── e2e/                   # Playwright
resources/js/__tests__/    # Vitest
```

**Structure Decision**: Monolito Laravel con módulos hexagonales en `app/Modules` y frontend Vue en
`resources/js`, servido con Inertia. Los puertos (interfaces) existen solo para repositorios y
almacenamiento de archivos (fronteras reales); el resto se implementa directamente como casos de uso.

## Complexity Tracking

Sin violaciones de la constitución que justificar.
