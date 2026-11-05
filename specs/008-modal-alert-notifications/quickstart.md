# Quickstart Guide: Validación de Notificaciones vía Modal Alert

**Feature**: `008-modal-alert-notifications`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Prerrequisitos
- Servidor de desarrollo y base de datos activa con usuario Administrador autenticado.

## 2. Escenarios de Validación

### Escenario 1: Actualización Exitosa de Permisos con Modal Alert
1. Iniciar sesión como Administrador en `/roles`.
2. Abrir el modal "Configurar permisos" de un rol (ej. `Docente`).
3. Cambiar alguna selección de permisos y hacer clic en **"Guardar y cerrar"**.
4. **Resultado esperado**:
   - Emerge inmediatamente el diálogo modal `AlertDialog` con icono verde (`success`), título "Operación exitosa" y el mensaje "Permisos actualizados correctamente."
   - El diálogo presenta **únicamente un botón** ("Entendido" o "Aceptar"), sin botón cancelar redundante.
   - Al pulsar el botón, el modal se cierra fluidamente.

### Escenario 2: Intento Fallido o Error General
1. En `/roles`, intentar una operación inválida o que devuelva error general.
2. **Resultado esperado**:
   - Emerge el diálogo modal `AlertDialog` con icono rojo (`danger`), título "No se pudo completar la operación" y la explicación del error.
   - Solo muestra el botón de confirmación ("Entendido").

### Escenario 3: Verificación de Pruebas Automatizadas
Ejecutar la suite de pruebas unitarias de frontend para `AlertDialog` y `useAlertDialog`:
```bash
PATH="/opt/homebrew/bin:$PATH" npm test -- --run
```

