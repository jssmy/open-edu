# Implementation Plan: Nombres Descriptivos de Permisos en Gestión de Roles

**Branch**: `007-permission-display-names` | **Date**: 2026-11-04 | **Spec**: [specs/007-permission-display-names/spec.md](specs/007-permission-display-names/spec.md)

**Input**: Feature specification from `specs/007-permission-display-names/spec.md`

## Summary

Enriquecer la gestión de roles en Open-Edu para que los permisos se presenten mediante nombres comprensibles en lenguaje natural (display names), acompañados de una categorización por módulo y descripciones de ayuda, reemplazando la exposición de identificadores técnicos crudos (`courses.manage`, `scheduling.manage`, etc.) en las interfaces de usuario (`Roles/Create.vue` y `Roles/Index.vue`), preservando al 100% las claves técnicas internas para autorización y directivas de seguridad.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 11+), TypeScript (Vue 3, Inertia.js v3)  
**Primary Dependencies**: Spatie Laravel Permission, Tailwind CSS v4, Inertia.js  
**Storage**: PostgreSQL (tabla `permissions` / catálogo canónico en dominio)  
**Testing**: Pest PHP (Feature & Unit tests), Vitest para componentes Vue  
**Target Platform**: Web (Desktop & Tablet)  
**Project Type**: Monolito Modular con Arquitectura Hexagonal (Laravel + Vue 3 SPA)  
**Performance Goals**: Cero overhead perceptible (< 5ms en serialización del catálogo de permisos)  
**Constraints**: 
- No alterar claves técnicas de Spatie para no quebrar autorizaciones ni gates existentes.
- Respetar principios de diseño accesible y sistema de diseño UI existente.
- Compatibilidad hacia atrás garantizada en payloads de API (`name` en arrays de sincronización).  
**Scale/Scope**: ~15 permisos iniciales agrupados en 7 módulos funcionales; extensible sin requerir migraciones adicionales.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **Principio I (Monolito Modular con Hexagonal)**: La lógica de metadatos de permisos y dominio se encapsula en `App\Modules\Identity\Domain` (`PermissionRecord`, `PermissionDictionary`) e infraestructura (`SpatieRolePermissionRepository`). El controlador se mantiene delgado.
- [x] **Principio II & III (SOLID, DRY, KISS, YAGNI)**: No se sobreingenia una tabla relacional innecesaria; se provee un catálogo canónico tipado en el dominio con fallback dinámico para KISS y YAGNI.
- [x] **Principio IV (Clean Code)**: Clases inmutables y fuertemente tipadas en PHP 8.4 (`readonly class PermissionRecord`).
- [x] **Principio V (Frontend Vue + Reusabilidad)**: Tipos de TypeScript actualizados (`PermissionItem`), componentes con script setup y composición limpia.
- [x] **Principio VI (Sistema de Diseño)**: Se reutilizan `Checkbox`, `Card`, `Badge` y tokens de espaciado y tipografía de Tailwind.
- [x] **Principio VII (Calidad y Pruebas)**: Cobertura con pruebas en Pest para `RoleController` y repositorio, y pruebas en Vitest para las vistas.
- [x] **Principio VIII (Seguridad)**: El backend sigue validando y aplicando autorizaciones estrictas sin confiar ciegamente en datos del frontend.

## Project Structure

### Documentation (this feature)

```text
specs/007-permission-display-names/
├── plan.md              # Este plan de implementación
├── research.md          # Investigación de patrones y catálogo canónico
├── data-model.md        # Definición de PermissionRecord, Dictionary y tipos TS
├── quickstart.md        # Guía de validación y escenarios de prueba
├── contracts/
│   └── roles-permissions-api.md  # Contratos HTTP e Inertia Props
└── checklists/
    └── requirements.md  # Checklist de calidad de requerimientos
```

### Source Code (repository root)

```text
app/Modules/Identity/
├── Domain/
│   ├── PermissionRecord.php           # DTO inmutable para permisos legibles
│   ├── PermissionDictionary.php       # Catálogo canónico con etiquetas, módulos y fallback
│   └── RolePermissionRepository.php   # Contrato con método allPermissionRecords()
├── Infrastructure/
│   ├── SpatieRolePermissionRepository.php # Implementación que resuelve PermissionRecord
│   └── Http/
│       └── RoleController.php         # Pasa los registros de permisos con labels a Inertia
resources/js/
├── types/
│   └── index.ts                       # Definición de interfaz PermissionItem
└── pages/
    └── Roles/
        ├── Create.vue                 # Renderizado de permisos con nombre amigable y módulo
        └── Index.vue                  # Modal de permisos con nombre amigable y módulo
tests/
├── Feature/
│   └── Identity/
│       └── RolePermissionsDisplayTest.php # Pruebas Pest para verificar props enriquecidas
```

## Phase 0: Outline & Research
*(Completado y documentado en `specs/007-permission-display-names/research.md`)*
- Se definió el catálogo de 13 permisos con sus etiquetas en español, descripciones y módulos de pertenencia.
- Se resolvió la estrategia de fallback automático para permisos nuevos o no catalogados.

## Phase 1: Design & Contracts
*(Completado y documentado en `specs/007-permission-display-names/data-model.md`, `contracts/roles-permissions-api.md` y `quickstart.md`)*
- Modelado de `PermissionRecord` y `PermissionDictionary`.
- Contratos Inertia para `Roles/Index` y `Roles/Create`.
- Interfaces de TypeScript correspondientes.
