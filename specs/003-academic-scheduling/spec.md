# Feature Specification: Programación académica (horarios, docentes y aulas)

**Feature Branch**: `003-academic-scheduling`

**Created**: 2026-11-03

**Status**: Draft

**Input**: User description: "Hay que cambiar el flujo de asignar los docentes: debe haber un módulo de programación académica donde se pueda gestionar el horario de los cursos, asignar el docente y el horario, el aula o indicar si sería virtual."

## Clarifications

### Session 2026-11-03

- Q: ¿Qué pasa con la asignación de docentes en el detalle del curso? → A: Se elimina; el detalle del curso solo muestra los docentes en modo lectura y la asignación se hace únicamente desde Programación académica.
- Q: ¿Quién puede gestionar la programación académica? → A: Un permiso nuevo asignable a roles; por defecto lo tiene el administrador.
- Q: ¿Se rechaza el traslape de docente en clases virtuales? → A: Sí, el traslape de docente se rechaza siempre, sea presencial o virtual.
- Q: ¿Cómo se selecciona el docente al crear o editar una programación académica? → A: Mediante un desplegable reutilizable con buscador (`SearchableSelect`) que muestra como máximo 5 docentes visibles a la vez con desplazamiento interno (scroll) para no ocupar toda la pantalla.
- Q: ¿Los horarios son semanales sin límite o dentro de un periodo académico? → A: Cada programación pertenece a un periodo académico con fechas de inicio y fin; los conflictos se validan solo dentro del mismo periodo.
- Q: ¿A qué periodo se asocian las asignaciones existentes al migrar? → A: Se crean como programaciones pendientes, sin periodo ni horario, en una lista "Por programar" hasta completarse.
- Q: ¿Qué pasa con las programaciones de un periodo ya terminado? → A: Se bloquean por completo (solo lectura) y no se pueden reabrir.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Programar un curso con docente, horario y modalidad (Priority: P1)

Quien administra la programación académica abre el módulo, elige un curso y crea una programación: selecciona el docente, define uno o más bloques de horario (día, hora de inicio y fin) y establece el lugar: un aula física o modalidad virtual.

**Why this priority**: Es el núcleo del cambio; reemplaza la asignación de docentes dentro del detalle del curso por un flujo centralizado.

**Independent Test**: Crear una programación para un curso con docente, dos bloques semanales y un aula; verificar que queda guardada y visible en el módulo.

**Acceptance Scenarios**:

1. **Given** un curso sin programación, **When** se asigna un docente, un bloque de horario y un aula, **Then** la programación se guarda y el docente queda asignado al curso.
2. **Given** una programación nueva, **When** se marca como virtual, **Then** no se exige aula y se muestra "Virtual" como lugar.
3. **Given** una programación presencial, **When** no se selecciona aula, **Then** el sistema rechaza el guardado indicando que falta el aula.
4. **Given** un curso con varias programaciones, **When** se consulta, **Then** se listan todas con docente, horario y lugar.

---

### User Story 2 - Evitar conflictos de horario (Priority: P2)

Al guardar, el sistema valida que el docente no tenga otro bloque que se traslape y que el aula física no esté ocupada en ese horario. Las programaciones virtuales no validan aula.

**Why this priority**: Evita programaciones inviables, pero el módulo ya es útil sin esto.

**Independent Test**: Intentar programar dos cursos con el mismo docente en horarios traslapados y comprobar el rechazo con mensaje claro.

**Acceptance Scenarios**:

1. **Given** un docente con clase lunes 08:00–10:00, **When** se le asigna otra el lunes 09:00–11:00, **Then** se rechaza señalando el conflicto.
2. **Given** un aula ocupada martes 10:00–12:00, **When** se intenta otra programación en esa aula y franja, **Then** se rechaza señalando el conflicto de aula.
3. **Given** bloques consecutivos (08:00–10:00 y 10:00–12:00), **When** se guardan, **Then** no se consideran conflicto.
4. **Given** dos programaciones virtuales del mismo horario con docentes distintos, **When** se guardan, **Then** se aceptan.

---

### User Story 3 - Editar, reasignar y consultar horarios (Priority: P3)

Se puede modificar o eliminar una programación (cambiar docente, horario, aula/virtual) y consultar horarios por curso, por docente y por aula.

**Why this priority**: Mantenimiento y visibilidad una vez creada la programación.

**Independent Test**: Cambiar el docente de una programación y verificar que el anterior pierde la asignación y el nuevo la obtiene; filtrar por docente.

**Acceptance Scenarios**:

