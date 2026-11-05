# Tasks: Programación Académica Basada en Docente y Reorganización de Filtros

**Input**: Design documents from `/specs/013-teacher-scheduling-workflow/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to ([US1], [US2])
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verificación de entorno y dependencias compartidas

- [x] T001 Verificar entorno y rutas en `app/Modules/Scheduling/Infrastructure/Http/SchedulingController.php` y `resources/js/pages/Scheduling/Index.vue`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Asegurar el soporte del backend para inicialización por defecto y filtrado de docente

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Actualizar `app/Modules/Scheduling/Infrastructure/Http/SchedulingController.php` para resolver el docente por defecto: cuando la petición no incluya el parámetro `teacher`, resolver el primer docente activo de `$teachers` (si existe); cuando se reciba `teacher=""`, interpretar como "todos los docentes"; y pasar siempre la lista de docentes con la opción consolidada.
- [x] T003 [P] Agregar prueba Feature en `tests/Feature/SchedulingTeacherFilterTest.php` para verificar que `GET /scheduling` adopta el primer docente activo por defecto cuando no se provee parámetro `teacher`, filtra correctamente las ofertas/sesiones cuando se provee `teacher={id}`, y devuelve todas las ofertas cuando `teacher=""`.

**Checkpoint**: Foundation ready - backend preparado para recibir y responder al flujo de docente y filtros.

---

## Phase 3: User Story 1 - Selección Principal de Docente para Gestión de Horario (Priority: P1) 🎯 MVP

**Goal**: Permitir al coordinador seleccionar un docente desde un desplegable con buscador en la cabecera superior de `/scheduling`, visualizando su agenda semanal en el calendario y precargando automáticamente dicho docente al crear una nueva programación o bloque.

**Independent Test**: Navegar a `/scheduling`. El selector superior debe mostrar al primer docente activo por defecto y el calendario debe mostrar únicamente los bloques y sesiones de ese docente. Cambiar a otro docente debe actualizar inmediatamente el calendario y, al abrir el modal "Nueva programación", el docente activo debe aparecer precargado por defecto.

### Implementation for User Story 1

- [x] T004 [US1] Añadir e integrar el componente `SearchableSelect` para selección de docente en la cabecera superior de `resources/js/pages/Scheduling/Index.vue`, permitiendo buscar entre los docentes disponibles y una opción "Todos los docentes".
- [x] T005 [US1] Conectar el evento de cambio del selector superior de docente en `resources/js/pages/Scheduling/Index.vue` para actualizar la vista vía Inertia (`router.get('/scheduling', ...)`), conservando scroll y actualizando `calendarBlocks`.
- [x] T006 [US1] Modificar `openCreate` en `resources/js/pages/Scheduling/Index.vue` para que el campo `form.teacher_id` se precargue automáticamente con el docente activo seleccionado en la cabecera (o el primero disponible si está en "Todos").

**Checkpoint**: User Story 1 completamente funcional. El gestor puede gestionar el horario por docente en el calendario interactivo.

---

## Phase 4: User Story 2 - Filtros Contextuales y Sincronizados para la Tabla Inferior (Priority: P2)

**Goal**: Reubicar los controles de filtrado (texto `q`, `course`, `classroom`, `status`) para situarlos inmediatamente encima de la tabla inferior de ofertas académicas, y mantener la sincronización automática de la tabla con el docente seleccionado arriba.

**Independent Test**: Cambiar de docente en el selector superior y constatar que la tabla inferior se actualiza mostrando las ofertas del docente. Aplicar un filtro de texto o aula en la barra de la tabla inferior y comprobar que filtra la tabla preservando el docente activo.

### Implementation for User Story 2

- [x] T007 [US2] Reubicar el formulario de filtros (búsqueda `q`, selector `course`, selector `classroom` y selector `status` con sus botones de búsqueda y limpiar) desde la parte superior hacia la sección inmediata superior a `<Table>` en `resources/js/pages/Scheduling/Index.vue`.
- [x] T008 [US2] Ajustar el composable/handler de filtros en `resources/js/pages/Scheduling/Index.vue` para que la búsqueda y paginación de la tabla inferior incluya y mantenga el parámetro `teacher` seleccionado en la cabecera superior.
- [x] T009 [US2] Asegurar que cuando se cambie el docente en la cabecera superior, la tabla inferior se reinicie a la página 1 sincronizando automáticamente las ofertas mostradas.

**Checkpoint**: User Stories 1 y 2 completamente integradas y sincronizadas.

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Verificación de calidad, linters y validación de escenarios de aceptación

- [x] T010 [P] Ejecutar linter y formateador de código en `app/Modules/Scheduling/Infrastructure/Http/SchedulingController.php` y `resources/js/pages/Scheduling/Index.vue`.
- [x] T011 [P] Crear o actualizar pruebas de frontend en `resources/js/__tests__/SchedulingIndex.test.ts` para verificar la presencia del selector de docente en la cabecera y los filtros sobre la tabla.
- [x] T012 Ejecutar la validación completa según la guía `specs/013-teacher-scheduling-workflow/quickstart.md`.
- [x] T013 Actualizar el estado del requerimiento en Obsidian [[REQ-024 - Programacion Academica por Docente y Reubicacion de Filtros]].

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias.
- **Foundational (Phase 2)**: Bloquea la implementación de las historias de usuario para asegurar que el backend maneje el docente por defecto y la opción global.
- **User Story 1 (Phase 3 - P1)**: Depende de Foundational (Phase 2). Entrega el MVP funcional (gestión por docente en calendario).
- **User Story 2 (Phase 4 - P2)**: Depende de US1 y Foundational. Sincroniza la tabla inferior y reubica sus filtros contextuales.
- **Polish (Phase 5)**: Depende de US1 y US2.

---

## Implementation Strategy

### MVP First (User Story 1 Only)
1. Completar T001, T002 y T003 (Backend con docente por defecto y pruebas).
2. Completar T004, T005 y T006 (Selector superior de docente y sincronización con el calendario y modal de creación).
3. Validar de forma independiente: el usuario ya puede planificar por docente.

### Incremental Delivery (User Story 2)
1. Reubicar la barra de filtros a la parte superior de la tabla inferior (T007).
2. Asegurar sincronización en paginación y búsqueda tabular (T008, T009).
3. Ejecutar pruebas finales, formateo y checklist de calidad (T010 - T013).
