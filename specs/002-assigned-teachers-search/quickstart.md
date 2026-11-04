# Quickstart: validación

**Prerrequisitos**: `composer run dev` (o `npm run dev`), usuario con permiso `courses.manage`, curso con docentes disponibles (ver `DemoDataSeeder`).

1. Abrir `/courses/{id}`, sección "Docentes asignados".
2. Con 3 asignados: ver 3 asignados primero y 10 filas en total.
3. Con 100+ asignados: ver todos con scroll interno; la página no crece.
4. Escribir parte de un nombre: la lista se filtra, asignados primero; sin coincidencias se ve "No se encontraron docentes".
5. Marcar un docente, filtrar para ocultarlo, borrar el filtro: sigue marcado; guardar y recargar lo conserva.

**Pruebas**: `npx vitest run resources/js/__tests__/useTeacherPicker.test.ts resources/js/__tests__/TeacherPicker.test.ts` y `php artisan test --compact tests/Feature/Courses/TeacherUnassignTest.php`.
