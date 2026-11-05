# Research: Notificaciones de Operaciones vía Modal Alert

**Feature**: `008-modal-alert-notifications`  
**Date**: 2026-11-04  
**Status**: Completed

## 1. Contexto y Problema
En `open-edu`, el sistema cuenta con el componente `AlertDialog.vue`, el composable `useAlertDialog.ts` y el host `AlertDialogHost.vue`. Sin embargo:
1. `AlertDialog.vue` siempre renderiza una cuadrícula de dos columnas con botón `Cancelar` y `Continuar/Confirmar`. Para notificaciones de estado (operación completada o error que ya ocurrió), el botón `Cancelar` resulta inapropiado y confuso.
2. Los mensajes de éxito se presentaban típicamente como banners estáticos en el layout (`<Alert v-if="page.props.flash?.success">`) o banners inline dentro de los formularios (`<Alert v-if="$page.props.errors?.general">`).
3. El usuario ha determinado en la clarificación que los mensajes de operaciones exitosas y fallidas deben presentarse como modal alert a través de **llamadas explícitas** desde cada formulario o acción.

## 2. Decisiones Técnicas

### Decisión 1: Flexibilizar `AlertDialog.vue` y `useAlertDialog.ts` para Diálogos Informativos
- **Decisión**:
  - Permitir que `cancelLabel` sea `null` o `undefined` en `AlertDialogOptions`.
  - En `AlertDialog.vue`:
    - Si `cancelLabel` está definido (string no vacío), renderizar los dos botones en grid (`cancel` y `confirm`).
    - Si `cancelLabel` es `null`, renderizar únicamente el botón `confirm` centrado o a ancho completo (`sm:w-auto sm:min-w-32 mx-auto` o `w-full`).
  - En `useAlertDialog.ts`:
    - Incorporar helpers ergonómicos:
      - `notifySuccess(message: string, title?: string): Promise<boolean>`
      - `notifyError(message: string, title?: string): Promise<boolean>`
      - `notifyWarning(message: string, title?: string): Promise<boolean>`
      - `notifyInfo(message: string, title?: string): Promise<boolean>`
    - Estos helpers llaman a `requestAlertDialog` configurando `cancelLabel: null` y `confirmLabel: 'Entendido'` o `'Aceptar'`.

### Decisión 2: Patrón de Uso en Formularios y Acciones
- **Decisión**:
  - En formularios que usan Inertia `useForm` (ej. `Roles/Create.vue`):
    ```ts
    form.post('/roles', {
        onError: (errors) => {
            if (errors.general) {
                notifyError(errors.general);
            }
        },
    });
    ```
  - En acciones que usan `router.put` o `submit` (ej. `Roles/Index.vue` para guardar permisos):
    ```ts
    router.put(`/roles/${editingRole.value.id}/permissions`, { permissions: selectedPermissions.value }, {
        preserveScroll: true,
        onSuccess: () => {
            editingRole.value = null;
            notifySuccess('Los permisos del rol se han actualizado correctamente.');
        },
        onError: (errors) => {
            notifyError(errors.general ?? 'No se pudieron actualizar los permisos.');
        },
    });
    ```
  - En `Roles/Create.vue`: al crearse el rol, redirige a `/roles`. Cuando el usuario aterriza o si se gestiona en la vista, se garantiza la confirmación adecuada.

### Decisión 3: Retrocompatibilidad
- Todos los llamados existentes a `requestAlertDialog` (como confirmaciones de inactivar roles, salir con cambios sin guardar o descartar lecciones) envían `cancelLabel` y `confirmLabel`, por lo que siguen mostrando el diálogo de confirmación con dos botones sin alteraciones.

