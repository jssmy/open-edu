# Research: Estado de Carga, Spinner y Deshabilitación en Botones

**Feature**: `009-button-spinner-loading-state`  
**Date**: 2026-11-04  
**Status**: Completed

## 1. Contexto y Problema
En `open-edu`, el componente base `resources/js/design-system/Button.vue` ya define la prop `loading?: boolean` y aplica `:disabled="disabled || loading"` y `:aria-busy="loading"`. Sin embargo:
1. No renderiza ningún spinner SVG visual (`animate-spin`), por lo que el botón solo atenúa ligeramente su opacidad (`disabled:opacity-50`) sin comunicar dinamismo de carga.
2. No tiene soporte para cambiar su etiqueta dinámicamente durante la carga (por ejemplo, mostrando "Guardando...").
3. En modales como el de permisos en `Roles/Index.vue`, la acción `savePermissions` no enlazaba un estado reactivo de carga a la prop `:loading` del botón "Guardar y cerrar".

## 2. Decisiones Técnicas

### Decisión 1: Spinner SVG en `Button.vue`
- **Decisión**:
  - Incorporar un SVG circular con `animate-spin` integrado directamente en `Button.vue`:
    ```vue
    <svg
        v-if="loading"
        class="size-4 animate-spin"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    ```
  - La prop `loadingText?: string` se evalúa:
    - Si `loading && loadingText`: se renderiza `loadingText`.
    - Si no (`!loading` o `!loadingText`): se renderiza `<slot />`.

### Decisión 2: Sincronización en Modales y Formularios
- **Decisión**:
  - En `Roles/Index.vue`:
    - Agregar `const isSavingPermissions = ref(false);`
    - Al invocar `savePermissions`:
      ```ts
      isSavingPermissions.value = true;
      router.put(`/roles/${editingRole.value.id}/permissions`, { permissions: selectedPermissions.value }, {
          preserveScroll: true,
          onSuccess: () => {
              isSavingPermissions.value = false;
              editingRole.value = null;
              void notifySuccess('Los permisos del rol han sido guardados correctamente.');
          },
          onError: (errors) => {
              isSavingPermissions.value = false;
              void notifyError(errors.general ?? errors.permissions ?? 'No se pudieron actualizar los permisos del rol.');
          },
      });
      ```
    - En el botón: `:loading="isSavingPermissions"` y `loading-text="Guardando..."`.
    - En el botón cancelar: `:disabled="isSavingPermissions"`.

## 3. Conclusión
El cambio central en `Button.vue` propaga instantáneamente el spinner a todos los botones que ya usan `:loading="form.processing"` a lo largo de toda la plataforma, satisfaciendo el requerimiento de forma elegante y centralizada.

