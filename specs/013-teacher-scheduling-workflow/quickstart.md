# Quickstart & Validation Guide: Programación Académica Basada en Docente

## Pre-requisitos
- Base de datos poblada con docentes activos y cursos programados.
- Servidor ejecutándose localmente (`php artisan serve` o `http://localhost:8000`).

## Escenario de Validación 1: Selección Inicial y Visualización por Docente
1. Navegar a `http://localhost:8000/scheduling`.
2. **Resultado esperado**:
   - En la parte superior de la página (sobre el calendario) aparece el selector desplegable con búsqueda de docente.
   - El selector muestra seleccionado por defecto al primer docente activo disponible.
   - El calendario semanal (`ScheduleCalendar`) muestra únicamente las clases y sesiones pertenecientes a ese docente.
   - La tabla inferior muestra las ofertas académicas vinculadas a ese docente.

## Escenario de Validación 2: Cambio de Docente
1. En el selector superior, buscar y seleccionar a otro docente diferente.
2. **Resultado esperado**:
   - El calendario actualiza sus bloques en pantalla reflejando la agenda del nuevo docente.
   - La tabla inferior se sincroniza automáticamente mostrando las ofertas del nuevo docente.

## Escenario de Validación 3: Opción "Todos los docentes"
1. En el selector superior, seleccionar la opción "Todos los docentes".
2. **Resultado esperado**:
   - El calendario muestra la programación combinada de todos los docentes.
   - La tabla inferior lista todas las programaciones vigentes sin filtrar por docente específico.

## Escenario de Validación 4: Filtros de la Tabla Inferior
1. Con un docente seleccionado, desplazarse a la cabecera de la tabla inferior.
2. Ingresar un término de búsqueda en `Buscar` o seleccionar un `Curso` / `Estado` específico.
3. Presionar el botón de búsqueda.
4. **Resultado esperado**:
   - La tabla inferior se filtra acotando los resultados según los filtros ingresados.
   - El selector de docente superior conserva el docente activo seleccionado.

## Escenario de Validación 5: Creación de Bloque / Programación con Docente Precargado
1. Con un docente seleccionado en la cabecera, hacer clic en una celda vacía del calendario o en el botón "Nueva programación".
2. Inspeccionar el campo "Docente" en el modal de creación.
3. **Resultado esperado**:
   - El campo "Docente" del modal está precargado con el docente que estaba seleccionado en la vista principal.
