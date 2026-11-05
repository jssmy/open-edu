# Implementation Plan: Notificaciones de Operaciones vía Modal Alert

**Branch**: `008-modal-alert-notifications` | **Date**: 2026-11-04 | **Spec**: [specs/008-modal-alert-notifications/spec.md](specs/008-modal-alert-notifications/spec.md)

**Input**: Feature specification from `specs/008-modal-alert-notifications/spec.md`

## Summary

Estandarizar la retroalimentación de operaciones exitosas y fallidas en Open-Edu mediante diálogos modales interactivos (`AlertDialog` del sistema de diseño). Se flexibiliza el componente para soportar alertas de notificación pura (con solo un botón de confirmación positivo) y se incorporan helpers explícitos (`notifySuccess`, `notifyError`, `notifyWarning`, `notifyInfo`) en `useAlertDialog`, conectándolos a las acciones clave del sistema (como la gestión de permisos y roles).

## Technical Context

**Language/Version**: TypeScript, Vue 3 (Composition API `<script setup>`)  
**Primary Dependencies**: Inertia.js v3, Tailwind CSS v4, Vue Test Utils  
**Storage**: N/A (Estado reactivo en memoria `useAlertDialog.ts`)  
**Testing**: Vitest con jsdom y Vue Test Utils  
**Target Platform**: Web (Desktop & Mobile)  
**Project Type**: Monolito Modular con Frontend SPA Vue  
**Performance Goals**: Apertura instantánea (< 16ms, 1 frame de renderizado)  
**Constraints**: 
- Conservar intacto el comportamiento existente de confirmaciones de dos botones (`requestAlertDialog`).
- No usar librerías externas pesadas ni alerts nativos del navegador (`window.alert`).
- Ocultar el botón "Cancelar" cuando el diálogo sea una notificación informativa.  
**Scale/Scope**: Reutilizable en todos los módulos de la aplicación.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **Principio V (Frontend con Vue y Componentes Reutilizables)**: Se encapsula la lógica en el composable `useAlertDialog.ts` y se reutiliza el componente `AlertDialog.vue` del sistema de diseño.
- [x] **Principio VI (Sistema de Diseño)**: Se respetan los tokens visuales existentes (variantes `success`, `danger`, `warning`, `info`, bordes, iconos y tipografía).
- [x] **Principio XI (Consistencia y Accesibilidad de Diálogos)**: "Toda acción destructiva o notificación relevante debe usar el componente AlertDialog del sistema de diseño... MUST NOT usarse alert, confirm ni prompt nativos del navegador."
- [x] **Principio VII (Calidad y Pruebas)**: Se amplían las pruebas en `resources/js/__tests__/AlertDialog.test.ts` y pruebas de vistas relacionadas.

## Project Structure

### Documentation (this feature)

```text
specs/008-modal-alert-notifications/
├── plan.md              # Este plan de implementación
├── research.md          # Investigación de patrones y helpers
├── data-model.md        # Definición de tipos y API del composable
├── quickstart.md        # Guía de validación y escenarios
├── contracts/
│   └── notification-contracts.md # Contratos de interfaz Vue
└── checklists/
    └── requirements.md  # Checklist de calidad
```

### Source Code (repository root)

```text
resources/js/
├── composables/
│   └── useAlertDialog.ts        # Soporte para cancelLabel nullable + helpers notifySuccess/notifyError
├── design-system/
│   └── AlertDialog.vue         # Render condicional de botón único cuando cancelLabel es null
├── pages/
│   └── Roles/
│       ├── Index.vue           # Disparo explícito de notifySuccess / notifyError al guardar permisos
│       └── Create.vue          # Disparo explícito de notifyError ante fallos generales
└── __tests__/
    ├── AlertDialog.test.ts     # Pruebas para alertas de un solo botón y helpers
    └── RolesIndex.test.ts      # Verificación de notificación al guardar permisos
```

## Phase 0: Outline & Research
*(Completado y documentado en `specs/008-modal-alert-notifications/research.md`)*
- Análisis de la estructura de `AlertDialog.vue` y `useAlertDialog.ts`.
- Diseño de helpers `notifySuccess` y `notifyError` para llamadas explícitas.

## Phase 1: Design & Contracts
*(Completado y documentado en `specs/008-modal-alert-notifications/data-model.md`, `contracts/notification-contracts.md` y `quickstart.md`)*
- Tipado TypeScript de `AlertDialogOptions.cancelLabel?: string | null`.
- Contrato del botón único de confirmación para notificaciones puras.
