# Research: Docentes asignados

## Decisión 1: Filtrado y orden en el cliente
- **Decision**: Calcular orden, tope de filas y filtro en un composable del frontend.
- **Rationale**: `availableTeachers` ya llega completo; la selección pendiente vive en el cliente y el filtro es efímero. KISS/YAGNI.
- **Alternatives**: Endpoint de búsqueda con paginación (descartado: pierde selecciones sin guardar, más superficie sin necesidad a la escala actual).

## Decisión 2: Criterio de orden estable
- **Decision**: El grupo "asignados" se fija según la asignación guardada (`course.teachers`), no según la casilla marcada actualmente. Dentro de cada grupo, orden alfabético por nombre (ya viene ordenado del servidor).
- **Rationale**: Evita que la fila salte al marcar/desmarcar (borde de la spec).
- **Alternatives**: Reordenar en vivo (descartado: confuso al interactuar).

## Decisión 3: Regla de filas visibles
- **Decision**: Sin búsqueda: todos los asignados; si son <10, se completan con no asignados hasta 10 en total. Con búsqueda: se aplican las mismas reglas sobre los resultados filtrados (asignados coincidentes primero), mostrando todos los coincidentes asignados y completando hasta 10.
- **Rationale**: Coherente con la aclaración del 2026-11-03.

## Decisión 4: Campos de búsqueda
- **Decision**: Buscar por nombre. El correo no se envía hoy al frontend y exponerlo ampliaría datos personales; queda fuera de esta iteración.
- **Rationale**: Mínimo privilegio. La spec (FR-005) menciona "nombre o correo": se ajusta a nombre salvo que se decida exponer el correo.
- **Alternatives**: Enviar correo al cliente (descartado sin decisión de privacidad).

## Decisión 5: Normalización
- **Decision**: Comparar en minúsculas y sin tildes (`normalize('NFD')` y quitar diacríticos), con trim.

## Decisión 6: Scroll
- **Decision**: Contenedor con alto máximo (≈ 10 filas de 44px) y `overflow-y-auto`, con `role="group"` y etiqueta accesible.
