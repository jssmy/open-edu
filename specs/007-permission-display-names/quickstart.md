# Quickstart Guide: Validación de Nombres Descriptivos de Permisos

**Feature**: `007-permission-display-names`  
**Date**: 2026-11-04  
**Status**: Ready

## 1. Prerrequisitos
- Base de datos inicializada y roles del sistema sembrados:
  ```bash
  php artisan migrate
  php artisan db:seed --class=RolesAndPermissionsSeeder
  ```

## 2. Escenarios de Validación Rápida

### Escenario 1: Creación de Rol con Nombres Descriptivos de Permisos
1. Iniciar sesión como Administrador (`admin@openedu.test`).
2. Navegar a `/roles/create`.
3. Verificar visualmente:
   - Los permisos aparecen con nombres en español inteligibles (ej. "Gestión de cursos", "Gestión de programación académica").
   - Los permisos están agrupados o identificados con su módulo funcional (ej. "Cursos", "Programación", "Usuarios").
   - No se observa texto crudo tipo `courses.manage` como etiqueta principal del checkbox.
4. Llenar el formulario con:
   - Nombre: `Coordinador de Evaluaciones`
   - Descripción: `Encargado de supervisar notas`
   - Permisos seleccionados: Marcar "Gestión de evaluaciones" y "Consulta de reportes".
5. Presionar "Crear rol".
6. Confirmar redirección exitosa a `/roles` con notificación de éxito.

### Escenario 2: Asignación de Permisos desde el Modal en Roles
1. En `/roles`, ubicar el rol creado o cualquier rol editable.
2. Hacer clic en "Permisos".
3. Verificar que el modal lista los permisos con sus nombres descriptivos organizados.
4. Modificar la selección y guardar.
5. Confirmar que los cambios se persisten y se muestran seleccionados al volver a abrir el modal.

### Escenario 3: Ejecución de Pruebas Automatizadas
Ejecutar la suite de pruebas de Roles para verificar que no hay regresiones:
```bash
php artisan test --filter=Role
```
Y ejecutar pruebas frontend de Vue/Vitest:
```bash
npm run test
```

