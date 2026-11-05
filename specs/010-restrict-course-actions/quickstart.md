# Quickstart & Verification Guide

## Testing Scenario

1. Iniciar sesión con un usuario docente asignado al curso:
```bash
vendor/bin/pest tests/Feature/Courses/TeacherCourseAccessTest.php
```

2. Pruebas esperadas:
- Verificar que `canManageCourses` llega en `false` para un docente asignado sin `courses.manage`.
- Verificar que el docente asignado recibe `403 Forbidden` al enviar `PATCH /courses/{id}/status`.
- Verificar que el docente asignado recibe `403 Forbidden` al enviar `DELETE /courses/{id}`.
- Verificar que un administrador con `courses.manage` sí recibe `canManageCourses = true` y puede ejecutar ambas acciones.

