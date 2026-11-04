# Feature Specification: Lista priorizada y buscador de docentes asignados

**Feature Branch**: `002-assigned-teachers-search`

**Created**: 2026-11-03

**Status**: Draft

**Input**: User description: "Sobre la pantalla 'Docentes asignados' (detalle de curso) se debe priorizar en la lista a los docentes asignados; si hay 100 asignados se muestran todos con scroll para no ocupar todo el alto. Si hay menos de 10 asignados, se muestran como mínimo 10 en la lista priorizando los seleccionados. La sección debe tener un buscador para ubicar docentes."

## Clarifications

### Session 2026-11-03

- Q: Cuando hay menos de 10 docentes asignados, ¿la lista muestra al menos 10 filas o como máximo 10? → A: Se muestran todos los asignados; si son menos de 10, la lista se completa con no asignados hasta un total de 10 filas (nunca más de 10 en ese caso), asignados siempre primero. Con 10 o más asignados se muestran todos con scroll.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver primero a los docentes asignados (Priority: P1)

Quien gestiona un curso abre la sección "Docentes asignados" y ve a los docentes ya asignados al inicio de la lista, seguidos de otros docentes disponibles. La lista tiene alto limitado y desplazamiento interno.

**Why this priority**: Es el núcleo del cambio: saber de un vistazo quién está asignado, sin que la sección ocupe toda la pantalla.

**Independent Test**: Abrir un curso con 100 docentes asignados y otro con 3; verificar orden, alto contenido y scroll.

**Acceptance Scenarios**:

1. **Given** un curso con 100 docentes asignados, **When** se abre la sección, **Then** los 100 aparecen al inicio de la lista, accesibles mediante scroll interno y la sección no excede un alto máximo fijo.
2. **Given** un curso con 3 docentes asignados y más docentes disponibles, **When** se abre la sección, **Then** se muestran los 3 asignados primero y se completa hasta un total de 10 filas con docentes no asignados.
3. **Given** que hay menos de 10 docentes en total, **When** se abre la sección, **Then** se muestran todos, asignados primero.
4. **Given** un docente que se marca o desmarca, **When** se guardan los cambios, **Then** al recargar queda en el grupo correcto (asignados primero).

---

### User Story 2 - Buscar docentes (Priority: P2)

Quien gestiona el curso escribe en un buscador dentro de la sección para ubicar rápidamente a un docente por nombre.

**Why this priority**: Con muchos docentes el scroll solo es insuficiente para encontrar a alguien concreto.

**Independent Test**: Escribir parte de un nombre y comprobar que la lista se filtra; borrar el texto y comprobar que vuelve a la vista priorizada.

**Acceptance Scenarios**:

1. **Given** una lista con muchos docentes, **When** se escribe un texto en el buscador, **Then** solo se muestran docentes cuyo nombre coincide (sin distinguir mayúsculas ni tildes), manteniendo asignados primero.
2. **Given** una búsqueda sin coincidencias, **When** se filtra, **Then** se muestra el mensaje "No se encontraron docentes".
3. **Given** un texto de búsqueda, **When** se borra, **Then** vuelve la vista priorizada con un total de 10 filas (o todos los asignados si son 10 o más).
4. **Given** docentes marcados y luego filtrados fuera de la vista, **When** se guarda, **Then** las selecciones ocultas por el filtro se conservan.

---

### Edge Cases

- Curso sin docentes asignados: se muestran hasta 10 disponibles sin grupo de asignados.
- No existen docentes disponibles: se mantiene el mensaje "No hay docentes disponibles."
- Búsqueda con espacios al inicio o final: se ignoran.
- Un docente asignado queda desmarcado sin guardar: permanece visible y no se reordena hasta guardar.
- Más de 10 docentes asignados y búsqueda que coincide con un no asignado: aparece tras los asignados coincidentes.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: La lista de "Docentes asignados" MUST mostrar primero a todos los docentes asignados al curso y después a los no asignados.
- **FR-002**: La lista MUST tener un alto máximo y desplazamiento interno, sin importar cuántos docentes contenga.
- **FR-003**: Cuando haya menos de 10 docentes asignados, la vista inicial MUST mostrar exactamente 10 docentes (si existen), asignados primero y completando con no asignados; nunca más de 10 filas en ese caso.
- **FR-004**: Cuando haya 10 o más asignados, MUST mostrarse todos los asignados en la lista desplazable.
- **FR-005**: La sección MUST incluir un buscador por nombre, insensible a mayúsculas y tildes.
- **FR-006**: Al buscar, los resultados MUST conservar el orden asignados primero.
- **FR-007**: Borrar la búsqueda MUST restaurar la vista priorizada.
- **FR-008**: La búsqueda MUST mostrar un mensaje claro cuando no hay coincidencias.
- **FR-009**: Las selecciones hechas por el usuario MUST conservarse al filtrar, aunque el docente quede oculto temporalmente.
- **FR-010**: El buscador MUST ser accesible por teclado y tener etiqueta para lectores de pantalla.
- **FR-011**: Solo usuarios con permiso para gestionar el curso pueden modificar asignaciones, igual que hoy.

### Key Entities

- **Docente**: Usuario con rol docente; atributo relevante: nombre.
- **Asignación de docente a curso**: Relación entre un docente y un curso; determina si el docente se muestra en el grupo prioritario.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Con 100 docentes asignados, la sección no ocupa más de un alto fijo en pantalla y todos son accesibles por scroll.
- **SC-002**: El usuario identifica a los docentes asignados sin desplazarse en el 100% de los casos con menos de 10 asignados.
- **SC-003**: Un docente concreto se localiza en menos de 10 segundos escribiendo parte de su nombre.
- **SC-004**: Los resultados de búsqueda se actualizan de forma percibida como instantánea (menos de 1 segundo).
- **SC-005**: Ninguna selección se pierde al filtrar y guardar.

## Assumptions

- La pantalla es la sección "Docentes asignados" del detalle de curso (`/courses/{id}`).
- "100 asignados" es un ejemplo de volumen, no un tope: siempre se muestran todos los asignados con scroll.
- La búsqueda filtra sobre todos los docentes disponibles, no solo los visibles inicialmente.
- La búsqueda es local sobre la lista ya cargada; no se prevén miles de docentes.
- Los permisos y el guardado de asignaciones existentes no cambian.
