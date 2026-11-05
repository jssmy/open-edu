# Tasks: Notificaciones de Operaciones vía Modal Alert

**Input**: Design documents from `specs/008-modal-alert-notifications/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`
**Feature Directory**: `specs/008-modal-alert-notifications`

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., [US1], [US2], [US3])
- Include exact file paths in descriptions

---

## Phase 1: Setup & Foundational (Infrastructure)

**Purpose**: Ampliar el composable y el componente del sistema de diseño para soportar alertas de notificación pura (un solo botón) y helpers explícitos.

- [x] T001 Actualizar tipos y estado reactivo para soportar `cancelLabel?: string | null` en `resources/js/composables/useAlertDialog.ts`
- [x] T002 Implementar helpers de notificación explícita `notifySuccess`, `notifyError`, `notifyWarning` y `notifyInfo` en `resources/js/composables/useAlertDialog.ts`
- [x] T003 Actualizar `resources/js/design-system/AlertDialog.vue` para ocultar el botón cancelar y centrar el botón de confirmación cuando `cancelLabel` sea `null` o no esté definido
- [x] T004 [P] Actualizar `resources/js/components/AlertDialogHost.vue` para enlazar correctamente las propiedades del estado reactivo de alerta
- [x] T005 [P] Añadir pruebas unitarias para alertas de notificación pura (un solo botón y helpers) en `resources/js/__tests__/AlertDialog.test.ts`

**Checkpoint**: Infraestructura de alertas lista y comprobada para invocaciones informativas de un solo botón.

---

## Phase 2: User Story 1 - Notificación Modal para Operaciones Exitosas (Priority: P1) 🎯 MVP

**Goal**: Permitir que acciones exitosas (como guardar permisos en la gestión de roles) muestren el modal de alerta con variante `success` y botón único de confirmación.

**Independent Test**: Abrir el modal de permisos en `/roles`, guardar cambios y verificar que se muestra el diálogo modal `AlertDialog` verde con "Operación exitosa", cerrándose con un clic en "Entendido".

- [x] T006 [US1] Conectar `notifySuccess` en el callback `onSuccess` de la actualización de permisos de roles en `resources/js/pages/Roles/Index.vue`
- [x] T007 [US1] Retirar banners estáticos innecesarios de confirmación en `resources/js/pages/Roles/Index.vue` y `resources/js/layouts/AppLayout.vue`
- [x] T008 [P] [US1] Actualizar pruebas de frontend en `resources/js/__tests__/RolesIndex.test.ts` para verificar la invocación de la alerta de éxito al guardar permisos

**Checkpoint**: User Story 1 (MVP) completamente funcional e independientemente verificable.

---

## Phase 3: User Story 2 - Notificación Modal para Operaciones Fallidas o Errores (Priority: P2)

**Goal**: Permitir que errores generales de backend se notifiquen mediante un modal de alerta con variante `danger`.

**Independent Test**: Simular o provocar un error general en la gestión de roles y verificar que aparece el modal `AlertDialog` rojo con el detalle del error.

- [x] T009 [US2] Conectar `notifyError` en los callbacks `onError` de `resources/js/pages/Roles/Index.vue` ante fallos al guardar permisos
- [x] T010 [US2] Conectar `notifyError` en `resources/js/pages/Roles/Create.vue` ante errores de servidor (`errors.general`)
- [x] T011 [P] [US2] Añadir prueba en `resources/js/__tests__/RolesCreate.test.ts` verificando el disparo de `notifyError` ante error general

**Checkpoint**: User Story 1 y 2 integradas para cubrir tanto el camino feliz como el camino de error.

---

## Phase 4: User Story 3 - Modal de Alerta Informativo con Solo Botón de Confirmación (Priority: P3)

**Goal**: Garantizar que el modal alert informativo no confunda al usuario con botones de cancelar redundantes en ninguna resolución ni vista.

**Independent Test**: Inspeccionar el DOM del modal durante una alerta informativa y comprobar que no existe ningún botón o acción secundaria de cancelación.

- [x] T012 [US3] Revisar y verificar accesibilidad y estilos responsivos del botón único de confirmación en `resources/js/design-system/AlertDialog.vue`
- [x] T013 [P] [US3] Verificar que los diálogos de confirmación interactiva existentes (dos botones: cancelar y confirmar) conserven su funcionamiento íntegro en `resources/js/__tests__/AlertDialog.test.ts`

**Checkpoint**: Experiencia pulida y accesible sin botones ambiguos.

---

## Phase 5: Polish & Regression Verification

**Purpose**: Verificación de toda la suite de pruebas y compilación de producción.

- [x] T014 Ejecutar suite de pruebas de frontend con Vitest: `npm test`
- [x] T015 Ejecutar suite de pruebas de backend con Pest: `php artisan test --filter=Role`
- [x] T016 Ejecutar compilación de producción con Vite: `npm run build`
- [x] T017 Validar los escenarios definidos en `specs/008-modal-alert-notifications/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup/Infraestructura)**: Sin dependencias, inicia inmediatamente.
- **Phase 2 (User Story 1 - MVP)**: Depende de Phase 1.
- **Phase 3 (User Story 2)**: Depende de Phase 1.
- **Phase 4 (User Story 3)**: Depende de Phase 1 y 2.
- **Phase 5 (Polish)**: Depende de todas las fases anteriores.

### Parallel Opportunities
- T004 y T005 pueden desarrollarse en paralelo tras T001 y T003.
- T008 y T011 (Pruebas unitarias de vistas) pueden ejecutarse en paralelo con los componentes respectivos.

---

## Implementation Strategy (MVP First)

1. **Paso 1**: Completar Fase 1 (Soporte de un botón en `AlertDialog.vue` y helpers en `useAlertDialog.ts`).
2. **Paso 2**: Implementar Fase 2 (User Story 1 - Alertas de éxito en guardado de permisos) como **MVP**.
3. **Paso 3**: Implementar Fase 3 (User Story 2 - Alertas de error).
4. **Paso 4**: Implementar Fase 4 (User Story 3 - Verificación de ergonomía visual).
5. **Paso 5**: Ejecutar Fase 5 (Validación completa, pruebas y compilación).