1. **Given** una programación existente, **When** se cambia el docente, **Then** el nuevo docente queda asignado y el anterior deja de estarlo si no tiene otra programación en ese curso.
2. **Given** una programación, **When** se elimina, **Then** desaparece del módulo y se actualizan las asignaciones.
3. **Given** varias programaciones, **When** se filtra por docente o aula, **Then** solo se muestran las coincidentes.
4. **Given** un docente, **When** consulta su horario, **Then** ve sus cursos, bloques y lugar.

---

### Edge Cases

- Bloque con hora de fin menor o igual a la de inicio: se rechaza.
- Docente o aula desactivados o eliminados con programaciones vigentes: se informa y se impide la eliminación o se exige reasignar.
- Cambio de presencial a virtual: se libera el aula.
- Curso con docentes asignados previamente bajo el flujo anterior: se conservan y aparecen en la lista "Por programar".
- Usuario sin permiso de programación académica: no puede acceder al módulo.
- Intento de editar o eliminar una programación de un periodo terminado: se rechaza indicando que el periodo está cerrado.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST ofrecer un módulo de "Programación académica" accesible solo a usuarios con el permiso de gestión de programación académica, asignable a roles y concedido por defecto al administrador.
- **FR-002**: El sistema MUST permitir crear programaciones de un curso con docente, uno o más bloques de horario (día, inicio, fin) y lugar.
- **FR-003**: El lugar MUST ser un aula física o modalidad virtual; el aula es obligatoria si no es virtual.
- **FR-004**: El sistema MUST permitir gestionar el catálogo de aulas (nombre, capacidad opcional, estado).
- **FR-005**: El sistema MUST rechazar traslapes de horario del mismo docente, sea la clase presencial o virtual.
- **FR-006**: El sistema MUST rechazar traslapes de horario en una misma aula física.
- **FR-007**: El sistema MUST permitir editar y eliminar programaciones.
- **FR-008**: La asignación de docentes a un curso MUST derivarse de la programación académica; el detalle del curso MUST mostrar los docentes asignados solo en modo lectura, sin controles de asignación.
- **FR-009**: El sistema MUST permitir consultar horarios filtrando por curso, docente y aula.
- **FR-010**: El sistema MUST migrar las asignaciones existentes sin pérdida, creándolas como programaciones pendientes, sin periodo ni horario, visibles en una lista "Por programar" hasta que se completen.
- **FR-011**: Los docentes MUST poder ver su propio horario mediante el permiso `scheduling.view-own`.
- **FR-012**: Cada programación MUST pertenecer a un periodo académico con fecha de inicio y fin; el sistema MUST permitir gestionar periodos.
- **FR-013**: Los conflictos de docente y aula (FR-005, FR-006) MUST validarse solo entre programaciones del mismo periodo.
- **FR-014**: Las programaciones de un periodo cuya fecha de fin ya pasó MUST quedar bloqueadas (solo lectura), sin posibilidad de edición, eliminación ni reapertura.
- **FR-015**: El selector de docente en el formulario de programación académica MUST utilizar un desplegable reutilizable con buscador (`SearchableSelect`) que muestre un máximo de 5 opciones visibles con scroll vertical y todos los modales MUST incluir un botón "Cancelar".

### Key Entities

- **Programación**: Vincula curso, periodo académico, docente, modalidad (presencial/virtual) y aula opcional; contiene bloques de horario.
- **Bloque de horario**: Día de la semana, hora de inicio y hora de fin.
- **Periodo académico**: Ciclo con nombre, fecha de inicio y fecha de fin; agrupa programaciones.
- **Aula**: Espacio físico con nombre, capacidad opcional y estado.
- **Curso / Docente**: Entidades existentes relacionadas.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador programa un curso (docente, horario y lugar) en menos de 2 minutos.
- **SC-002**: 100% de los traslapes de docente o aula se detectan antes de guardar.
- **SC-003**: 100% de las asignaciones existentes se conservan tras la migración.
- **SC-004**: Un docente identifica su horario semanal completo en menos de 30 segundos.

## Assumptions

- Dentro de un periodo, los horarios son semanales recurrentes; no se modelan sesiones con fechas específicas.
- Un curso puede tener varias programaciones (p. ej., secciones o varios docentes).
- Los permisos se gestionan con el sistema de roles existente, añadiendo uno para programación académica.
- No se valida la capacidad del aula contra el número de estudiantes.
- Fuera de alcance: matrícula de estudiantes por sección, notificaciones y calendarios externos.
