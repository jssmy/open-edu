# Phase 0: Research & Decision Log

## Problem Statement
En la vista de detalle de curso (`/courses/{id}`), los usuarios docentes asignados al curso están recibiendo la propiedad `canManageCourses = true` en la respuesta de Inertia debido a una condición en el controlador:
```php
'canManageCourses' => $request->user()->can('courses.manage') || ($request->user()->hasRole('docente') && $course->teachers()->whereKey($request->user()->id)->exists()),
```
Esta asignación permite que el frontend renderice las secciones de "Publicación" (cambio de estado entre Borrador/Publicado/Desactivado) y "Zona de peligro" (Eliminar curso), permitiendo que el docente intente o ejecute acciones reservadas exclusivamente para administradores o gestores académicos.

## Decisions & Findings

### Decision 1: Criterio para `canManageCourses`
- **Decisión**: La prop `canManageCourses` en `CourseController::show` debe evaluarse **únicamente** con `$request->user()->can('courses.manage')`.
- **Justificación**: El rol `docente` solo tiene responsabilidades de impartición y/o edición de contenido en cursos asignados (`content.manage`), pero nunca administración curricular institucional (publicar, archivar, desactivar o eliminar un curso).

### Decision 2: Protección a Nivel de Ruta y Endpoints
- **Decisión**: Verificar y corroborar que las rutas `DELETE /courses/{course}` y `PATCH /courses/{course}/status` estén resguardadas bajo el middleware `can:courses.manage`.
- **Hallazgo**: `routes/web.php` ya agrupa estas dos rutas bajo `Route::middleware('can:courses.manage')`. Cualquier intento de petición HTTP directa por un docente sin este permiso es rechazado con `403 Forbidden`.
- **Acción**: Añadir pruebas explícitas de regresión que verifiquen que un docente asignado no puede ejecutar `DELETE` ni `PATCH /status` y que en `Inertia::render('Courses/Show')` la prop `canManageCourses` es estrictamente `false`.

### Decision 3: Frontend `Courses/Show.vue`
- **Decisión**: `Courses/Show.vue` ya condiciona los bloques de Publicación y Zona de peligro a `v-if="canManageCourses"`. Al corregir el valor enviado por el backend, dichos bloques quedan automáticamente ocultos para docentes sin permisos administrativos.

