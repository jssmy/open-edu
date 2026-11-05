# Tasks: Estado de Carga, Spinner y Deshabilitación en Botones de Operación

**Input**: Design documents from `specs/009-button-spinner-loading-state/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`
**Feature Directory**: `specs/009-button-spinner-loading-state`

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., [US1], [US2], [US3])
- Include exact file paths in descriptions

---

## Phase 1: Setup & Foundational (Base Button Component)

**Purpose**: Ampliar el componente base `Button.vue` para incluir el spinner SVG animado y la prop `loadingText`.

- [x] T001 [US1] Añadir prop opcional `loadingText?: string` y definir spinner SVG con clase `animate-spin` en `resources/js/design-system/Button.vue`
- [x] T002 [US1] Configurar la deshabilitación nativa (`disabled || loading`) y clase `disabled:cursor-not-allowed` en `resources/js/design-system/Button.vue`
- [x] T003 [P] [US1] Añadir pruebas unitarias para el renderizado del spinner y deshabilitación en `resources/js/__tests__/design-system/components.test.ts`

**Checkpoint**: Componente base `Button` listo y verificado con spinner visual.

---

## Phase 2: User Story 2 - Texto Dinámico de Progreso ("Guardando...") (Priority: P2) 🎯 MVP

**Goal**: Permitir que el botón muestre automáticamente el texto dinámico provisto en `loadingText` durante la carga.

**Independent Test**: Montar `Button` con `:loading="true"` y `loading-text="Guardando..."` y verificar que el texto cambia al gerundio configurado junto al spinner.

- [x] T004 [US2] Implementar en `resources/js/design-system/Button.vue` el renderizado condicional de `loadingText` cuando `loading` sea verdadero, manteniendo el `<slot />` por defecto si `loadingText` no se pasa
- [x] T005 [P] [US2] Añadir prueba unitaria en `resources/js/__tests__/design-system/components.test.ts` validando la sustitución de texto con `loadingText`

**Checkpoint**: Botón con texto dinámico completamente operativo.

---

## Phase 3: User Story 3 - Integración en Operaciones Modales y Acciones Asíncronas (Priority: P3)

**Goal**: Conectar el estado de procesamiento en el modal de permisos de roles y asegurar que los botones secundarios queden deshabilitados durante el guardado.

**Independent Test**: En `/roles`, presionar "Guardar y cerrar" en el modal de permisos y comprobar que el botón muestra "Guardando..." con spinner y que el botón "Cancelar" queda inactivo mientras la petición PUT se completa.

- [x] T006 [US3] Agregar estado reactivo `isSavingPermissions` en `resources/js/pages/Roles/Index.vue` para controlar el ciclo de vida de `router.put`
- [x] T007 [US3] Enlazar `:loading="isSavingPermissions"` y `loading-text="Guardando..."` al botón "Guardar y cerrar" en `resources/js/pages/Roles/Index.vue`
- [x] T008 [US3] Deshabilitar el botón "Cancelar" (`:disabled="isSavingPermissions"`) mientras la persistencia esté en curso en `resources/js/pages/Roles/Index.vue`
- [x] T009 [P] [US3] Actualizar pruebas unitarias en `resources/js/__tests__/RolesIndex.test.ts` para verificar la reactividad de `isSavingPermissions` y deshabilitación

**Checkpoint**: Integración completa en el modal de permisos y formularios.

---

## Phase 4: Polish & Regression Verification

**Purpose**: Ejecución de suites completas de pruebas y build de producción.

- [x] T010 Ejecutar suite de pruebas de frontend con Vitest: `npm test`
- [x] T011 Ejecutar suite de pruebas de backend con Pest: `php artisan test --filter=Role`
- [x] T012 Ejecutar compilación de producción con Vite: `npm run build`
- [x] T013 Validar los escenarios definidos en `specs/009-button-spinner-loading-state/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup/Base Button)**: Sin dependencias, inicia inmediatamente.
- **Phase 2 (User Story 2 - MVP)**: Depende de Phase 1.
- **Phase 3 (User Story 3)**: Depende de Phase 1 y 2.
- **Phase 4 (Polish)**: Depende de todas las fases anteriores.

### Parallel Opportunities
- T003 y T005 (Pruebas unitarias de Button) pueden correr en paralelo con el componente.
- T009 (Prueba de RolesIndex) puede correr en paralelo con T006-T008.

---

## Implementation Strategy (MVP First)

1. **Paso 1**: Completar Fase 1 y 2 (`Button.vue` con spinner SVG y `loadingText`).
2. **Paso 2**: Implementar Fase 3 (Integración en modal de permisos en `Roles/Index.vue`).
3. **Paso 3**: Ejecutar Fase 4 (Pruebas globales y build de Vite).
