# Contratos de Interfaz: Notificaciones de Operaciones vía Modal Alert

**Feature**: `008-modal-alert-notifications`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Contrato de Notificación en Componentes Vue

### 1.1 Invocación de Notificación de Éxito
```typescript
import { notifySuccess } from '@/composables/useAlertDialog';

// Al completar una acción exitosa
await notifySuccess('Los permisos del rol han sido guardados correctamente.');
```

### 1.2 Invocación de Notificación de Error
```typescript
import { notifyError } from '@/composables/useAlertDialog';

// Al recibir un error o rechazo
await notifyError(errors.general ?? 'Ocurrió un error al procesar la solicitud.');
```

### 1.3 Contrato de Props y Emits de `AlertDialog.vue`
```typescript
defineProps<{
    open: boolean;
    variant?: 'info' | 'success' | 'warning' | 'danger';
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string | null;
}>();

defineEmits<{
    confirm: [];
    cancel: [];
}>();
```

