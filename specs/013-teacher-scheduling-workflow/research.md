# Research & Technical Decisions: Programación Académica Basada en Docente

## Decision 1: Ubicación e Implementación del Selector de Docente en Cabecera

- **Decision**: Colocar el selector de docente en la zona superior de `Scheduling/Index.vue`, integrado junto o inmediatamente debajo del encabezado de la página, empleando `SearchableSelect` para permitir autocompletado y búsqueda rápida entre los docentes activos.
- **Rationale**: Proporciona foco inmediato al usuario. La gestión horaria se realiza siempre en torno a la disponibilidad de un docente. Al usar `SearchableSelect`, listas con muchos docentes siguen siendo accesibles y cómodas sin saturar la pantalla.
- **Alternatives Considered**:
  - `Select` estándar nativo: Descartado porque no permite buscar docentes por texto cuando la lista supera 10 o 15 usuarios.
  - Dejar el docente solo en el modal de creación: Descartado por ser el motivo del requerimiento; se busca visualizar la agenda del docente antes y durante la planificación.

## Decision 2: Comportamiento Inicial y Sincronización con la Tabla Inferior

- **Decision**: 
  1. Si no viene un query param `teacher` en la URL, el controlador o componente selecciona por defecto al primer docente disponible de la lista `$teachers` (si existe) y permite seleccionar `""` / "Todos los docentes".
  2. Al cambiar el docente en el selector superior, se realiza un Inertia visit (`router.get('/scheduling', { ...filters, teacher: selectedTeacherId }, { preserveState: true, preserveScroll: true, replace: true })`), sincronizando tanto el calendario (`calendarBlocks`) como la tabla inferior paginada (`schedules`).
- **Rationale**: Cumple explícitamente con la clarificación acordada: el cambio de docente sincroniza tanto el calendario interactivo como el listado de ofertas inferior.
- **Alternatives Considered**:
  - Filtrado puramente en cliente sin consultar al backend: Descartado porque la paginación de ofertas (`CourseOffering`) se realiza en servidor y requiere que la base de datos entregue las filas correspondientes al docente seleccionado.

## Decision 3: Reubicación de Filtros en la Tabla Inferior

- **Decision**: Los filtros de texto (`q`), curso (`course`), aula (`classroom`) y estado (`status`) se reubican en un panel de búsqueda específico situado inmediatamente encima de `<Table>`.
- **Rationale**: Limpia el espacio visual del calendario FullCalendar, agrupando los filtros de búsqueda exactamente donde producen efecto (la tabla de resultados de ofertas).
- **Alternatives Considered**:
  - Mantener los filtros en un sidebar flotante o drawer: Innecesariamente complejo para la arquitectura simple y directa del sistema.
