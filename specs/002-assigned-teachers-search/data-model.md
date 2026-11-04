# Data Model

Sin cambios de persistencia. Estructuras de presentación:

- **TeacherOption**: `{ id: number; name: string }` (ya existente en `availableTeachers`).
- **Estado del selector**:
  - `assignedIds`: ids guardados del curso (fijan el grupo prioritario).
  - `selectedIds`: ids marcados actualmente (incluye ocultos por el filtro).
  - `query`: texto de búsqueda (trim, sin tildes, minúsculas).
- **Lista visible** = asignados que coinciden + no asignados que coinciden, truncada a: todos los asignados, o 10 filas totales si los asignados son <10.

Reglas: `selectedIds` nunca se modifica por filtrar; se envía completo al guardar.
