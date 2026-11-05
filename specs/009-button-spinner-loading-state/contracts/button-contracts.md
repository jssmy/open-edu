# Contratos de Interfaz: Estado de Carga y Spinner en Botones

**Feature**: `009-button-spinner-loading-state`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Contrato de Componente `Button.vue`

### Declaración de Props en Vue `<script setup>`:
```typescript
withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger';
        type?: 'button' | 'submit';
        loading?: boolean;
        loadingText?: string;
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        type: 'button',
        loading: false,
        loadingText: undefined,
        disabled: false,
    },
);
```

### Contrato HTML / Accesibilidad:
- Atributo `aria-busy="true"` cuando `loading` sea `true`.
- Atributo `disabled` nativo en el elemento `<button>` cuando `disabled || loading`.
- Spinner SVG con `aria-hidden="true"` para lectores de pantalla.

