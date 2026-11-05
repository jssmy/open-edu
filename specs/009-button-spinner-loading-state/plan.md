# Implementation Plan: Estado de Carga, Spinner y Deshabilitación en Botones de Operación

**Branch**: `009-button-spinner-loading-state` | **Date**: 2026-11-04 | **Spec**: [specs/009-button-spinner-loading-state/spec.md](specs/009-button-spinner-loading-state/spec.md)

**Input**: Feature specification from `specs/009-button-spinner-loading-state/spec.md`

## Summary

Modernizar el componente base `Button.vue` del sistema de diseño para incorporar un spinner SVG animado (`animate-spin`), soporte para texto dinámico de progreso vía la prop opcional `loadingText?: string` (ej. "Guardando...") y deshabilitación integral contra dobles envíos. Integrar este comportamiento en el modal de asignación de permisos de roles (`Roles/Index.vue`) y asegurar la consistencia en todos los formularios de la plataforma.

## Technical Context

**Language/Version**: TypeScript, Vue 3 (`<script setup>`)  
**Primary Dependencies**: Tailwind CSS v4, Inertia.js v3  
**Storage**: N/A (UI visual state)  
**Testing**: Vitest con jsdom y Vue Test Utils  
**Target Platform**: Web (Desktop & Mobile)  
**Project Type**: Monolito Modular con Frontend SPA Vue  
**Performance Goals**: Renderizado inmediato (< 16ms) sin parpadeo de layout  
**Constraints**: 
- Reutilizar el componente `Button.vue` sin crear componentes paralelos.
- No romper botones existentes que no usan `loadingText`.
- Respetar las reglas de accesibilidad (`aria-busy`, foco y deshabilitación nativa).  
**Scale/Scope**: Componente base transversal consumido por todas las páginas y modales de Open-Edu.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **Principio V (Frontend con Vue y Componentes Reutilizables)**: Se mejora el componente central `Button.vue` evitando duplicación de botones personalizados en vistas.
- [x] **Principio VI (Sistema de Diseño)**: "Existirá un único sistema de diseño como fuente de verdad visual... Botones, formularios, tablas, modales y mensajes MUST ser consistentes."
- [x] **Principio XI (Interfaz y Experiencia de Usuario)**: Se previenen dobles envíos y se brinda retroalimentación instantánea de progreso al usuario.
- [x] **Principio VII (Calidad y Pruebas)**: Se amplían las pruebas en `resources/js/__tests__/design-system/components.test.ts` y pruebas de vistas relacionadas.

## Project Structure

### Documentation (this feature)

```text
specs/009-button-spinner-loading-state/
├── plan.md              # Este plan de implementación
├── research.md          # Análisis de patrones y spinner SVG
├── data-model.md        # Definición de props y tipos
├── quickstart.md        # Guía de validación y escenarios
├── contracts/
│   └── button-contracts.md # Contratos del componente Button
└── checklists/
    └── requirements.md  # Checklist de calidad
```

### Source Code (repository root)

```text
resources/js/
├── design-system/
│   └── Button.vue              # Spinner SVG animado + prop loadingText
├── pages/
│   └── Roles/
│       └── Index.vue           # isSavingPermissions en botón "Guardar y cerrar" con loading-text="Guardando..."
└── __tests__/
    ├── design-system/
    │   └── components.test.ts  # Pruebas para spinner y loadingText en Button
    └── RolesIndex.test.ts      # Verificación del estado de carga en el modal de permisos
```

## Phase 0: Outline & Research
*(Completado y documentado en `specs/009-button-spinner-loading-state/research.md`)*
- Diseño del icono SVG con `animate-spin` integrado.
- Lógica de sustitución de texto con fallback al slot.

## Phase 1: Design & Contracts
*(Completado y documentado en `specs/009-button-spinner-loading-state/data-model.md`, `contracts/button-contracts.md` y `quickstart.md`)*
- Contratos de TypeScript para `Button.vue`.
- Integración en `Roles/Index.vue`.
