# Data Model: Notificaciones de Operaciones vía Modal Alert

**Feature**: `008-modal-alert-notifications`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Composable Types & Contracts (`useAlertDialog.ts`)

```typescript
export type AlertDialogVariant = 'info' | 'success' | 'warning' | 'danger';

export interface AlertDialogOptions {
    variant: AlertDialogVariant;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string | null; // null indica que el diálogo es informativo (oculta botón cancelar)
}

export interface AlertDialogState {
    open: boolean;
    variant: AlertDialogVariant;
    title: string;
    message: string;
    confirmLabel: string;
    cancelLabel: string | null;
}
```

## 2. API Helper Functions (`useAlertDialog.ts`)

```typescript
/**
 * Muestra una notificación modal de éxito con solo botón de confirmación.
 */
export function notifySuccess(message: string, title = 'Operación exitosa'): Promise<boolean>;

/**
 * Muestra una notificación modal de error con solo botón de confirmación.
 */
export function notifyError(message: string, title = 'No se pudo completar la operación'): Promise<boolean>;

/**
 * Muestra una notificación modal de advertencia con solo botón de confirmación.
 */
export function notifyWarning(message: string, title = 'Atención'): Promise<boolean>;

/**
 * Muestra una notificación modal informativa con solo botón de confirmación.
 */
export function notifyInfo(message: string, title = 'Información'): Promise<boolean>;
```

## 3. Comportamiento Visual de `AlertDialog.vue`

- Si `cancelLabel` es falsy/`null`:
  - Contenedor de acciones: `flex justify-center` con un único botón `Button` (`confirmLabel ?? 'Entendido'`).
- Si `cancelLabel` es string (ej. `'Cancelar'`):
  - Contenedor de acciones: `grid grid-cols-1 gap-3 sm:grid-cols-2` con botón secundario y botón principal.

