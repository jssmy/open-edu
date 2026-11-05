# Data Model: Estado de Carga, Spinner y Deshabilitación en Botones

**Feature**: `009-button-spinner-loading-state`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Contrato de Componente (`Button.vue`)

### Props Interface:
```typescript
export interface ButtonProps {
    variant?: 'primary' | 'secondary' | 'danger';
    type?: 'button' | 'submit';
    loading?: boolean;
    loadingText?: string;
    disabled?: boolean;
}
```

### Valores por defecto:
- `variant`: `'primary'`
- `type`: `'button'`
- `loading`: `false`
- `loadingText`: `undefined`
- `disabled`: `false`

### Comportamiento del Renderizado:
- `v-if="loading"`: Muestra el spinner animado SVG (`animate-spin`).
- Contenido textual:
  - Si `loading && loadingText`: Muestra `loadingText`.
  - En cualquier otro caso: Muestra `<slot />`.
- Atributos dinámicos:
  - `disabled`: `disabled || loading`
  - `aria-busy`: `loading ? 'true' : undefined`
  - Clase CSS: `disabled:cursor-not-allowed disabled:opacity-50`

## 2. Contrato de Estado en Vistas con Acciones Asíncronas

En vistas con llamadas asíncronas no gestionadas por `useForm` (ej. `Roles/Index.vue`):
```typescript
const isSaving = ref(false);
```
Enlazado a:
```vue
<Button
    variant="primary"
    :loading="isSaving"
    loading-text="Guardando..."
    @click="save"
>
    Guardar y cerrar
</Button>
```

