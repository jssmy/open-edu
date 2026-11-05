# Quickstart Guide: Validación de Spinner y Deshabilitación en Botones

**Feature**: `009-button-spinner-loading-state`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Escenarios de Validación

### Escenario 1: Botón en Estado Loading con Spinner y Texto
1. Visitar `/roles`.
2. Abrir el modal de permisos de cualquier rol editable (ej. `Docente`).
3. Modificar un permiso y presionar **"Guardar y cerrar"**.
4. **Verificación visual inmediata**:
   - El botón pasa a mostrar un spinner animado girando suavemente.
   - La etiqueta cambia visiblemente a "Guardando...".
   - El botón de guardar y el botón de cancelar quedan deshabilitados contra clics.
5. Al recibir la respuesta del servidor:
   - Se muestra el modal de alerta (`AlertDialog`) de éxito.
   - El modal de edición se cierra.

### Escenario 2: Botón de Envío en Formulario de Creación de Rol
1. Visitar `/roles/create`.
2. Completar los campos y enviar el formulario.
3. Verificar que durante el envío, el botón principal muestra el spinner animado.

### Escenario 3: Pruebas Unitarias Automatizadas
Ejecutar la suite de pruebas unitarias de componentes de frontend:
```bash
PATH="/opt/homebrew/bin:$PATH" npx vitest run resources/js/__tests__/design-system/components.test.ts
```

