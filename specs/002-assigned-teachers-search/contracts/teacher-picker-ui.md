# Contrato UI: TeacherPicker

**Props**: `teachers: { id: number; name: string }[]`, `assignedIds: number[]`, `modelValue: number[]` (ids seleccionados).

**Eventos**: `update:modelValue(ids: number[])`.

**Comportamiento**:
- Campo de búsqueda con etiqueta visible "Buscar docente" y botón de limpiar (`IconButton` icono `clear`).
- Lista con alto máximo y scroll; casillas con etiqueta accesible.
- Vacío sin datos: "No hay docentes disponibles." Vacío por filtro: "No se encontraron docentes".
- Guardar sigue usando `assignCourseTeachers(courseId, ids)` sin cambios.

Sin cambios en endpoints.
